<?php declare(strict_types=1);
/**
 * Part of Teinte https://github.com/oeuvres/teinte_php
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Teinte\Tei2;

use Exception, DOMDocument, DOMNode, ZipArchive;
use Oeuvres\Teinte\Format\{Zip};
use Oeuvres\Kit\{Check, Log, Filesys, Xt};
use Oeuvres\Xsl\{Xpack};
Check::extension('zip');

/**
 * Output a MS.Word docx document from TEI.
 * code convention https://www.php-fig.org/psr/psr-12/
 */
class Tei2docx extends AbstractTei2
{
    const EXT = '.docx';
    const NAME = "docx";


    /** Some mapping between 3 char iso languange code to 2 char */
    const ISO639_3char = [
        'eng' => 'en',
        'fra' => 'fr',
        'lat' => 'la',    
    ];

    public static function init()
    {
        parent::init();
    }


    /**
     * Return a configured template or default
     */
    static private function template(?array $pars=null):string
    {
        if ($pars && isset($pars['template.docx'])) {
            return $pars['template.docx'];
        }
        return Xpack::dir() . '/tei_docx/template.docx';
    }

    /**
     * @ override
     */
    static function toDOM(DOMDocument $dom, ?array $pars=null):?\DOMDocument
    {
        Log::error(__METHOD__." dom export not relevant");
        return null;
    }
    /**
     * @ override
     */
    static function toXML(DOMDocument $dom, ?array $pars=null):?string
    {
        Log::error(__METHOD__." xml export not relevant");
        return null;
    }

    /**
     * Include images files pointed by <graphic url=""/>
     */
    private static function images(DOMDocument $dom, Zip $zip)
    {
        $dom_dir = null;
        if ($dom->documentURI && $dom->documentURI !== getcwd()) {
            $dom_dir = dirname($dom->documentURI);
        }
        $count = 1;
        $nl = $dom->getElementsByTagNameNS('http://www.tei-c.org/ns/1.0', 'graphic');
        if (!$nl->count()) return;
        $pad = strlen('' . $nl->count());
        $found = false;
        $toDel = [];
        // be careful, NodeList is living, do not delete here
        foreach ($nl as $el) {
            $att = $el->getAttributeNode("url");
            if (!isset($att) || !$att || !$att->value) {
                continue;
            }
            $url = $att->value;
            // broken link from word, be nice
            if (substr( $url, 0, 6 ) === "zip://" 
            && substr( $url, -6 ) === "#word/") {
                $toDel[] = $el;
                continue;
            }
            $data = Filesys::loadURL($url, $dom_dir);
            if (!$data) {
                // a log message given upper
                continue;
            }
            $image_path = "media/image_" 
                . str_pad(strval($count), $pad, '0', STR_PAD_LEFT)
                . '.' . $data['ext'];
            if ($zip->put('word/' . $image_path, $data['bytes']) === false) {
                // write failed
                continue;
            }
            // change attribute value
            $el->setAttribute("url", $image_path);
            $count++;
        }
        // outside living nodeList
        if (($count = count($toDel))) {
            log::warning("$count image broken links zip://…#word/");
        }
        foreach ($toDel as $key => $el) {
            $text = $el->ownerDocument->createTextNode("[?]");
            $parent = $el->parentNode;
            if($parent->tagName == 'figure') {
                $el = $parent;
                $parent = $el->parentNode;
            }
            $parent->insertBefore($text, $el);
            $parent->removeChild($el);
        }
    }

    /**
     * @ override
     */
    static function toURI($dom, $dst_file, ?array $pars=null)
    {
        Log::debug("Tei2" . static::NAME ." $dst_file");
        $template = self::template($pars);
        if (!Filesys::readable($template)) {
            throw new Exception("“{$template}” not readble as a template file");
        }
        if (!Filesys::copy($template, $dst_file)) {
            throw new Exception("“{$dst_file}” not writable. May be this destination is open in Ms.Word\n" . Log::last());
        }
        // documentURI works for xml loader from file
        // $name = pathinfo($dom->documentURI, PATHINFO_FILENAME);
        $zip = new Zip();
        if ($zip->open($dst_file) !== TRUE) {
            return false;
        }
        // import images before transforming tei, to have good paths
        self::images($dom, $zip);

        // get a default lang from the source TEI, set it in the style.xml
        $xpath = Xt::xpath($dom);
        $entries = $xpath->query("/*/@xml:lang");
        $lang = null;
        foreach ($entries as $node) {
            $lang = $node->value;
        }
        // template is supposed to have a default language
        if ($lang) {
            if (isset(self::ISO639_3char[$lang])) $lang = self::ISO639_3char[$lang];
            $name = 'word/styles.xml';
            $xml = $zip->get($name);
            $xml = preg_replace(
                '@<w:lang[^>]*/>@', 
                '<w:lang w:val="'. $lang . '"/>', 
                $xml
            );
            $zip->put($name, $xml);
        }


        $re_clean = array(
            '@(<w:rPr/>|<w:pPr/>)@' => '',
        );

        // extract template to 
        $templPath = tempnam(sys_get_temp_dir(), "teinte_docx_");
        $templPath = "file:///" 
            . str_replace(DIRECTORY_SEPARATOR, "/", $templPath);
        // $this->logger->debug(__METHOD__.' $templPath='.$templPath);

        /* No more support for comments
        $xml = Xt::transformToXml(
            Xpack::dir() . '/tei_docx/tei_docx_comments.xsl', 
            $dom,
        );
        $zip->addFromString('word/comments.xml', $xml);
        */

        // template may not contain images
        // Word is confused without image file types
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="xml" ContentType="application/xml"/>
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <!-- Needed for images -->
    <Default Extension="emf" ContentType="image/x-emf"/>
    <Default Extension="jpeg" ContentType="image/jpeg"/>
    <Default Extension="jpg" ContentType="image/jpeg"/>
    <Default Extension="png" ContentType="image/png"/>
    <Default Extension="tif" ContentType="image/tif"/>
    <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
    <Override PartName="/word/_rels/document.xml.rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
    <Override PartName="/word/endnotes.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.endnotes+xml"/>
    <Override PartName="/word/fontTable.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.fontTable+xml"/>
    <Override PartName="/word/footnotes.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footnotes+xml"/>
    <Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/>
    <Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>
    <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
    <Override PartName="/word/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>
    <Override PartName="/word/webSettings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.webSettings+xml"/>
</Types>
        ';
        $zip->put('[Content_Types].xml', $xml);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:zoom w:percent="100"/>
    <w:defaultTabStop w:val="708"/>
    <w:autoHyphenation w:val="true"/>
    <w:footnotePr>
    <w:numFmt w:val="decimal"/>
        <w:footnote w:id="0"/>
        <w:footnote w:id="1"/>
    </w:footnotePr>
    <w:compat>
        <w:doNotExpandShiftReturn/>
        <w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="12"/>
        <w:compatSetting w:name="overrideTableStyleFontSizeAndJustification" w:uri="http://schemas.microsoft.com/office/word" w:val="1"/>
        <w:compatSetting w:name="enableOpenTypeFeatures" w:uri="http://schemas.microsoft.com/office/word" w:val="1"/>
        <w:compatSetting w:name="doNotFlipMirrorIndents" w:uri="http://schemas.microsoft.com/office/word" w:val="1"/>
        <w:compatSetting w:name="differentiateMultirowTableHeaders" w:uri="http://schemas.microsoft.com/office/word" w:val="1"/>
    </w:compat>
    <w:themeFontLang w:val="fr-FR" w:eastAsia="" w:bidi=""/>
</w:settings>
';
        // $zip->put('word/settings.xml', $xml);
        // generation of word/document.xml needs some links
        // from template, especially for head and foot page.
        file_put_contents($templPath, $zip->get('word/document.xml'));
        $xml = Xt::transformToXml(
            Xpack::dir() . '/tei_docx/tei_docx.xsl',
            $dom,
            array(
                'templPath' => $templPath,
            )
        );
        $xml = preg_replace(
            array_keys($re_clean), 
            array_values($re_clean), 
            $xml
        );
        $zip->put('word/document.xml', $xml);
        // keep some relations in footnotes from template
        file_put_contents(
            $templPath, 
            $zip->get('word/_rels/document.xml.rels')
        );
        $xml = Xt::transformToXml(
            Xpack::dir() . '/tei_docx/tei_docx_rels.xsl',
            $dom,
            array(
                'templPath' => $templPath,
            )
        );
        $zip->put('word/_rels/document.xml.rels', $xml);


        $xml = Xt::transformToXml(
            Xpack::dir() . '/tei_docx/tei_docx_fn.xsl',
            $dom,
        );
        $xml = preg_replace(
            array_keys($re_clean), 
            array_values($re_clean), 
            $xml
        );
        $zip->put('word/footnotes.xml', $xml);


        $xml = Xt::transformToXml(
            Xpack::dir() . '/tei_docx/tei_docx_fnrels.xsl',
            $dom,
        );
        $zip->put('word/_rels/footnotes.xml.rels', $xml);
        // 
        if (!$zip->close()) {
            Log::error(
                "Tei2docx ERROR writing \033[91m$dst_file\033[0m\nMaybe an app has an handle on this file. Is this docx open in MS.Word or LibreO.Writer?"
            );
        }
    }

}

Tei2docx::init();