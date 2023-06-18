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
    use Teiable;
    /** Avoid multiple initialisation */
    static private bool $init = false;
    /** A search replace program */
    static protected ?array $preg;
    /** A user search replace program */
    protected ?array $user_preg;
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

    /**
     * Load and check
     */
    public function load(string $file): bool
    {
        $this->teiReset();
        if (!parent::load($file)) {
            return false;
        }
        return true;
    }

    function __construct()
    {
        $this->tmpl = Xpack::dir() . 'docx/default.xml';
        $this->tmpl = "file:///" . str_replace(DIRECTORY_SEPARATOR, "/", $this->tmpl);
    }

    
    /**
     * Give a dom with right options
     */
    private function newDom(): DOMDocument
    {
        // keep a special dom options
        $dom = new DOMDocument();
        $dom->substituteEntities = true;
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        return $dom;
    }



    /**
     * Transform docx as a tei that we can get by dom()
     */
    function teiMake(?array $pars = null): void
    {
        $this->pkg();
        $this->teilike($pars);
        $this->pcre();
        $this->tmpl($pars);
    }

    /**
     * Get an XML concatenation of docx content
     */
    function pkg(): void
    {
        // should have been loaded here
        // concat XML files sxtracted, without XML prolog
        $this->tei = '<?xml version="1.0" encoding="UTF-8"?>
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
            $this->tei .= "
  <pkg:part pkg:contentType=\"$type\" pkg:name=\"/$name\">
    <pkg:xmlData>\n" . $content . "\n    </pkg:xmlData>
  </pkg:part>
";
        }
        // add custom style table here for tag mapping
        $content = file_get_contents(Xpack::dir() . 'docx/styles.xml');
        $content = preg_replace('/^.*<sheet/ms', '<sheet', $content);
        $this->tei .= "
        <pkg:part pkg:contentType=\"$type\" pkg:name=\"/teinte/styles.xml\">
          <pkg:xmlData>\n" . $content . "\n    </pkg:xmlData>
        </pkg:part>
      ";
        $this->tei .= "\n</pkg:package>\n";
    }

    /**
     * Build a lite TEI with some custom tags like <i> or <sc>, easier to clean
     * with regex
     */
    function teilike(?array $pars = null):void
    {
        // a local dom with right properties
        $dom = $this->newDom();
        // DO NOT indent here
        Xt::loadXml($this->tei, $dom);
        $dom = Xt::transformToDoc(
            Xpack::dir() . 'docx/docx_teilike.xsl', 
            $dom,
            $pars
        );
        // out that as xml for pcre
        $this->tei = Xt::transformToXml(
            Xpack::dir() . 'docx/divs.xsl', 
            $dom,
        );
    }

    /**
     * Clean XML with pcre regex
     */
    function pcre(): void
    {
        // clean xml oddities
        $this->tei = preg_replace(self::$preg[0], self::$preg[1], $this->tei);
        // custom patterns
        if (isset($this->user_preg)) {
            $this->tei = preg_replace($this->user_preg[0], $this->user_preg[1], $this->tei);
        }
    }

    /**
     * Clean teilike and apply template
     */
    function tmpl(?array $pars = null): void
    {
        // a local dom with right properties
        $dom = $this->newDom();
        // xml should come from pcre transform
        Xt::loadXml($this->tei, $dom);
        if (!isset($pars["template" ])) {
            $pars["template" ] = $this->tmpl;
        }
        // TEI regularisations and model fusion
        $dom = Xt::transformToDoc(
            Xpack::dir() . 'docx/tei_tmpl.xsl',
            $dom,
            $pars
        );
        // delete old xml
        $this->tei = null;
        // set new dom
        $this->teiDoc = $dom;
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



    /**
     * TODO, extract image from docx
     * Extract iamges from  <graphic> elements from a DOM doc, copy linked images in a flat dstdir
     * copy linked images in an images folder $dstdir, and modify relative link
     *
     * $hrefdir : a href prefix to redirest generated links
     * $dstdir : a folder if images should be copied
     * return : a doc with updated links to image
     */
    public function images($dst_tei, $href_prefix)
    {
        $dom = $this->dom;
        $count = 1;
        $nl = $dom->getElementsByTagNameNS('http://www.tei-c.org/ns/1.0', 'graphic');
        // $pad = strlen('' . $nl->count());
        foreach ($nl as $el) {
            $att = $el->getAttributeNode("url");
            if (!isset($att) || !$att || !$att->value) {
                continue;
            }
            $src = $att->value;
            // do not modify data image
            if (strpos($src, 'data:image') === 0) {
                continue;
            }
            // test if coming fron the internet
            if (substr($src, 0, 4) == 'http') {
                continue;
            }
            // should be an image comming from the docx
            $no = str_pad(strval($count), 3, '0', STR_PAD_LEFT);
            $ext = pathinfo($src, PATHINFO_EXTENSION);
            $dir = dirname($dst_tei) . "/";
            if ($dir == '/')  {
                $dir = '';
            }
            $href = $href_prefix . $no . '.' . $ext;
            $dst_file =  $dir . $href;
            Filesys::mkdir(dirname($dst_file));
            Log::info($src . " -> " . $dst_file);
            $contents = $this->zip->getFromName('word/' . $src);
            file_put_contents($dst_file, $contents);
            $att->value = $href;
            // $this->img($el->getAttributeNode("url"), , $hrefdir, $dstdir);
            $count++;
        }
        /*
    do not store images of pages, especially in tif
    foreach ($doc->getElementsByTagNameNS('http://www.tei-c.org/ns/1.0', 'pb') as $el) {
      $this->img($el->getAttributeNode("facs"), $hrefTei, $dstdir, $hrefSqlite);
    }
    */
        // return $dom;
    }



}

Docx::init();
