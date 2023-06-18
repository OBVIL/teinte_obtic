<?php declare(strict_types=1);

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use Oeuvres\Kit\{I18n};

I18n::load(__DIR__ . "/messages.tsv");
const TEMP_DIR = "temp_dir";
// get workdir from params
$config = [];
$config_file = dirname(__DIR__).'/config.php';
if (is_readable($config_file)) {
    $config = require($config_file);
}
if (!isset($pars[TEMP_DIR]) || !$pars[TEMP_DIR]) {
    $config[TEMP_DIR] = sys_get_temp_dir();
}
$config[TEMP_DIR] = rtrim($config[TEMP_DIR], '\/') . '/';
// cookie name
const TEINTE = "teinte";
