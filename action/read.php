<?php 
include_once(__DIR__ . '/inc.php');
use Oeuvres\Kit\{Config, Http, I18n, Log};

// nothing has been uploaded
if (!isset($_COOKIE[TEINTE])) {
    attach("Teinte, " . I18n::_("ERROR_NO_UPLOAD") . '.txt');
    Log::error(I18n::_('download.nofile'));
    print_r($_COOKIE);
    exit();
}
$cookie = json_decode($_COOKIE[TEINTE], true);
$zip_file = Config::get(TEMP_DIR) . $cookie['id'] .'/' . Http::par('zip_file');
Http::readfile($zip_file);