<?php declare(strict_types=1);

namespace Oeuvres\Teinte;

include_once(dirname(__DIR__) . '/vendor/autoload.php');
?>
<div id="row">
    <div id="upload">
        <header>
            <div id="icons">
                <div class="format docx" title="DOCX : texte bureautique (LibreOffice, Microsoft.Word…)"></div>
                <div class="format epub" title="EPUB : livre électronique ouvert"></div>
                <!--
                <div class="todo format html" title="HTML : page internet"></div>
                -->
                <div class="format markdown" title="MarkDown : texte brut légèrement formaté"></div>
                <div class="format tei" title="TEI : texte XML (Text Encoding Initiative)"></div>
                <div class="format zip" title="ZIP : livres encodés en plusieurs formats"></div>
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
    <?php // inclure texte de présentation
    include_once(__DIR__ . '/presentation.html');
     ?>
    </div>
    <div id="download">
    <header>
        <div id="icons">

        <div class="format docx" title="DOCX : texte bureautique (LibreOffice, Microsoft.Word…)"></div>
        
        <!--
        <div class="todo format epub" title="EPUB : livre électronique ouvert"></div>
        -->

        <div class="format html" title="HTML : page internet"></div>

        <div class="format markdown" title="MarkDown : texte brut légèrement formaté"></div>
        <div class="format tei" title="TEI : texte XML (Text Encoding Initiative)"></div>

        </div>
    </header>
    <div class="card inactive" id="downzone">
        <h3>Téléchargements</h3>
        <output id="exports"></output>
    </div>
    </div>
</div>
