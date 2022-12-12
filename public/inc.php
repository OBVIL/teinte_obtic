<?php declare(strict_types=1);

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use Oeuvres\Kit\{I18n};

I18n::load(__DIR__ . "/messages.tsv");
// get workdir from params
$config = [];
$config_file = dirname(__DIR__).'/config.php';
if (is_readable($config_file)) {
    $config = require($config_file);
}
if (!isset($pars['workdir']) || !$pars['workdir']) {
    $config['workdir'] = sys_get_temp_dir();
} else {
    $config['workdir'] = rtrim($config['workdir'], '\/') . '/'; 
}
// cookie name
const TEINTE = "teinte";
