<?php

declare(strict_types=1);
/**
 * Part of Teinte https://github.com/oeuvres/teinte_php
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Teinte\Format;

use DOMDocument, DOMElement, DOMNode, DOMNodeList, DOMXpath;
use Exception;
use Oeuvres\Kit\{Check, Filesys, I18n, Log, Parse, Xt};
use Oeuvres\Xsl\{Xpack};


Check::extension('tidy');
/**
 * Extract texts from epub files
 */
class Epub extends Zip
{
    /** Opf content as a string */
    private ?string $opf_xml;
    /** path of the opf file */
    private ?string $opf_path;
    /** opf directory to solve relative path */
    private ?string $opf_dir = '';
    /** toc directory to solve relative path */
    private ?string $ncx_dir = '';
    /** dom version of the opf */
    private ?DOMDocument $opf_dom;
    /** xpath version of the opf */
    private ?DOMXpath $opf_xpath;
    /** manifest of resources, map id => path */
    private $manifest = [];
    /** A css model with semantic properties */
    private CssModel $style;
    /** A private dom of the html */
    private ?DOMDocument $dom;
    /** Regexp program to clean some html oddidie */
    private array $preg;

    /** Config for tidy html, used for inserted fragments: http://tidy.sourceforge.net/docs/quickref.html */
    public static $tidyconf = array(
        // will strip all unknown tags like <svg> or <section>
        'clean' => true, // MSO mess
        'doctype' => "omit",
        // 'force-output' => true, // let tidy complain
        // 'indent' => true, // xsl done
        'input-encoding' => "utf8", // ?? OK ?
        'newline' => "LF",
        'numeric-entities' => true,
        // 'new-blocklevel-tags' => 'section',
        // 'char-encoding' => "utf8",
        'output-encoding' => "utf8", // ?? OK ?
        'output-xhtml' => true,
        // 'output-xml' => true, // show-body-only will bug with <svg> => <html>
        // 'preserve-entities' => false, // unknown
        // 'quote-nbsp' => false,
        'wrap' => false,
        'show-body-only' => true,
    );

    public function __construct()
    {
        $this->style = new CssModel();
        $this->html = '';
        // useful for dev
        // Xpack::dir() = dirname(__DIR__, 3) . '/teinte_xsl/';
        // load a regex program
        $pcre_tsv = Xpack::dir() . 'html_tei/html_pcre.tsv';
        $this->preg = Parse::pcre_tsv($pcre_tsv);

    }

    /**
     * Load and check
     */
    public function load(string $file): bool
    {
        $this->style = new CssModel();
        unset($this->dom);
        $this->chops = 0;
        if (!parent::load($file)) {
            return false;
        }
        if (null === ($cont = $this->get('META-INF/container.xml'))) {
            Log::warning(I18n::_('Epub.container404', $file));
            return false;
        }
        // seen, container.xml in UTF-16
        $dom = Xt::loadXml($cont);
        $opf_path = null;
        foreach ($dom->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:container', 
            'rootfile') 
            as $el
        ) {
            $opf_path = $el->getAttribute('full-path');
        }
        if (!$opf_path) {
            Log::warning(I18n::_('Epub.opf400', $file));
            return false;
        }

        $this->opf_path = urldecode($opf_path);
        if (null === ($this->opf_xml = $this->get($this->opf_path))) {
            Log::warning(I18n::_('Epub.opf0', $file));
            return false;
        }
        // set dir for path resolution in opf
        $this->opf_dir = dirname($this->opf_path);
        if ($this->opf_dir == ".") $this->opf_dir = "";
        else $this->opf_dir .= "/"; // ensure ending slash
        $this->opf_dom = Xt::loadXml($this->opf_xml);
        // validate minimum required elements
        $ok = true;
        foreach (['metadata', 'manifest', 'spine'] as $el) {
            $nl = $this->opf_dom->getElementsByTagName('manifest');
            if (!$nl) {
                Log::warning(I18n::_('Epub.opfel404', $this->file, $this->opf_pat, $el));
                $ok = false;
            }
        }
        // decode all uris in opf
        if (!$ok) return false;
        $this->opf_xpath = new DOMXpath($this->opf_dom);
        $this->opf_xpath->registerNamespace("opf", "http://www.idpf.org/2007/opf");
        $nl = $this->opf_xpath->query("//@href");
        foreach ($nl as $node) {
            $node->value = urldecode($node->value);
        }
        // read resources
        $this->manifest();
        $this->spine();
        
        return true;
    }

    /**
     * 
     */
    public function html(): string
    {
        $this->dom();
        $this->dom->formatOutput = true;
        return $this->dom->saveXML();
    }

    /**
     * Build a dom from epub sections
     */
    private function dom(): void
    {
        if (isset($this->dom) && $this->dom != null) return;
        if (!isset($this->opf_dom)) {
            Log::error(I18n::_('Epub.load'));
            throw new Exception(I18n::_('Epub.load'));
        }
        $sections = $this->sections();
        $sections = preg_replace($this->preg[0], $this->preg[1], $sections);
        $html = "<article 
  xmlns=\"http://www.w3.org/1999/xhtml\"
  xmlns:epub=\"http://www.idpf.org/2007/ops\"
>
  <template id=\"css\">
" . $this->style->asXml() . "
  </template>
" . $sections . "
</article>
";
        // print $html;
        $dom = Xt::loadXml($html);
        $this->dom = Xt::transformToDoc(
            Xpack::dir() . 'html_tei/epub_teinte_html.xsl', 
            $dom
        );
    }
    
    /**
     * parse  toc to get pages in order and hierarchy
     */
    private function sections(): ?string
    {
        // concat toc + spine
        $xml = "<pack>\n";
        $toc = $this->ncx_xml();
        if (!$toc) $toc = '';
        $toc = preg_replace_callback(
            '/ src="([^"]*)"/',
            function ($matches) { 
                return ' src="' . urldecode($matches[1]) . '"';
            },
            $toc
        );
        // strip prolog
        $xml .= preg_replace("/^.*?(<\p{L}+)/su", '$1', $toc);
        $xml .= preg_replace("/^.*?(<\p{L}+)/su", '$1', $this->opf_xml);
        $xml .= "</pack>\n";
        $dom = Xt::loadXml($xml);
        // get an html from the toc with includes
        $sections = Xt::transformToXml(
            Xpack::dir().'html_tei/ncx_html.xsl', 
            $dom,
            ['ncx_dir' => $this->ncx_dir, 'opf_dir' => $this->opf_dir,]
        );

        $sections = preg_replace(
            ["/<body[^>]*>/", "/<\/body>/"],
            ["", ""],
            $sections
        );
        // resolve includes
        $sections = preg_replace_callback(
            '/<content src="([^"]+)"( to="([^"]+)")?\/>/',
            function ($matches) {                
                if (isset($matches[3])) {
                    return "\n" 
                    . $this->chop($matches[1], $matches[3]) 
                    . "\n";
                }
                else {
                    return "\n" 
                    . $this->chop($matches[1]) 
                    . "\n";
                }
            },
            $sections
        );
        // echo $sections;
        return $sections;
    }

    /**
     * transform to TEI
     */
    public function teiToDoc(): ?DOMDocument
    {
        // ensure html generation
        $this->dom();
        // produce a <teiHeader>
        $metadata = $this->opf_dom->getElementsByTagName('metadata')->item(0);

        $dom = Xt::dom();
        $metadata = $dom->importNode($metadata, true);
        $dom->appendChild($metadata);

        $teiHeader = Xt::transformToDoc(
            Xpack::dir() . 'html_tei/epub_dc_tei.xsl', 
            $dom
        );
        // toDom for indent-
        $dom = Xt::transformToDoc(
            Xpack::dir() . 'html_tei/html_tei.xsl', 
            $this->dom
        );
        $teiHeader = $dom->importNode($teiHeader->documentElement, true);
        $first = self::elder($dom->documentElement);
        $dom->documentElement->insertBefore($teiHeader, $first);
        $dom->formatOutput = true;
        return $dom;
    }

    public function tei(): ?string
    {
        $dom = $this->teiToDoc();
        return $dom->saveXML();
    }

    public static function elder(DOMNode $node): ?DOMElement
    {
        if(XML_ELEMENT_NODE != $node->nodeType ) return null;
        if (!$node->hasChildNodes()) return null;
        for ($i = 0, $count = $node->childNodes->count(); $i < $count; $i++) {
            $el = $node->childNodes->item($i);
            if(XML_ELEMENT_NODE == $el->nodeType) return $el; 
        }
    }


    /**
     * Populate the spine table, requires load() and manifest()
     * (for $this->opf_dom)
     */
    private function spine()
    {
        // keep the flow of <spine>
        $nl = $this->opf_dom->getElementsByTagName('spine');
        foreach ($nl->item(0)->childNodes as $node) {
            if ($node->nodeType != XML_ELEMENT_NODE) continue;
            $idref = $node->getAttribute("idref");
            if (!isset($this->manifest[$idref])) {
                Log::warning(I18n::_('Epub.spine404', $this->file, $idref));
                continue;
            }
            // if content.opf is not in same folder as toc.ncx, possible problems
            $path = $this->manifest[$idref];
            $this->spine[basename($path)] = $path;
        }
    }

    /**
     * populate the manifest table, and populate a css model
     */
    private function manifest()
    {
        $this->style = new CssModel();
        $nl = $this->opf_dom->getElementsByTagName('manifest');
        foreach ($nl->item(0)->childNodes as $node) {
            if ($node->nodeType != XML_ELEMENT_NODE) continue;
            $id = $node->getAttribute("id");
            $href = $node->getAttribute("href");
            $type = $node->getAttribute("media-type");
            if ($type == "text/css") {
                $css = $this->get($this->opf_dir . $href);
                if ($css === null) continue;
                $this->style->parse($css);
                Log::debug("load css " . $this->opf_dir . $href);
            }
            $this->manifest[$id] = $href;
        }
    }




    /**
     * Get a toc in ncx format
     * requires load() for content.opf
     * set some properties
     */
    private function ncx_xml(): ?string
    {
        // <item href="toc.ncx" id="ncx" media-type="application/x-dtbncx+xml"/>
        $nl = $this->opf_xpath->query("//opf:item[@media-type='application/x-dtbncx+xml']");
        if (!$nl->length) {
            Log::warning(I18n::_('Epub.ncx400', $this->file, $this->opf_path));
            return null;
        }
        $ncx_el = self::cast_el($nl->item(0));
        if (!$ncx_el || !$ncx_el->hasAttribute("href")) {
            Log::warning(I18n::_('Epub.ncx400', $this->file, $this->opf_path));
            return null;
        }
        $ncx_path = urldecode($ncx_el->getAttribute("href"));
        if ($ncx_path[0] != "/") $ncx_path = $this->opf_dir . $ncx_path;
        $this->ncx_dir = dirname($ncx_path);
        if ($this->ncx_dir == ".") $this->ncx_dir = "";
        else $this->ncx_dir .= "/";
        return $this->get($ncx_path);
    }

    /**
     * A workaround for a casting error with DOMNodeList
     */
    static public function cast_el(DOMNode $node): DOMElement
    {
        if (!$node) return null;
        if ($node->nodeType !== XML_ELEMENT_NODE) return null;
        return $node;
    }


    /**
     * According to toc, cut a piece of html to insert in a section hierachy
     */
    public function chop(string $from, ?string $to = null)
    {
        $html = "";
        $from_file = $from;
        $from_anchor = "";
        if ($pos = strpos($from, '#')) {
            list($from_file, $from_anchor) = explode("#", $from);
        }
        $to_file = $to;
        $to_anchor = "";
        if ($to && ($pos = strpos($to, '#'))) {
            list($to_file, $to_anchor) = explode("#", $to);
        }
        if ($to_file) $to_file = urldecode($to_file);
        
        // text to insert should be from one file only
        $from_file = urldecode($from_file);
        $contents = $this->get($from_file);
        if (!$contents) {
            $msg = I18n::_("Epub.chop.404", $this->file, $from_file);
            Log::warning($msg);
            $html .= "<!-- $msg -->\n";
            return $html;
        }
        // indent some blocks pbefor chopping
        $contents = preg_replace(
            array('@(</(div|h1|h2|h3|h4|h5|h6|p)>)([^\n])@', '@(<body)@'),
            array("$1\n$3", "\n$1"),
            $contents
        );
        // get the start index from wich insert
        $pos_start = 0;
        if (!preg_match('@<body[^>]*>@', $contents, $matches, PREG_OFFSET_CAPTURE)) {
            $msg = I18n::_("Epub.chop.body", $this->file, $from_file);
            Log::warning($msg);
            $html .= "<!-- $msg -->\n";
        } else {
            $pos_start = $matches[0][1] + strlen($matches[0][0]);
        }
        if ($from_anchor) {
            // take start of line
            // <h1 class="P_Heading_1"><span><a id="auto_bookmark_1"/>PROLOGUE</span></h1>
            if (!preg_match('@\n.*id="' . $from_anchor . '"@', $contents, $matches, PREG_OFFSET_CAPTURE)) {
                $msg = I18n::_("Epub.chop.anchor", $this->file, $from_file, $from_anchor);
                Log::warning($msg);
                $html .= "<!-- $msg -->\n";
            } else {
                $pos_start = $matches[0][1];
            }
        }
        // search en index
        $pos_end = strlen($contents);
        if (preg_match('@</body>@', $contents, $matches, PREG_OFFSET_CAPTURE)) {
            $pos_end = $matches[0][1];
        }
        // end anchor ?
        if ($from_file == $to_file) {
            // no anchor, bas toc, warn
            if (!$to_anchor) {
                $msg = I18n::_("Epub.chop.to.no_anchor", $this->file, $from_file, $from, $to);
                Log::warning($msg);
                $html .= "<!-- $msg -->\n";
            } 
            // anchor not found
            else if (!preg_match('@\n.*id="' . $to_anchor . '"@', $contents, $matches, PREG_OFFSET_CAPTURE)) {
                $msg = I18n::_("Epub.chop.to.anchor404", $this->file, $from_file, $from, $to, $to_anchor);
                Log::warning($msg);
                $html .= "<!-- $msg -->\n";
            } 
            // end index found
            else {
                $pos_end = $matches[0][1];
            }
        }
        $contents = substr($contents, $pos_start, $pos_end - $pos_start);
        // NO ! &
        // preserve some critic XML entities before transcoding
        $contents = preg_replace("@&(amp|lt|gt);@", "£$1;", $contents);
        $contents = html_entity_decode($contents, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $contents = preg_replace("@£(amp|lt|gt);@", "&$1;", $contents);
        // restore some entities before transcoding
        // $html = preg_replace( self::$rehtml[0], self::$rehtml[1], $html );
        // html usually need to be repaired, because of bad html fragments
        $contents = tidy_repair_string($contents, self::$tidyconf);

        $html .= $contents;

        return $html;
    }
}
