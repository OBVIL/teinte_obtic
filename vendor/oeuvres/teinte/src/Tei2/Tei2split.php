<?php declare(strict_types=1);

/**
 * Part of Teinte https://github.com/oeuvres/teinte_php
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Teinte\Tei2;

use DOMDocument;
use Oeuvres\Kit\{Filesys, Log, Xsl};

/**
 * Export a TEI document as an html fragment <article>
 */

class Tei2split extends AbstractTei2
{
    const EXT = '_xml/';
    const XSL = "tei_split.xsl";
    const NAME = "split";

    /**
     * @ override
     */
    static public function toUri(DOMDocument $dom, string $dstFile, ?array $pars=array())
    {
        if (!$pars) $pars = array();
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
    }

    /**
     * @ override
     */
    static public function toDoc(DOMDocument $dom, ?array $pars=null):?\DOMDocument
    {
        Log::error(__METHOD__." dom export not relevant");
        return null;
    }
    /**
     * @ override
     */
    static public function toXml(DOMDocument $dom, ?array $pars=null):?string
    {
        Log::error(__METHOD__." string export not relevant");
        return null;
    }

}

// EOF