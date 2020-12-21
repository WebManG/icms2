<?php

/**
 * Определяет и подключает PHP-файл содержащий указанный класс
 * @param string $_class_name
 * @param bool $debug_on Включение логирования для отладки
 * @return boolean
 */
function autoLoadCoreClass($_class_name, $debug_on = false){

    static $debug_enabled = false;

    if ($debug_enabled) {
        $point_id = cmsDebugging::pointStart('autoload');
    }

    $class_name = strtolower($_class_name);
    $class_file = false;

    if (strpos($class_name, 'cms') === 0) {
        $class_name = substr($class_name, 3);
        $class_file = 'system/core/' . $class_name . '.php';
    } else

    if (strpos($class_name, 'field') === 0) {
        $class_name = substr($class_name, 5);
        $class_file = 'system/fields/' . $class_name . '.php';
    } else

    if (strpos($class_name, 'model') === 0) {
        $controller = strtolower(
            preg_replace(
                ['/([A-Z]+)/', '/_([A-Z]+)([A-Z][a-z])/'],
                ['_$1', '_$1_$2'],
                lcfirst(substr($_class_name, 5))
            )
        );
        $class_file = 'system/controllers/' . $controller . '/model.php';
    }

    if (!$class_file) {

        if ($debug_enabled) {
            cmsDebugging::pointProcess('autoload', array(
                'info'       => 'Not a core, field or component model class: '.$_class_name,
                'name'       => $_class_name,
                'data'       => $class_file,
                'result'     => false,
                'error'      => 'Not a core, field or component model class'
            ), 0, $point_id);
        }

        return false;

    }

    if (!is_readable(PATH . '/' . $class_file)){
        throw new Exception($class_file);
    }

    $result = include_once PATH . '/' . $class_file;

    if ($debug_enabled) {
        cmsDebugging::pointProcess('autoload', array(
            'info'       => $_class_name.' => '.$class_file,
            'name'       => $_class_name,
            'data'       => $class_file,
            'result'     => $result
        ), 0, $point_id);
    }

    // Включаем отладку для всех следующих автозагрузок
    if ($debug_on) { $debug_enabled = true; }

    return true;

}
