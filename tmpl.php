<?php declare(strict_types=1);

include_once(__DIR__ . '/vendor/autoload.php');

use \Oeuvres\Kit\{Http, Route};

?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <link rel="preconnect" href="https://fonts.gstatic.com">
  <link href="https://fonts.googleapis.com/css2?family=Lato&amp;display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://oeuvres.github.io/teinte_theme/teinte.css" />
  <link rel="stylesheet" href="https://oeuvres.github.io/teinte_theme/teinte.tree.css" />
  <script src="https://oeuvres.github.io/teinte_theme/teinte.tree.js"></script>
  <link rel="stylesheet" href="<?= Route::home_href() ?>theme/obtic_teinte.css" />
  <title>ObTiC, Teinte, conversion de livres</title>
</head>

<body>
  <div id="win">
    <header id="header">
        <a class="logo" href="https://obtic.sorbonne-universite.fr">
          <img src="<?= Route::home_href() ?>theme/obtic_logo.svg" alt="ObTIC" />
        </a>
      <nav id="tabs">
        <?= Route::tab('', 'Accueil') ?>
        <?= Route::tab('contact', 'Contact') ?>
        <a target="_blank" id="github" href="https://github.com/OBVIL/teinte_obtic">Open Source</a>
      </nav>
    </header>
    <?= Route::main() ?>
    
    <footer id="footer">
      <div class="rule">
        <div class="monogram"></div>
      </div>
    </footer>
  </div>
  <script type="module" type="text/javascript" src="<?= Route::home_href() ?>obtic_teinte.js"> </script>
</body>

</html>