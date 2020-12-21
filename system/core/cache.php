<?php
class cmsCache {

    private static $instance;

    private $cacher;
    private $cache_ttl;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public static function getCacher($config) {

        $cacher_class = 'cmsCache' . string_to_camel('_', $config->cache_method);

        return new $cacher_class($config);

    }

    public function __construct() {

        $config = cmsConfig::getInstance();

        if ($config->cache_enabled) {

            $this->cacher = self::getCacher($config);

            $this->cache_ttl = $config->cache_ttl;

        }

    }

    public function __call($method_name, $arguments) {

        // кеширование отключено
        if(!isset($this->cacher)){
            return false;
        }

        // есть метод здесь, вызываем его
        if(method_exists($this, '_'.$method_name)){
            return call_user_func_array(array($this, '_'.$method_name), $arguments);
        }
        // есть метод в кешере, вызываем его
        if(method_exists($this->cacher, $method_name)){
            return call_user_func_array(array($this->cacher, $method_name), $arguments);
        }
        // ничего нет
        trigger_error('not defined method name '.$method_name, E_USER_NOTICE);

        return false;

    }

    private function _set($key, $value, $ttl=false){

        $debug_enabled = cmsConfig::getInstance()->debug;

        if ($debug_enabled) {
            $point_id = cmsDebugging::pointStart('cache');
        }

        if (!$ttl) { $ttl = $this->cache_ttl; }

        $result = $this->cacher->set($key, $value, $ttl);

        if ($debug_enabled) {
            cmsDebugging::pointProcess('cache', array(
                'info'   => 'set => '.$key,
                'name'   => $key,
                'action' => 'set',
                'data'   => $value,
                'result' => $result
            ), 5, $point_id);
        }

        return $result;

    }

    private function _get($key){

        $debug_enabled = cmsConfig::getInstance()->debug;

        if ($debug_enabled) {
            $point_id = cmsDebugging::pointStart('cache');
        }

        if (!$this->cacher->has($key)) {

            if ($debug_enabled) {
                cmsDebugging::pointProcess('cache', array(
                    'info'   => 'get => Key \''.$key.'\' not found',
                    'name'   => $key,
                    'action' => 'get',
                    'result' => false,
                    'error'  => 'Key not found'
                ), 5, $point_id);
            }

            return false;

        }

        $value = $this->cacher->get($key);

        if ($debug_enabled) {
            cmsDebugging::pointProcess('cache', array(
                'info'   => 'get => '.$key,
                'name'   => $key,
                'action' => 'get',
                'result' => $value
            ), 5, $point_id);
        }

        return $value;

    }

}
