const home_href = '';
const ext2format = {
    "docx": "docx",
    "epub": "epub",
    "htm": "html",
    "html": "html",
    "markdown": "markdown",
    "md": "markdown",
    "tei": "tei",
    "txt": "markdown",
    "xhtml": "html",
    "xml": "tei"
};
const formats = {
    "docx": {
        "ext":".docx",
        "mime":"application/vnd.openxmlformats-officedocument.wordprocessingml.document"
    },
    "epub": {
        "ext":".epub",
        "mime":"application/epub+zip"
    },
    "html": {
        "ext":".html",
        "mime":"text/html; charset=utf-8"
    },
    "markdown": {
        "ext":".md",
        "mime":"text/plain; charset=utf-8"
    },
    "md": {
        "ext":".md",
        "mime":"text/plain; charset=utf-8"
    },
    "tei": {
        "ext":".xml",
        "mime":"text/xml"
    }
};
const conversions = {
    "docx": ["tei", "html", "markdown"],
    "tei": ["docx", "html", "markdown"],
    "docx": ["tei", "html", "markdown"],
    "epub": ["tei", "docx", "html", "markdown"],
    "markdown": ["tei", "docx", "html"],
}



function dropInit() {
    const dropZone = document.querySelector("#dropzone");
    if (!dropZone) return;
    const dropOutput = dropZone.querySelector("output");
    const dropBut = dropZone.querySelector("button");
    const dropInput = dropZone.querySelector("input");
    const dropPreview = document.getElementById('preview');
    const dropDownzone = document.getElementById('downzone');
    const dropExports = document.getElementById('exports');

    const message = {
        "default": "Déposer ici votre fichier",
        "over": "<big>Lâcher pour téléverser</big>",
    }
    // shared variable
    let file;
    let format;
    if (dropOutput) {
        dropOutput.innerHTML = message['default'];
    }
    if (dropBut) {
        dropBut.onclick = () => {
            dropInput.click(); //if user click on the button then the input also clicked
        }
    }
    function dropFocus() {
        dropZone.classList.remove("inactive");
        dropZone.classList.add("active");
        dropPreview.classList.remove("active");
        dropPreview.classList.add("inactive");
        dropDownzone.classList.remove("active");
        dropDownzone.classList.add("inactive");
    }
    dropZone.onmousedown = () => {
        dropFocus();
        dropInput.click(); 
    }
    if (dropInput) {
        dropInput.addEventListener("change", function () {
            //getting user select file and [0] this means if user select multiple files then we'll select only the first one
            file = this.files[0];
            dropFocus();
            showFile(); //calling function
        });
    }
    //If user Drag File Over DropArea
    dropZone.addEventListener("dragover", (event) => {
        event.preventDefault(); //preventing from default behaviour
        dropFocus();
        dropOutput.innerHTML = message['over'];
    });
    //If user leave dragged File from DropArea
    dropZone.addEventListener("dragleave", () => {
        dropZone.classList.remove("inactive");
        dropZone.classList.remove("active");
        dropOutput.innerHTML = message['default'];
    });
    //If user drop File on DropArea
    dropZone.addEventListener("drop", (event) => {
        event.preventDefault(); //preventing from default behaviour
        //getting user select file and [0] this means if user select multiple files then we'll select only the first one
        file = event.dataTransfer.files[0];
        showFile(); //calling function
    });

    function showFile() {
        // user interrupt ?
        if (!file || !file.name) return;
        dropZone.classList.add("inactive");
        let ext = file.name.split('.').pop();
        format = ext2format[ext];
        if (!format) format = ext;
        if (!(format in conversions)) {
            dropOutput.innerHTML = '<b>“' + format + '” format<br/>is not  supported</b><br/>' + file.name;
            return;
        }
        dropOutput.innerHTML = '<div class="filename">' + file.name + '</div>' 
        + '<div class="format ' + format + '"></div>';
        upload();
    }
    async function upload() {
        dropPreview.classList.remove("inactive");
        dropPreview.innerHTML = '<p class="center">Fichier en cours de traitement… (jusqu’à plusieurs secondes selon le format et la taille du fichier)</p>'
        + '<img width="80%" class="waiting" src="theme/waiting.svg"/>';
        let timeStart = Date.now();
        let formData = new FormData();
        formData.append("file", file);
        // url is resolved from the script url
        fetch('upload', {
            method: "POST",
            body: formData
        }).then((response) => {
            if (response.ok) {
                let downs = conversions[format];
                dropDownzone.classList.remove("inactive");
                dropDownzone.classList.add("active");
                let html = "";
                const name = file.name.replace(/\.[^/.]+$/, "");
                for (let i = 0, length = downs.length; i < length; i++) {
                    const format2 = downs[i];
                    if (!formats[format2]) continue;
                    let ext = formats[format2].ext;
                    html += '\n<a class="download format ' + format2 +'" href="download?format=' + format2 + '">' 
                    + '<div class="filename">' + name + ext + '</div>'
                    + '</a>';
                }
                dropExports.innerHTML = html;
                dropPreview.classList.add("active");
                return response.text();    
            }
            else if (response.status = 404) {
                return "Erreur de développement, la page de téléchargement n’a pas été trouvée."
            }
            else {
                return `HTTP error ${response.status}`;
            }

        }).then((html) => {
            dropPreview.innerHTML = html;
            Tree.load();
        });
    }
}
dropInit();

