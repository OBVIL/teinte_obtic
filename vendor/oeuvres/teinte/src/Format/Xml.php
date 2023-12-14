<?php declare(strict_types=1);
/**
 * Part of Teinte https://github.com/oeuvres/teinte
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 */

namespace Oeuvres\Teinte\Format;

use Exception, DOMDocument, DOMXpath;
use Oeuvres\Kit\{Xt};

/**
 * Tei exports are designed as a Strategy pattern
 * {@see \Oeuvres\Teinte\AbstractTei2}
 * This class is the Context to use the different strategies.
 * All initialisations are as lazy as possible
 * to scan fast big directories.
 */
class Xml extends File
{
    /** Store XML as a string, maybe reused */
    protected ?string $xml = null;
    /** DOM Document to process */
    protected $dom;

    /**
     * Is there a dom loaded ?
     */
    public function isEmpty()
    {
        return ($this->dom === null);
    }

    /**
     * Load XML/TEI as a file (preferred way to hav some metas).
     */
    public function open(string $src_file): bool
    {
        if (!parent::open($src_file)) {
            return false;
        }
        $this->dom = null;
        $this->xml = null;
        $this->loadXML($this->contents());
        return true;
    }

    /**
     * Load XML/TEI as string, normalize and load it as DOM
     */
    public function loadXML(string $xml):DOMDocument
    {
        $xml = static::lint($xml);
        $this->xml = $xml;
        // spaces are normalized upper, keep them
        // set dom properties before loading
        $dom = new DOMDocument();
        $dom->substituteEntities = true;
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        $this->dom = Xt::loadXML($xml, $dom);
        if (!$this->dom) {
            throw new Exception("XML malformation");
        }
        return $this->dom;
    }

    /**
     * Some formats may override
     */
    static public function lint(string $xml):string
    {
        return $xml;
    }

    /**
     * Return an XPath processor (do not cache here, dom may have been modified)
     */
    public function xpath()
    {
        return Xt::xpath($this->dom);
    }

    /**
     * Return xml state (maybe transformed and diverges from origi)
     */
    public function xml(): string
    {
        if ($this->xml === null) {
            $this->xml = $this->contents();
        }
        return $this->xml;
    }
}
// EOF
