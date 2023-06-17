<?php declare(strict_types=1);

include_once(__DIR__ . '/vendor/autoload.php');

/** Routage */

use Psr\Log\{LogLevel};
use Oeuvres\Kit\{Route, I18n, Http, Log, LoggerWeb};

// debug routing
// Log::setLogger(new LoggerWeb(LogLevel::DEBUG));

// upload file
Route::post('/upload', __DIR__ . '/actions/upload.php', [], null);
// download transformed uploaded file
Route::get('/download', __DIR__ . '/actions/download.php', [], null);
// Run the daemon on the work directory
Route::route(
    '/working', // path to request
    __DIR__ . '/actions/working.php', // contents to include
    [], // parameters
    __DIR__ . '/tmpl_action.php' // template
);


// register the default template in which include content
Route::template(__DIR__ . '/tmpl.php');

// welcome page
Route::get('/', __DIR__ . '/pages/accueil.php');
// try if a local php page is available
Route::get('/(.*)', __DIR__ . '/pages/$1.php');
// or a local html page
Route::get('/(.*)', __DIR__ . '/pages/$1.html');



// Catch all, php or html
Route::route('/404', __DIR__ . '/pages/404.php');
Route::route('/404', __DIR__ . '/pages/404.html');
// No Route has worked
echo "Bad routage, 404.";
