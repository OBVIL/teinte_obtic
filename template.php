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
    <div id="row">
      <div id="upload">
        <header>
          <div id="icons">
            <div class="format tei" title="TEI : texte XML (Text Encoding Initiative)"></div>

            <div class="format docx" title="DOCX : texte bureautique (LibreOffice, Microsoft.Word…)"></div>

            <div class="format epub" title="EPUB : livre électronique ouvert"></div>
            <!--
            <div class="todo format html" title="HTML : page internet"></div>
            -->

            <div class="format markdown" title="MarkDown : texte brut légèrement formaté"></div>

          </div>
        </header>
        <div id="dropzone" class="card">
          <h3>Votre fichier</h3>
          <output></output>
          <div class="bottom">
            <button>ou chercher sur votre disque…</button>
            <input type="file" hidden />
          </div>
        </div>
      </div>
      <div id="preview">
        <?php Route::main(); ?>
      </div>
      <div id="download">
        <header>
          <div id="icons">
            <div class="format tei" title="TEI : texte XML (Text Encoding Initiative)"></div>

            <div class="format docx" title="DOCX : texte bureautique (LibreOffice, Microsoft.Word…)"></div>
            
            <!--
            <div class="todo format epub" title="EPUB : livre électronique ouvert"></div>
            -->

            <div class="format html" title="HTML : page internet"></div>

            <div class="format markdown" title="MarkDown : texte brut légèrement formaté"></div>

          </div>
        </header>
        <div class="card inactive" id="downzone">
          <h3>Téléchargements</h3>
          <output id="exports"></output>
        </div>
      </div>
    </div>
    <footer id="footer">
      <div class="rule">
        <div class="monogram"></div>
      </div>
    </footer>
  </div>
  <script type="module" type="text/javascript" src="<?= Route::home_href() ?>obtic_teinte.js"> </script>
</body>

</html>