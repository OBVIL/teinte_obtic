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
    use Htmlable;
    use Teiable;
    /** Opf content as a string */
    private ?string $opfXML;
    /** path of the opf file */
    private ?string $opfPath;
    /** opf directory to solve relative path */
    private ?string $opfDir = '';
    /** dom version of the opf */
    private ?DOMDocument $opfDOM;
    /** xpath version of the opf */
    private ?DOMXpath $opfXpath;
    /** toc directory to solve relative path */
    private ?string $ncxDir = '';
    /** manifest of resources, map id => path */
    private $manifest = [];
    /** spine, map name => path */
    private $spine = [];
    /** A css model with semantic properties */
    private CssModel $style;
    /** A private dom of the html */
    // private ?DOMDocument $dom;
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
     * DomDoc with right options here (no indent ?)
     */
    private static function DOM() {
        $DOM = new DOMDocument();
        $DOM->substituteEntities = true;
        $DOM->preserveWhiteSpace = true;
        $DOM->formatOutput = false;
        return $DOM;
    }

    /**
     * Load and check
     */
    public function open(string $file, ?int $flags = 0): bool
    {
        $this->reset();
        if (!parent::open($file)) {
            return false;
        }
        $this->style = new CssModel();
        // $this->chops = 0; // what was it ?
        
        return true;
    }

    /**
     * Reset props
     */
    public function reset():void
    {
        parent::reset();
        unset($this->opfDOM);
        $this->teiReset();
        $this->htmlReset();

    }

    /**
     * Get opf dom
     */
    public function opfDOM()
    {
        if (isset($this->opfDOM)) return $this->opfDOM; 
        if (null === ($XML = $this->get('META-INF/container.xml'))) {
            Log::warning(I18n::_('Epub.container404', $this->file));
            return false;
        }
        // seen, container.xml in UTF-16
        $DOM = Xt::loadXML($XML);
        $opfPath = null;
        foreach ($DOM->getElementsByTagNameNS(
            'urn:oasis:names:tc:opendocument:xmlns:container', 
            'rootfile') 
            as $el
        ) {
            $opfPath = $el->getAttribute('full-path');
        }
        if (!$opfPath) {
            Log::warning(I18n::_('Epub.opf400', $this->file));
            return false;
        }

        $this->opfPath = urldecode($opfPath);
        if (null === ($this->opfXML = $this->get($this->opfPath))) {
            Log::warning(I18n::_('Epub.opf0', $this->file));
            return false;
        }
        // set dir for path resolution in opf
        $this->opfDir = dirname($this->opfPath);
        if ($this->opfDir == ".") $this->opfDir = "";
        else $this->opfDir .= "/"; // ensure ending slash
        $this->opfDOM = Xt::loadXML($this->opfXML);
        $this->opfDOM->documentURI = $this->opfPath;
        // decode all %## in uris of opf
        $this->opfXpath = new DOMXpath($this->opfDOM);
        $this->opfXpath->registerNamespace("opf", "http://www.idpf.org/2007/opf");
        $nl = $this->opfXpath->query("//@href");
        foreach ($nl as $node) {
            $node->value = urldecode($node->value);
        }
        return $this->opfDOM;
    }

    /**
     * Build a dom from epub sections
     */
    public function htmlMake(?array $pars = null): void
    {
        // validate minimum required elements
        $ok = true;
        $this->opfDOM();
        foreach (['metadata', 'manifest', 'spine'] as $el) {
            $nl = $this->opfDOM->getElementsByTagName($el);
            if (!$nl) {
                Log::warning(I18n::_('Epub.opfel404', $this->file, $this->opfPath, $el));
                $ok = false;
            }
        }
        // are those files needed ?
        $this->manifest();
        $this->spine();
        $sections = $this->sections();
        $sections = preg_replace($this->preg[0], $this->preg[1], $sections);
        $css = "";
        $css = htmlspecialchars($this->style->contents(), ENT_NOQUOTES|ENT_IGNORE);
        $xhtml = "<article 
  xmlns=\"http://www.w3.org/1999/xhtml\"
  xmlns:epub=\"http://www.idpf.org/2007/ops\"
>
  <template id=\"css\">
" . $this->style->asXml() . "
  </template>
  <style title=\"epub\">
" . preg_replace("/--/", "—", $css) . "
  </style>
" . $sections . "
</article>
";
        // css will be included as XML comment, clean here the '--'
        // a no indent dom, work is done upper
        $DOM = self::DOM();
        Xt::loadXML($xhtml, $DOM);
        // indent yes or indent no (la la la)
        $this->htmlDOM = Xt::transformToDOM(
            Xpack::dir() . 'html_tei/epub_teinte_html.xsl', 
            $DOM
        );
    }
    
    /**
     * parse  toc to get pages in order and hierarchy
     */
    private function sections(): ?string
    {
        // TODO, concat toc + spine
        $xml = "<pack>\n";
        $toc = $this->ncxXML();
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
        $xml .= preg_replace("/^.*?(<\p{L}+)/su", '$1', $this->opfXML);
        $xml .= "</pack>\n";
        $dom = Xt::loadXML($xml);
        // get an html from the toc with includes
        $sections = Xt::transformToXml(
            Xpack::dir().'html_tei/ncx_html.xsl', 
            $dom,
            ['ncx_dir' => $this->ncxDir, 'opf_dir' => $this->opfDir,]
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
    public function teiMake(?array $pars = null): void
    {
        // ensure xhtml generation
        $this->htmlDOM();
        // to produce a <teiHeader>, make a new doc to transform with opf
        $metadata = $this->opfDOM->getElementsByTagName('metadata')->item(0);
        $metaDoc = Xt::dom();
        $metadata = $metaDoc->importNode($metadata, true);
        $metaDoc->appendChild($metadata);

        $teiHeader = Xt::transformToDOM(
            Xpack::dir() . 'html_tei/epub_dc_tei.xsl', 
            $metaDoc
        );


        // toDom for indent-
        $this->teiDOM = Xt::transformToDOM(
            Xpack::dir() . 'html_tei/html_tei.xsl', 
            $this->htmlDOM
        );


        $teiHeader = $this->teiDOM->importNode($teiHeader->documentElement, true);
        $text = Xt::firstElementChild($this->teiDOM->documentElement);
        $this->teiDOM->documentElement->insertBefore($teiHeader, $text);
        $this->teiDOM->formatOutput = true;
    }

    /**
     * Populate the spine table, requires load() and manifest()
     * (for $this->opf_dom)
     */
    private function spine()
    {
        // keep the flow of <spine>
        $nl = $this->opfDOM->getElementsByTagName('spine');
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
        $nl = $this->opfDOM->getElementsByTagName('manifest');
        foreach ($nl->item(0)->childNodes as $node) {
            if ($node->nodeType != XML_ELEMENT_NODE) continue;
            $id = $node->getAttribute("id");
            $href = $node->getAttribute("href");
            $type = $node->getAttribute("media-type");
            if ($type == "text/css") {
                $css = $this->get($this->opfDir . $href);
                if ($css === null) continue;
                $this->style->parse($css);
                Log::debug("load css " . $this->opfDir . $href);
            }
            $this->manifest[$id] = $href;
        }
    }




    /**
     * Get a toc in ncx format
     * requires load() for content.opf
     * set some properties
     */
    private function ncxXML(): ?string
    {
        // <item href="toc.ncx" id="ncx" media-type="application/x-dtbncx+xml"/>
        $nl = $this->opfXpath->query("//opf:item[@media-type='application/x-dtbncx+xml']");
        if (!$nl->length) {
            Log::warning(I18n::_('Epub.ncx400', $this->file, $this->opfPath));
            return null;
        }
        $ncxEl = self::castNode($nl->item(0));
        if (!$ncxEl || !$ncxEl->hasAttribute("href")) {
            Log::warning(I18n::_('Epub.ncx400', $this->file, $this->opfPath));
            return null;
        }
        $ncxPath = urldecode($ncxEl->getAttribute("href"));
        if ($ncxPath[0] != "/") $ncxPath = $this->opfDir . $ncxPath;
        $this->ncxDir = dirname($ncxPath);
        if ($this->ncxDir == ".") $this->ncxDir = "";
        else $this->ncxDir .= "/";
        return $this->get($ncxPath);
    }

    /**
     * A workaround for a casting error with DOMNodeList
     */
    static public function castNode(DOMNode $node): DOMElement
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
            $msg = preg_replace("/--/", "—", $msg); // if filename with --
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
            $msg = preg_replace("/--/", "—", $msg); // if filename with --
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
                $msg = preg_replace("/--/", "—", $msg); // if filename with --
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
                $msg = preg_replace("/--/", "—", $msg); // if filename with --
                $html .= "<!-- $msg -->\n";
            } 
            // anchor not found
            else if (!preg_match('@\n.*id="' . $to_anchor . '"@', $contents, $matches, PREG_OFFSET_CAPTURE)) {
                $msg = I18n::_("Epub.chop.to.anchor404", $this->file, $from_file, $from, $to, $to_anchor);
                Log::warning($msg);
                $msg = preg_replace("/--/", "—", $msg); // if filename with --
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
