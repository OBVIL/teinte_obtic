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
    public function open(string $file, ?int $flags = 0): bool
    {
        $this->teiReset();
        if (!parent::open($file)) {
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
     * Give a DOM with right options
     */
    private function newDOM(): DOMDocument
    {
        // keep a special DOM options
        $DOM = new DOMDocument();
        $DOM->substituteEntities = true;
        $DOM->preserveWhiteSpace = true;
        $DOM->formatOutput = false;
        return $DOM;
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
        $this->teiXML = '<?xml version="1.0" encoding="UTF-8"?>
<pkg:package xmlns:pkg="http://schemas.microsoft.com/office/2006/xmlPackage">
';
        // list of entries from the docx to concat
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
            $this->teiXML .= "
  <pkg:part pkg:contentType=\"$type\" pkg:name=\"/$name\">
    <pkg:xmlData>\n" . $content . "\n    </pkg:xmlData>
  </pkg:part>
";
        }
        // add file contents
        foreach ([
            'docx/styles.xml' => 'application/teinte.styles',
            'docx/symbol.xml' => 'application/teinte.charmap',

        ] as $name => $type) {
            // clean prolog
            $dom = new DOMDocument();
            $dom->load(Xpack::dir() . $name);
            $this->teiXML .= "
            <pkg:part pkg:contentType=\"$type\" pkg:name=\"$name\">
              <pkg:xmlData>\n" . $dom->saveXML($dom->documentElement) . "\n    </pkg:xmlData>
            </pkg:part>
          ";
        }
        $this->teiXML .= "\n</pkg:package>\n";
    }

    /**
     * Build a lite TEI with some custom tags like <i> or <sc>, easier to clean
     * with regex
     */
    function teilike(?array $pars = []):void
    {
        $pars['file'] = $this->file;
        // a local DOM with right properties
        $DOM = $this->newDOM();
        // DO NOT indent here
        Xt::loadXML($this->teiXML, $DOM);
        $DOM = Xt::transformToDOM(
            Xpack::dir() . 'docx/docx_teilike.xsl', 
            $DOM,
            $pars
        );
        // out that as xml for pcre
        $this->teiXML = Xt::transformToXML(
            Xpack::dir() . 'docx/divs.xsl', 
            $DOM,
        );
    }

    /**
     * Clean XML with pcre regex
     */
    function pcre(): void
    {
        // clean xml oddities
        $this->teiXML = preg_replace(self::$preg[0], self::$preg[1], $this->teiXML);
        // custom patterns
        if (isset($this->user_preg)) {
            $this->teiXML = preg_replace($this->user_preg[0], $this->user_preg[1], $this->teiXML);
        }
    }

    /**
     * Clean teilike and apply template
     */
    function tmpl(?array $pars = null): void
    {
        // a local DOM with right properties
        $DOM = $this->newDOM();
        // xml should come from pcre transform
        Xt::loadXML($this->teiXML, $DOM);
        if (!isset($pars["template" ])) {
            $pars["template" ] = $this->tmpl;
        }
        // TEI regularisations and model fusion
        $DOM = Xt::transformToDOM(
            Xpack::dir() . 'docx/tei_tmpl.xsl',
            $DOM,
            $pars
        );
        // delete old xml
        $this->teiXML = null;
        // set new DOM
        $this->teiDOM = $DOM;
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
     * Get properies of document
     */
    public function properties()
    {
        $props = [ // =>null == !isset
            'dcterms:created' => 0,
            'dcterms:modified' => 0,
            'TotalTime' => 0,
            'media' => 0,
            // app.xml seems poorly updated
            // 'Pages' => 0,
            // 'Paragraphs' => 0,
            // 'Lines' => 0,
            // 'Words' => 0,
            // 'Characters' => 0,
            // 'CharactersWithSpaces' => 0,
        ];
        $DOM = new DOMDocument();
        $DOM->substituteEntities = true;
        $entries = [
            'docProps/app.xml',
            'docProps/core.xml',
        ];
        foreach($entries as $entry) {
            $content = $this->zip->getFromName($entry);
            // some generators (ABBYY) do not provide stats
            if ($content === false) {
                // Log::debug("404 " . $entry);
                continue;
            }
            $DOM->loadXML($content);
            $root = $DOM->documentElement;
            foreach( $root->childNodes as $node )
            {
                if ($node->nodeType !== 1) continue;
                $name = $node->nodeName;
                if (isset($props[$name])) {
                    $props[$name] = $node->textContent;
                }
            }
        }
        // verify signs by looping on document
        // DOM loop is very very slow
        // SAX is used instead
        $counter = new class {
            public $signs;
            private $t = false;
            public function start($parser, $name, $atts)
            {
                if($name == 'w:t') $this->t = true;
            }
            public function end($parser, $name)
            {
                if($name == 'w:t') $this->t = false;
            }
            public function text($parser, $data)
            {
                if (!$this->t) return;
                $this->signs += mb_strlen($data);
            }
        };
        $entries = [
            'word/document.xml',
            'word/footnotes.xml',
        ];
        foreach($entries as $entry) {
            $content = $this->zip->getFromName($entry);
            if ($content === false) {
                continue;
            }
            // parser seems to be rebuild each time
            $parser = xml_parser_create();
            xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
            xml_set_character_data_handler($parser, array($counter, "text"));
            xml_set_element_handler($parser, array($counter, "start"), array($counter, "end"));
            xml_parse($parser, $content, true);
            unset($content);
            xml_parser_free($parser);
        }
        $props['signs'] = $counter->signs;

        // count media files
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $stat = $this->zip->statIndex($i);
            $name = $stat['name'];
            if (strpos($name, 'word/media/') !== 0) continue;
            $props['media']++;
        }

        return $props;
    }
}

Docx::init();
