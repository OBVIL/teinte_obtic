<?php declare(strict_types=1);

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use \Oeuvres\Kit\{Http, Route};

?>
<article>
    <h1>Perdu ?</h1>
    <p><a href="<?= Route::home_href() ?>">Revenir à l’accueil.</a></p>
</article>