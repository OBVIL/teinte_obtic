<?php declare(strict_types=1);
/**
 * Part of Teinte https://github.com/oeuvres/teinte_php
 * Copyright (c) 2022 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Teinte\Format;

use DOMDocument, ErrorException;
use Oeuvres\Kit\{Filesys, Log, Parse, Xt};
use Oeuvres\Xsl\{Xpack};


/**
 * A wrapper around docx document for export to semantic formats.
 * For now, mostly tested on:
 *  — Abbyy Finereader docx export
 */
class Docx extends Zip
{
    /** Avoid multiple initialisation */
    static private bool $init = false;
    /** A search replace program */
    static protected ?array $preg;
    /** A user search replace program */
    protected ?array $user_preg;
    /** Store XML as a string, maybe reused */
    protected ?string $xml = null;
    /** DOM Document to process */
    protected ?DOMDocument $dom = null;
    /** Absolute file:/// path to an XML template  */
    protected ?string $tmpl = null;

    /**
     * Inialize static variables
     */
    static function init(): void
    {
        if (self::$init) return;
        parent::init();
        $pcre_tsv = Xpack::dir() . 'docx/teilike_pcre.tsv';
        self::$preg = Parse::pcre_tsv($pcre_tsv);

        self::$init = true;
    }



    function __construct()
    {
        // keep a special dom options
        $this->dom = new DOMDocument();
        $this->dom->substituteEntities = true;
        $this->dom->preserveWhiteSpace = true;
        $this->dom->formatOutput = false;
        $this->tmpl = Xpack::dir() . 'docx/default.xml';
        $this->tmpl = "file:///" . str_replace(DIRECTORY_SEPARATOR, "/", $this->tmpl);
    }

    function xml(): string
    {
        // a dom have been calculated and kept during process
        if ($this->xml === null && $this->dom !== null) {
            $this->xml = $this->dom->saveXML();
        }
        return $this->xml;
    }

    function dom(): DOMDocument
    {
        return $this->dom;
    }

    function tei(): void
    {
        $this->pkg();
        $this->teilike();
        $this->pcre();
        $this->tmpl();
    }

    /**
     * Get an XML concatenation of docx content
     */
    function pkg(): void
    {
        // should have been loaded here
        // concat XML files sxtracted, without XML prolog
        $this->xml = '<?xml version="1.0" encoding="UTF-8"?>
<pkg:package xmlns:pkg="http://schemas.microsoft.com/office/2006/xmlPackage">
';
        // list of entries 
        $entries = [
            // styles 
            'word/styles.xml' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml',
            // main content
            'word/document.xml' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml',
            // links target in main content
            'word/_rels/document.xml.rels' => 'application/vnd.openxmlformats-package.relationships+xml',
            // footnotes
            'word/footnotes.xml' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.footnotes+xml',
            // links in footnotes
            'word/_rels/footnotes.xml.rels' => 'application/vnd.openxmlformats-package.relationships+xml',
            // endnotes
            'word/endnotes.xml' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.endnotes+xml',
            // link in endnotes
            'word/_rels/endnotes.xml.rels' => 'application/vnd.openxmlformats-package.relationships+xml',
            // for lists numbering
            "word/numbering.xml" => "application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml",
        ];
        foreach($entries as $name => $type) {
            // check error here ?
            $content = $this->zip->getFromName($name);
            if ($content === false) {
                $m = "$name not found in docx " . $this->file;
                if ($name === 'word/document.xml') Log::error($m);
                // else Log::debug($m);
                continue;
            }
            // delete xml prolog
            $content = preg_replace(
                [
                    "/\s*<\?xml[^\?>]*\?>\s*/",
                    // no effects seen on performances
                    // '/<w:latentStyles.*?<\/w:latentStyles>/s',
                    // '/ w:rsidRPr="[^"]+"/',
                ],
                [
                    '',
                ],
                $content
            );
            $this->xml .= "
  <pkg:part pkg:contentType=\"$type\" pkg:name=\"/$name\">
    <pkg:xmlData>\n" . $content . "\n    </pkg:xmlData>
  </pkg:part>
";
        }
        // add custom style table here for tag mapping
        $content = file_get_contents(Xpack::dir() . 'docx/styles.xml');
        $content = preg_replace('/^.*<sheet/ms', '<sheet', $content);
        $this->xml .= "
        <pkg:part pkg:contentType=\"$type\" pkg:name=\"/teinte/styles.xml\">
          <pkg:xmlData>\n" . $content . "\n    </pkg:xmlData>
        </pkg:part>
      ";
        $this->xml .= "\n</pkg:package>\n";
    }

    /**
     * Build a lite TEI with some custom tags like <i> or <sc>, esier to clean
     * with regex
     */
    function teilike():void
    {
        // DO NOT indent, reuse dom object with right props
        Xt::loadXml($this->xml, $this->dom);
        $this->dom = Xt::transformToDoc(
            Xpack::dir() . 'docx/docx_teilike.xsl', 
            $this->dom,
        );
        // out that as xml for pcre
        $this->xml = Xt::transformToXml(
            Xpack::dir() . 'docx/divs.xsl', 
            $this->dom,
        );
    }

    /**
     * Clean XML with pcre regex
     */
    function pcre(): void
    {
        // clean xml oddities
        $this->xml = preg_replace(self::$preg[0], self::$preg[1], $this->xml);
        // custom patterns
        if (isset($this->user_preg)) {
            $this->xml = preg_replace($this->user_preg[0], $this->user_preg[1], $this->xml);
        }
    }

    /**
     * Clean teilike and apply template
     */
    function tmpl(): void
    {
        // xml should come from pcre transform
        Xt::loadXml($this->xml, $this->dom);
        // TEI regularisations and model fusion
        $this->dom = Xt::transformToDoc(
            Xpack::dir() . 'docx/tei_tmpl.xsl',
            $this->dom,
            array("template" => $this->tmpl)
        );
        // delete old xml
        $this->xml = null;
    }

    /**
     * Record a regex program to clean file
     */
    function user_pcre(string $pcre_file)
    {
        if (!is_file($pcre_file)) {
            Log::error("Docx > tei, user regex 404: $pcre_file");
            return;
        }
        Log::info("Docx > tei, user regex loading: $pcre_file");
        $this->user_preg = Parse::pcre_tsv($pcre_file);
    }

    /**
     * Recod an XML file as a TEI template
     */
    function user_template(string $xml_file)
    {
        if (!is_file($xml_file)) {
            Log::error("Docx > tei, user template 404: $xml_file");
            return;
        }
        $this->tmpl = "file:///" . str_replace(DIRECTORY_SEPARATOR, "/", $xml_file);
        Log::info("Docx > tei, user xml template: ". $this->tmpl);
    }




}

Docx::init();
