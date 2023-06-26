<?php declare(strict_types=1);

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use Oeuvres\Kit\{Config, I18n};

I18n::load(__DIR__ . "/messages.tsv");
const TEMP_DIR = "temp_dir";
// get workdir from params
if (!Config::get(TEMP_DIR)) {
    Config::set(TEMP_DIR, sys_get_temp_dir());;
}
Config::set(TEMP_DIR, rtrim(Config::get(TEMP_DIR), '\/') . '/');
// cookie name
const TEINTE = "teinte";
