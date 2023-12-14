<?php

/**
 * Part of Teinte https://github.com/oeuvres/teinte
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 */

declare(strict_types=1);

namespace Oeuvres\Teinte\Format;

use Exception, DOMDocument, DOMXpath;
use Oeuvres\Kit\{Filesys,Log, Xt};
use Oeuvres\Teinte\Tei2\{AbstractTei2};

/**
 * A tei file with export strategies
 */
class Tei extends File
{
    use Teiable;
    /** Array of templates, registred by format when relevant */
    protected array $templates = [];


    /**
     * Load XML/TEI as a file (preferred way to hav some metas).
     */
    public function open(string $src_file): bool
    {
        $this->teiReset();
        if (!parent::open($src_file)) {
            // parent has return false, probably an error 
            return false;
        }
        $this->loadXML($this->contents());
        // set DocumentURI for xi:include resolution
        $this->teiDOM->documentURI = "file:///" . str_replace('\\', '/', realpath($src_file));
        // inclusions done, XML has change
        if ($this->teiDOM->xinclude()) {
            $this->teiXML = $this->teiDOM->saveXML();
        }
        return true;
    }


    /**
     * Load XML/TEI as a string, normalize and load it as DOM
     */
    public function loadXML(string $xml):DOMDocument
    {
        $this->teiReset();
        $tei = static::lint($xml);
        $this->teiXML = $tei;
        // spaces are normalized upper, keep them
        // set dom properties before loading
        $dom = new DOMDocument();
        $dom->substituteEntities = true;
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        $this->teiDOM = Xt::loadXML($tei, $dom);
        if (!$this->teiDOM) {
            throw new Exception("XML malformation");
        }
        return $this->teiDOM;
    }

    /**
     * Load a dom directly, 
     */
    public function loadDOM(DOMDocument $dom)
    {
        $this->teiReset();
        $this->teiDOM = $dom;
    }

    /**
     * Nothing to do, already TEI
     */
    public function teiMake(?array $pars = null): void
    {
        if (
            $this->teiXML == null 
            || $this->teiDOM == null 
            || !$this->teiDOM->documentElement
        ) {
            throw new Exception("No XML/TEI loaded, use Tei::() or Tei::loadDoc()");
            
        }

    }

    private function pars(string $format, ?array $pars = null)
    {
        if (isset($this->templates[$format])) {
            if (!is_array($pars)) {
                $pars = [];
            }
            else if (isset($pars['template'])) {
                return;
            }
            $pars['template'] = $this->templates[$format];
        }
        return $pars;
    }

    /**
     * Check if Tei file is in state to be exported
     */
    public function domCheck()
    {

    }

    /**
     * Transform current dom and write to file.
     */
    public function toURI(string $format, String $uri, ?array $pars = null)
    {
        if (!Filesys::writable($uri)) {
            throw new Exception("“{$uri}” not writable as a destination file");
        }
        if ($format == 'tei') {
            // TODO : copy linked images 
            file_put_contents($uri, $this->teiXML());
            return;
        } 
        $transfo = AbstractTei2::transfo($format);
        $pars = $this->pars($format, $pars);
        $transfo::toUri($this->teiDOM, $uri, $pars);
    }

    /**
     * Transform current dom and returns XML
     * (when relevant)
     */
    public function toXML(string $format, ?array $pars = null): string
    {
        $transfo = AbstractTei2::transfo($format);
        $pars = $this->pars($format, $pars);
        return $transfo::toXml($this->teiDOM, $pars);
    }

    /**
     * Transform current and returns result as dom
     * (when relevant)
     */
    public function toDOM(string $format, ?array $pars = null): DOMDocument
    {
        $transfo = AbstractTei2::transfo($format);
        $pars = $this->pars($format, $pars);
        return $transfo::toDoc($this->teiDOM, $pars);
    }

    /**
     * Build a destination file path according to a preferred format
     * extension. Nothing is supposed to be loaded, such path is used
     * for testing.
     */
    public static function destination(string $src_file, string $format, ?string $dst_dir = null): string
    {
        $transfo = AbstractTei2::transfo($format);
        return $transfo::destination($src_file, $dst_dir);
    }

    /**
     * Set a template for a format
     */
    public function template(string $format, string $tmpl_file)
    {
        if (!is_file($tmpl_file)) {
            throw new \InvalidArgumentException(
                "Template: \"$tmpl_file\" is not a valid file"
            );
        }
        if (!AbstractTei2::has($format)) {
            throw new \InvalidArgumentException(
                "Template: \"$format\" format not yet available as a TEI export"
            );
        }
        // validate extension ?
        $this->templates[$format] = $tmpl_file;
    }


    /**
     * Load a TEI string, and normalize things, especially 
     * spaces, for docx (bad indent produce bad spacing)
     */
    public static function lint(string $xml): string
    {
        $block = "(ab|bibl|byline|dateline|desc|entry|entryFree|head|l|label|lb|p|signed|salute)";
        $re_norm = array(
            '@\r\n?@' => "\n", // normalize EOL
            '@[ \t]*\n[ \t]*@' => "\n", // suppress trailing spaces
            "@\n([,.)\]}])@u" => "$1", // bad indent may have broke some pun
            "@(<$block(>| [^>]*>))\s+@" => "$1", // spaces at start of para
            "@\s+(</$block>)@" => '$1', // space before end of para
            // '@(<pb[^>]*>)\s+@' => '$1', // page breaks may add unwanted space
            /* Something have to be done with <pb/>
            '@(<(ab|head|l|p|stage)( [^>]*)?>)\s*(<pb( [^>]*)?/>)\s+@' => '$1$4',
            */
        );
        $xml = preg_replace(
            array_keys($re_norm),
            array_values($re_norm),
            $xml
        );
        /* libxml indent is quite nice, 
        but may produce undesired line break for some inlines containing inlines
        */
        return $xml;
    }


    /**
     * Book metadata
     */
    public function meta()
    {
        $meta = self::dc($this->teiDOM);
        $meta['code'] = pathinfo($this->file, PATHINFO_FILENAME);
        $meta['filename'] = $this->filename();
        $meta['filemtime'] = $this->filemtime();
        $meta['filesize'] = $this->filesize();
        return $meta;
    }

    /**
     * Return an array of metadata from a TEI DOM document
     */
    public static function dc($DOM)
    {
        $xpath = new DOMXpath($DOM);
        $xpath->registerNamespace('tei', "http://www.tei-c.org/ns/1.0");

        $meta = array();
        $nl = $xpath->query("/*/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:author");
        $meta['author'] = array();
        $meta['byline'] = null;
        $first = true;
        foreach ($nl as $node) {
            $value = $node->getAttribute("key");
            $text = preg_replace('@\s+@', ' ', trim($node->textContent));
            if (!$value) $value = $text;
            if (($pos = strpos($value, '('))) $value = trim(substr($value, 0, $pos));
            $meta['author'][] = $value;
            if ($first) {
                $meta['author1'] = $value;
                $first = false;
            } else $meta['byline'] .= " ; ";
            // prefer text value to att value
            if ($text) $meta['byline'] .= $text;
            else $meta['byline'] .= $value;
        }
        // editors
        $meta['editby'] = null;
        $nl = $xpath->query("/*/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:editor");
        $first = true;
        foreach ($nl as $node) {
            $value = $node->getAttribute("key");
            if (!$value) $value = $node->textContent;
            if (($pos = strpos($value, '('))) $value = trim(substr($value, 0, $pos));
            if ($first) $first = false;
            else $meta['editby'] .= " ; ";
            $meta['editby'] .= $value;
        }
        // title
        $nl = $xpath->query("/*/tei:teiHeader//tei:title");
        if ($nl->length) $meta['title'] = $nl->item(0)->textContent;
        else $meta['title'] = null;
        // publisher
        $nl = $xpath->query("/*/tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:publisher");
        if ($nl->length) $meta['publisher'] = $nl->item(0)->textContent;
        else $meta['publisher'] = null;
        // identifier
        $nl = $xpath->query("/*/tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:idno");
        if ($nl->length) $meta['identifier'] = $nl->item(0)->textContent;
        else $meta['identifier'] = null;
        // dates
        $nl = $xpath->query("/*/tei:teiHeader/tei:profileDesc/tei:creation/tei:date");
        // loop on dates
        $meta['created'] = null;
        $meta['issued'] = null;
        $meta['date'] = null;
        foreach ($nl as $date) {
            $value = $date->getAttribute('when');
            if (!$value) $value = $date->getAttribute('to');
            if (!$value) $value = $date->getAttribute('notAfter');
            if (!$value) $value = $date->nodeValue;
            $value = substr(trim($value), 0, 4);
            if (!is_numeric($value)) {
                $value = null;
                continue;
            }
            if (!$meta['date']) $meta['date'] = $value;
            if ($date->getAttribute('type') == "created" && !$meta['created']) $meta['created'] = $value;
            else if ($date->getAttribute('type') == "issued" && !$meta['issued']) $meta['issued'] = $value;
        }
        if (!$meta['issued'] && isset($value) && is_numeric($value)) $meta['issued'] = $value;
        $meta['source'] = null;
        return $meta;
    }

    /**
     * Output a txt fragment with no html tags for full-text searching
     */
    /*
    public function ft($destfile = null)
    {
        $html = $this->article();
        $html = self::detag($html);
        if ($destfile) file_put_contents($destfile, $html);
        return $html;
    }
    */

    /**
     * Extract <graphic> elements from a DOM doc,
     * copy linked images in a flat img_dir
     * and modify relative link
     *
     * $file_prefix : a prefix to build image file path
     * $href_prefix : a prefix to build a href link from tei XML to image file
     * return : a doc with updated links to image
     */
    public static function imagesCopy($dom, $file_prefix, $href_prefix, $counter=false)
    {
        if (!$dom->documentURI) {
            throw new Exception("DOMDocument has no documentURI property to resolve relative path to images");
        }
        if ($dom->documentURI == getcwd()) {
            throw new Exception("DOMDocument has no documentURI property to resolve relative path to images");
        }
        $dom_dir = dirname($dom->documentURI);
        // do not normalize href or file prefix with a '/', caller should know
        $count = 1;
        $nl = $dom->getElementsByTagNameNS('http://www.tei-c.org/ns/1.0', 'graphic');
        $pad = strlen(strval($nl->count()));
        $n = 0;
        foreach ($nl as $el) {
            $n++;
            $att = $el->getAttributeNode("url");
            if (!isset($att) || !$att || !$att->value) {
                continue;
            }
            $url = $att->value;
            if (strpos($url, 'http') === 0) {
                // copy images from the internet ?
                // continue;
            }
            $data = Filesys::loadURL($url, $dom_dir);
            if (!$data) {
                // something went wrong and should have been logged
                continue;
            }
            if ($counter) {
                $img_filename = str_pad(strval($n), $pad, "0", STR_PAD_LEFT) . '.' . $data['ext'];
            }
            // preserve original filename, avoiding collisions
            else {
                $count = 2;
                $img_filename = $data['name'] . '.' . $data['ext'];
                while (file_exists($file_prefix . $img_filename)) {
                    $img_filename = $data['name'] . $count . '.' . $data['ext'];
                    $count++;
                }
            }
            $img_file = $file_prefix . $img_filename;
            Filesys::mkdir(dirname($img_file));
            file_put_contents($img_file, $data['bytes']);
            $el->setAttribute("url", $href_prefix . $img_filename);
        }
    }
}

// EOF
