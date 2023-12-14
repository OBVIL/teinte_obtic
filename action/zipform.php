<?php
$name = '';
if (isset($_COOKIE["teinte"])) {
    $cookie = json_decode($_COOKIE["teinte"], true);
    $name = $cookie['src_filename'];
}

?>
<form id="form" method="post" target="zipwork" 
    onsubmit="this.format = event.submitter.name;"
    style="text-align: center; margin: 1rem; display: block;"
    action="zipwork">
        <button type="submit" name="docx"  class="format docx" title="DOCX : texte bureautique (LibreOffice, Microsoft.Word…)"> </button>

        <button  type="submit" name="html" class="format html" title="HTML : page internet"> </<button>

        <button  type="submit" name="markdown" class="format markdown" title="MarkDown : texte brut légèrement formaté"> </button>

        <button  type="submit" name="tei" class="format tei" title="TEI : texte XML (Text Encoding Initiative)"> </button>
    </form>
    <iframe 
    id="zipwork"
    name="zipwork"
    src="about:blank"
    onload="
// innerHTML do not parse <script>
console.log('src = ' + this.src );
if (!this.src.endsWith('zipwork')) return;
const dropExports = document.getElementById('exports');
console.log('name = <?= $name ?>');
    ">
    </iframe>

<?php 
/*
const iframe = document.getElementById('zipwork');
iframe.addEventListener('load', function () {
    
    console.log('<?= print_r($_COOKIE["teinte"], true) ?>')

// if (!isset($_COOKIE["teinte"])) return;
$cookie = json_decode($_COOKIE["teinte"], true);
echo "console.log('{$cookie['src_filename']}')";
    ?>
    const dropExports = document.getElementById('exports');
    $name = pathinfo($src_zip, PATHINFO_FILENAME);
    const url = 'listing';
    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        table.innerHTML = this.responseText;
    };
    xhr.open('GET', url);
    xhr.send();
});
*/
?>