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
trait Xhtmlable
{
    /** Store an html string */
    protected ?string $xhtml = null;
    /** DOM Document to process */
    protected ?DOMDocument $xhtmlDom = null;
    
    /**
     * Return xml state (maybe transformed and diverges from original)
     */
    function xhtml(): string
    {
        // a dom have been calculated and kept during process
        if ($this->xhtml === null) {
            $this->xhtmlMake();
        }
        if ($this->xhtml !== null) {
            return $this->xhtml;
        }
        $this->xhtmlDom();
        $this->xhtml = $this->xhtmlDom->saveXML();
        return $this->xhtml;
    }

    /**
     * Return dom state
     */
    function xhtmlDom(): DOMDocument
    {
        if ($this->xhtmlDom === null || !$this->xhtmlDom->documentElement) {
            $this->xhtmlMake();
        }
        if ($this->xhtmlDom !== null && $this->xhtmlDom->documentElement) {
            return $this->xhtmlDom;
        }
        // problem
        if ($this->xhtml === null) {
            throw new ErrorException("No xhtml or xhtmlDom produced by xhtmlMake()");
        }
        $this->xhtmlDom = Xt::loadXml($this->xhtml);
        return $this->xhtmlDom;
    }

    /**
     * Reset values before loading
     */
    function xhtmlReset(): void
    {
        $this->xhtml = null;
        $this->xhtmlDom = null;
    }

    /**
     * Make tei, usually on dom
     */
    abstract public function xhtmlMake(?array $pars = null): void;
}
