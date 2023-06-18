<?php declare(strict_types=1);

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use \Oeuvres\Kit\{Http, Route};

?>
<style>
#upload, #download {display:none;}
#preview {margin-left: auto; margin-right: auto;}
</style>
<article>
    <h1>Traitements par lots</h1>
    <p>L’administrateur de cette installation a posé des fichiers dans un dossier de travail avec son accès FTP. Cette page permet de les transformer dans tous les formats disponibles.</p>
    <form target="working" method="post" action="working" style="text-align: center; margin-bottom: 1rem; display: block;">
        <button type="submit">Rafraîchir</button>
        <input type="submit" name="force" value="Tout refaire"/>
    </form>
    <iframe name="working"
    src="working">
    </iframe>
</article>