<?php

class cmsDebugging {

    const DECIMALS = 5;

    private static $is_enable = false;
    private static $start_time  = array();
    private static $work_time   = array();
    private static $points_data = array();
    private static $points_count = 0;      // Счётчик точек отладки
    private static $types_allowed = ['db', 'events_empty', 'hooks', 'widgets', 'cache']; // Разрешить учёт этих типов событий

    public static function enable($start_data = []) {

        self::$is_enable = true;

        self::$points_count++;
        self::startTimer('cms', self::$points_count);

    }

    public static function pointStart($target) {

        self::$points_count++;
        self::startTimer($target, self::$points_count);
        return self::$points_count;
    }

    public static function pointWork($target, $point_id) {
        self::workTimer($target, $point_id);
    }

    public static function pointProcess($target, $params, $offset = 2, $point_id = 0) {

        if(!self::$is_enable){ return false; }

        if(!in_array($target, self::$types_allowed)){ return false; }

        // Если точка логируется без вызова pointStart(), то увеличиваем счётчик точек
        if ($point_id == 0) { self::$points_count++; }

        $time = self::getTime($target, self::DECIMALS, $point_id);

        $backtrace = debug_backtrace();

        while (($backtrace && !isset($backtrace[0]['line']))) {
            array_shift($backtrace);
        }

        if(!isset($backtrace[$offset])){
            $offset -= 1;
        }

        $_offset = $offset + 1;

        $call = $backtrace[$offset];

        if(empty($call['file'])){

            $_offset = $offset;

            $call = $backtrace[$offset-1];

        }

        if (isset($backtrace[$_offset])) {
            if (isset($backtrace[$_offset]['class'])) {
                $call['function'] = $backtrace[$_offset]['class'] . $backtrace[$_offset]['type'] . $backtrace[$_offset]['function'] . '()';
            } else {
                $call['function'] = $backtrace[$_offset]['function'] . '()';
            }
        } else {
            if (isset($backtrace[$offset]['class'])) {
                $call['function'] = $backtrace[$offset]['class'] . $backtrace[$offset]['type'] . $backtrace[$offset]['function'] . '()';
            } elseif(isset($backtrace[$offset]['function'])) {
                $call['function'] = $backtrace[$offset]['function'] . '()';
            } else {
                $call['function'] = '';
            }
        }

        $src = str_replace(cmsConfig::get('root_path'), '/', $call['file']).' => '.$call['line'].($call['function'] ? ' => '.$call['function'] : '');

        self::$points_data[$target][] = array_merge(array(
            'src'  => $src,
            'time' => $time
        ), ['info' => $params['info']]);

        return true;

    }

    public static function getPointsTargets() {

        $_targets = array_keys(self::$points_data);

        $targets = array();

        foreach ($_targets as $target) {
            $targets[$target] = array(
                'title' => string_lang('LANG_DEBUG_TAB_'.$target),
                'count' => count(self::$points_data[$target])
            );
        }

        return $targets;

    }

    public static function loadIncludedFiles() {

        $_files = get_included_files();

        foreach ($_files as $path) {
            self::$points_data['includes'][] = array(
                'src'  => str_replace(cmsConfig::get('root_path'), '/', $path),
                'time' => 0,
                'info' => ''
            );
        }

    }

    public static function getPointsData($target = '') {

        self::loadIncludedFiles();

        if($target && isset(self::$points_data[$target])){
            return self::$points_data[$target];
        }

        return self::$points_data;

    }

    public static function startTimer($target, $point_id) {
        self::$start_time[$target][$point_id] = microtime(true);
    }

    public static function workTimer($target, $point_id) {
        self::$work_time[$target][$point_id] = microtime(true);
    }

    public static function getTime($target, $decimals = self::DECIMALS, $point_id = 0) {

        if (!isset(self::$start_time[$target]) || $point_id == 0) { return 0; }

        if (!isset(self::$start_time[$target][$point_id])) { return 0; }

        return number_format((microtime(true) - self::$start_time[$target][$point_id]), $decimals);
    }

}
