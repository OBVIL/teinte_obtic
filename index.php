<?php declare(strict_types=1);

include_once(__DIR__ . '/vendor/autoload.php');

/** Routage */

use Oeuvres\Kit\{Route, I18n, Http};

// upload file, without pars and template
Route::post('/upload', __DIR__ . '/code/upload.php', [], null);
// download file, without pars and template
Route::get('/download', __DIR__ . '/code/download.php', [], null);

// register the template in which include content
Route::template(__DIR__ . '/template.php');

// welcome page
Route::get('/', __DIR__ . '/pages/presentation.html');
// try if a local php page is available
Route::get('/(.*)', __DIR__ . '/pages/$1.php');
// or a local html page
Route::get('/(.*)', __DIR__ . '/pages/$1.html');



// Catch all, php or html
Route::route('/404', __DIR__ . '/pages/404.php');
Route::route('/404', __DIR__ . '/pages/404.html');
// No Route has worked
echo "Bad routage, 404.";
