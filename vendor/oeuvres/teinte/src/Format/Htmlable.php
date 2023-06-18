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
trait Htmlable
{
    /** Store an html string */
    protected ?string $html = null;
    /** DOM Document to process */
    protected ?DOMDocument $htmlDoc = null;
    
    /**
     * Return xml state (maybe transformed and diverges from original)
     */
    function html(): string
    {
        // a dom have been calculated and kept during process
        if ($this->html === null) {
            $this->htmlMake();
        }
        if ($this->html !== null) {
            return $this->html;
        }
        $this->htmlDoc();
        $this->html = $this->htmlDoc->saveXML();
        return $this->html;
    }

    /**
     * Return dom state
     */
    function htmlDoc(): DOMDocument
    {
        if ($this->htmlDoc === null || !$this->htmlDoc->documentElement) {
            $this->htmlMake();
        }
        if ($this->htmlDoc !== null && $this->htmlDoc->documentElement) {
            return $this->htmlDoc;
        }
        // problem
        if ($this->html === null) {
            throw new ErrorException("No html or htmlDoc produced by htmlMake()");
        }
        $this->htmlDoc = Xt::loadXml($this->html);
        return $this->htmlDoc;
    }

    /**
     * Reset values before loading
     */
    function htmlReset(): void
    {
        $this->html = null;
        $this->htmlDoc = null;
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
    abstract public function htmlMake(?array $pars = null): void;
}
