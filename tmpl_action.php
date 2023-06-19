<?php declare(strict_types=1);

include_once(__DIR__ . '/vendor/autoload.php');

use \Oeuvres\Kit\{Http, Route};

/** 
 * Minimum styling for an actoin in iframe
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// supposed required to output logging line by line
header( 'Content-type: text/html; charset=utf-8' );
// used without template
header('Content-Encoding: none');
// header('Content-Type: text/plain; charset=UTF-8');
ob_implicit_flush();
while (ob_get_level()) ob_end_clean(); 
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <link rel="preconnect" href="https://fonts.gstatic.com"/>
        <link href="https://fonts.googleapis.com/css2?family=Lato&amp;display=swap" rel="stylesheet"/>
        <link rel="stylesheet" href="<?= Route::home_href() ?>theme/obtic_teinte.css" />
        <style>
#log {
    padding: 50px; 
    position: relative;
}
#footline {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
}
#footline img {
    display: block;  
    margin-left: auto; 
    margin-right: auto;
    width: 200px;
}
        </style>
        <?= str_repeat(" ", 1024) ?>
    </head>
    <body class="action">
        <div id="log">
            <footer id="footline">
                <img width="200" src="<?= Route::home_href() ?>theme/line.svg"/>
            </footer>
            <script>
const gobot = setInterval(function() {
    location.hash = "#footline";
}, 500);
window.addEventListener("focus", 
    function() {clearInterval(gobot);}
);
            </script>
            <?= Route::main() ?>
        </div>
        <script>
clearInterval(gobot);
document.getElementById("footline").style.display = "none";
        </script>
    </body>
</html>