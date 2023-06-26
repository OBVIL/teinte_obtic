<?php declare(strict_types=1);

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use \Oeuvres\Kit\{Http, Route};

?>
<article id="lots">
    <h1>Traitements par lots</h1>
    <p>L’administrateur de cette installation a posé des fichiers dans un dossier de travail avec son accès FTP. Cette page permet de les transformer dans tous les formats disponibles.</p>
    <form id="form" target="working" method="post"
    action="working" style="text-align: center; margin-bottom: 1rem; display: block;">
        <button id="refresh" type="submit">Rafraîchir</button>
        <input type="submit" name="force" value="Tout refaire"/>
    </form>
    <iframe 
    id="working"
    name="working"
    src="working">
    </iframe>
    <div id="table">

    </div>
    <script>
const table = document.getElementById('table');
const working = document.getElementById('working');
working.addEventListener('load', function () {
    const url = 'listing';
    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        table.innerHTML = this.responseText;
    };
    xhr.open('GET', url);
    xhr.send(); 
});
const form = document.getElementById('form');
form.addEventListener('submit', function () {
    table.innerHTML = '';
});

    </script>
</article>