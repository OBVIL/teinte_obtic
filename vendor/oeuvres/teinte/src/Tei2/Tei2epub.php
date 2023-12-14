<?php declare(strict_types=1);

/**
 * Part of Teinte https://github.com/oeuvres/teinte_php
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Teinte\Tei2;

use Exception, DOMDocument, ZipArchive;
use Oeuvres\Kit\{Filesys, Log, Xt};
use Oeuvres\Xsl\{Xpack};
use Oeuvres\Teinte\Format\{Epub};

/**
 * Export a TEI document as an html fragment <article>
 */
class Tei2epub extends AbstractTei2
{
    const NAME = "epub";
    const EXT = '.epub';
    /** a pool of generated objects, used by static xsl function */
    static private $pool = [];
    /** counter in pool */
    static private $poolkey = 1;

    /**
     * Return a configured template or default
     */
    static private function template(?array $pars=null):string
    {
        if ($pars && isset($pars['template.epub'])) {
            return $pars['template.epub'];
        }
        return Xpack::dir() . '/tei_epub/template.epub';
    }

    /**
     * @ override
     */
    static public function toURI(DOMDocument $teiDOM, string $dst_file, ?array $pars=[])
    {
        Log::debug("Tei2" . static::NAME ." $dst_file");
        // copy the epub template as dst file
        $template = self::template($pars);

        if (!Filesys::readable($template)) {
            throw new Exception("“{$template}” not readble as a template file");
        }
        if (!Filesys::copy($template, $dst_file)) {
            throw new Exception("“{$dst_file}” not writable.\n" . Log::last());
        }
        $epub = new Epub();
        $epubId = self::$poolkey++;
        self::$pool[$epubId] = $epub;
        $epub->open($dst_file);
        // content.opf, extract from template, insert in TEI dom to merge for a new content.opf, put in the epub zip
        $opfDOM = $epub->opfDOM();
        $opfPath = $opfDOM->documentURI;
        $opfRoot = $opfDOM->documentElement;
        $opfRoot = $teiDOM->importNode($opfRoot, true);
        $teiDOM->documentElement->appendChild($opfRoot);
        $opfXML = Xt::transformToXML(
            Xpack::dir().'tei_epub/tei_opf.xsl',
            $teiDOM,
            [],
        );
        $epub->put($opfPath, $opfXML);
        // create xhtml pages (opf used to get css links)
        $report = Xt::transformToXML(
            Xpack::dir().'tei_epub/tei_epub.xsl',
            $teiDOM,
            [
                'epubId' => (int) $epubId,
            ]
        );

        // ? 
        // reset
        $teiDOM->documentElement->removeChild($opfRoot);
        unset(self::$pool[$epubId]);

        // toc.ncx, generate from TEI dom, put in the epub zip
        // pages, generate from TEI dom, insert in zip

        /*
        $dst_dir = Filesys::cleandir($dstFile) . "/";
        if (DIRECTORY_SEPARATOR == "\\") {
            $dst_dir = "file:///" . str_replace('\\', '/', $dst_dir);
        }
        $pars = array_merge($pars, array("dst_dir" => $dst_dir));
        // Log::info("Tei2" . static::NAME . "->toUri() " . $dst_dir);
        return Xt::transformToXml(
            Xpack::dir().static::XSL,
            $dom,
            $pars,
        );
        */
    }

    static public function item($epubId, $name, $html)
    {
        $html = Xt::nodesetXML($html);
        self::$pool[$epubId]->put('OEBPS/' . $name, $html);
        // echo $epubId . " — " . $name . "\n";
    }

    /**
     * @ override
     */
    static public function toDOM(DOMDocument $dom, ?array $pars=null):?\DOMDocument
    {
        Log::error(__METHOD__." dom export not relevant");
        return null;
    }
    /**
     * @ override
     */
    static public function toXML(DOMDocument $dom, ?array $pars=null):?string
    {
        Log::error(__METHOD__." string export not relevant");
        return null;
    }

}

// EOF