<?php declare(strict_types=1);

include_once(__DIR__ . '/vendor/autoload.php');

use \Oeuvres\Kit\{Http, Route};

/** 
 * Minimum styling for an actoin in iframe
 */
?>
<!DOCTYPE html>
<html>
    <head>
        <link rel="preconnect" href="https://fonts.gstatic.com"/>
        <link href="https://fonts.googleapis.com/css2?family=Lato&amp;display=swap" rel="stylesheet"/>
        <link rel="stylesheet" href="<?= Route::home_href() ?>theme/obtic_teinte.css" />
    </head>
    <body>
        <?= Route::main() ?>
    </body>
</html>