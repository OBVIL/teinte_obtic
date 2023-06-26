<form id="form" method="post" target="zipwork" 
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
    src="">
    </iframe>
    <script>
const table = document.getElementById('table');
const working = document.getElementById('zipwork');
working.addEventListener('load', function () {
    /*
    const url = 'listing';
    const xhr = new XMLHttpRequest();
    xhr.onload = function () {
        table.innerHTML = this.responseText;
    };
    xhr.open('GET', url);
    xhr.send();
    */ 
});

    </script>