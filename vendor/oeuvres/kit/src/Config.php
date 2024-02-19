<?php
/**
 * Part of Teinte https://github.com/oeuvres/teinte
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

declare(strict_types=1);

namespace Oeuvres\Kit;

/**
 * A light system to handle Configuration parameters
 * as a singleton
 */
class Config
{
    /** The initialisation variables */
    static private $config=[];
    /** Avoid multiple initialisation */
    static private $init = false;
    /**
     * Inialize static vars
     */
    static function init():void
    {
        if (self::$init) return;
        // getcwd is quite robust on most server, where is index.php
        $config_file = getcwd() . DIRECTORY_SEPARATOR .'/config.php';
        if (is_readable($config_file)) {
            self::load($config_file);
        }
    }

    static public function load($config_file = '')
    {
        $config = require($config_file);
        self::$config = array_merge(self::$config, $config);
    }

    static public function get($name, $default=null)
    {
        if (isset(self::$config[$name])) {
            return self::$config[$name];
        }
        return $default;
    }
    static public function set($name, $value)
    {
        $ret = null;
        if (isset(self::$config[$name])) $ret = self::$config[$name];
        return self::$config[$name] = $value;
        return $ret;
    }
}

Config::init();
