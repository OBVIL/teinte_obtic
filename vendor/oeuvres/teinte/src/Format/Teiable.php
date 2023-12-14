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
 * A File format accepting xml manipulation
 */
trait Teiable
{
    /** Store XML as a string, maybe reused */
    protected ?string $teiXML = null;
    /** DOM Document to process */
    protected ?DOMDocument $teiDOM = null;
    
    /**
     * Return xml state (maybe transformed and diverges from original)
     */
    function teiXML(): string
    {
        if ($this->teiXML === null) {
            $this->teiDOM();
            $this->teiXML = $this->teiDOM->saveXML();
        }
        return $this->teiXML;
    }

    /**
     * Write to file xml state (maybe transformed and diverges from original)
     */
    function teiURI(string $dstFile): void
    {
        if ($this->teiXML === null) {
            $this->teiDOM();
            $this->teiXML = $this->teiDOM->saveXML();
        }
        file_put_contents($dstFile, $this->teiXML);
    }


    /**
     * Return dom state (maybe transformed and diverges from original)
     */
    function teiDOM(): DOMDocument
    {
        if ($this->teiDOM === null || !$this->teiDOM->documentElement) {
            $this->teiMake();
        }
        if ($this->teiDOM !== null && $this->teiDOM->documentElement) {
            return $this->teiDOM;
        }
        // we may have a tei string here ?
        if ($this->tei === null) {
            throw new ErrorException("No tei or teiDOM produced by teiMake()");
        }
        $this->teiDOM = Xt::loadXML($this->tei);
        return $this->teiDOM;
    }

    /**
     * Reset values before loading
     */
    function teiReset(): void
    {
        $this->teiXML = null;
        $this->teiDOM = null;
        // if file
        if (property_exists($this, 'file')) $this->file = null;
        if (property_exists($this, 'filename')) $this->filename = null;
        if (property_exists($this, 'filemtime')) $this->filemtime = null;
        if (property_exists($this, 'filesize')) $this->filesize = null;
        if (property_exists($this, 'contents')) $this->contents = null;
    }


    /**
     * Make tei, usually on dom
     */
    abstract public function teiMake(?array $pars = null): void;
}
