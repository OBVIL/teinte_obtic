# Teinte, TEI shades, XSL pack

[teinte_xsl](https://github.com/oeuvres/teinte_xsl/) is an XSLT-1.0 pack to transform XML texts, from, and to, different formats. The pivot format is a subset of [TEI](https://tei-c.org/release/docs/tei-p5-docs/en/html/REF-ELEMENTS.html), an academic XML schema dedicated to all forms of texts. Some formats have a complex packaging (docx, epub…). Such agregations requires another programation language for file manipulation or zipping (php, java, python…), but some results are already possible with xslt only, see below for browser or command line.

![Teinte xsl graph](https://oeuvres.github.io/teinte/docs/teinte_xsl.png)

This pack is compatible with xsltproc and other XSLT engines. Tested with
 * PHP: [teinte_php](https://github.com/oeuvres/teinte_php)
 * Java: [alix](https://github.com/oeuvres/alix)
 * Python: a command line pilot for Python is planned, needs support [teinte_py](https://github.com/oeuvres/teinte_py)
 * Javascript: direct transformation in browser (see below)
 * Bash: (see below)

# Browser

All modern browsers have the xsltproc library embedded. This allows to provide direct transformation in the browser. See this [example XML file](https://oeuvres.github.io/teinte/examples/moliere_misanthrope.xml) under a web server, you should see a nicely formatted theatre play, even if it’s an [XML/TEI source](https://github.com/oeuvres/teinte/blob/main/examples/moliere_misanthrope.xml).

![Misanthrope](https://oeuvres.github.io/teinte/docs/screens//teinte_misanthrope.png)

The magic rely on the XML prolog of the file

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!-- Link to a transformation for the browser over https:// -->
<?xml-stylesheet type="text/xsl" href="https://oeuvres.github.io/teinte_xsl/tei_html.xsl"?>
```
This can be added to all TEI files to provide instant formatting online.

Offline, this feature is blocked for security reasons, see for example (image below) the result and the reason on the Google LLC proprietary browser. The  open source [Firefox](https://www.mozilla.org/fr/firefox/new/) browser offers a workaround to bypass this security setting (list below). This little manipulation is done for ever, is reversible, is a lot less risky than javascript through the web, but industry has more interests in JS than XSLT. For a TEI editor, this allows to see the results of its XML modifications by a simple reload in the browser.

![Google.LLC.Chrome fileuri](https://oeuvres.github.io/teinte/docs/screens/chrome_fileuri.png)

1. Firefox browser, in address bar, type `about:config`
2. accept security alert
3. search for the property: security.fileuri.strict_origin_policy
4. set to false

![Firefox fileuri](https://oeuvres.github.io/teinte/docs/screens/firefox_fileuri.png)

# Bash

Under a linux box, or with a [WSL linux](https://ubuntu.com/wsl) on Microsoft.Windows, install xsltproc, and tranform your files.

```bash
sudo apt install xsltproc
cd teinte_xsl
xsltproc tei2html.xsl tests/moliere_misanthrope.xml
```

