<?php

/**
 * Part of Teinte https://github.com/oeuvres/teinte
 * MIT License https://opensource.org/licenses/mit-license.php
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 */

declare(strict_types=1);

namespace Oeuvres\Kit;

Check::extension('xsl');

use DOMDocument, DOMElement, DOMNode, DOMXPath, Error, XSLTProcessor;

/**
 * A set of well configured method for XML manipulation with Libxml and xxltproc
 * with record of some odd tricks,
 * and xsl parsing cache for repeated transformations.
 * code convention https://www.php-fig.org/psr/psr-12/
 */
class Xt
{
    /** XSLTProcessors */
    private static $transcache = array();
    /** get a temp dir */
    private static $tmpdir;
    /** libxml options for DOMDocument */
    const LIBXML_OPTIONS =
        LIBXML_NOENT
            | LIBXML_NONET
            | LIBXML_NSCLEAN
            | LIBXML_NOCDATA
            | LIBXML_PARSEHUGE
         // | LIBXML_NOWARNING  // ? hide warn for <?xml-model
    ;

    /**
     * Intialize static variables
     */
    public static function init()
    {
        libxml_use_internal_errors(true); // keep XML error for this process
    }

    /**
     * Get a DOM document with best options from a file path
     */
    public static function load(string $src_file): ?DOMDocument
    {
        if (!Filesys::readable($src_file)) {
            Log::error("XML file not loaded");
            return null;
        }
        $DOM = self::DOM();
        // $DOM->recover=true; // no recover, display errors
        // suspend error reporting, libxml messages are better
        $ret = @$DOM->load($src_file, self::LIBXML_OPTIONS);
        self::logLibxml(libxml_get_errors());
        if (!$ret) return null;
        $DOM->documentURI = realpath($src_file);
        $DOM->xinclude(); // resolve xincludes
        return $DOM;
    }

    /**
     * 
     */
    public static function replaceText(
        DOMNode &$node, 
        $search, 
        $replace, 
        $exclude=[], 
        $include=[]
    ) {
        $exclude = array_flip($exclude);
        $include = array_flip($include);
        self::replaceRecurs(
            $node, 
            $search, 
            $replace,
            $exclude,
            $include,
        );
    }

    private static function replaceRecurs(
        DOMNode &$node, 
        &$search, 
        &$replace,
        &$exclude=[], 
        &$include=[]
    ) {
        $children = [];
        foreach ($node->childNodes as $child)
        {
            $children[] = $child;
        }
        foreach($children as $child) {
            // recurs on elements
            if ( $child->nodeType === XML_ELEMENT_NODE ) {
                $name = $child->tagName;
                // not an included element
                if (count($include) and !isset($include[$name])) {
                    continue;
                }
                // excluded element
                if (count($exclude) and isset($exclude[$name])) {
                    continue;
                }
                self::replaceRecurs($child, $search, $replace, $exclude, $include);
            }
            if ( $child->nodeType != XML_TEXT_NODE ) {
                continue;
            }
            $textOld = $child->wholeText;
            $textNew = preg_replace($search, $replace, $textOld);
            $textNodeNew = $node->ownerDocument->createTextNode($textNew);
            $node->replaceChild($textNodeNew, $child);
        }
    }

    /**
     * Output the informative libxml messages by the logger
     */
    public static function logLibxml(array $errors)
    {
        foreach ($errors as $error) {
            $message = "";
            if ($error->file) {
                $message .= $error->file;
            }
            $message .= "  " . ($error->line) . ":" . ($error->column) . " \t";
            $message .= "err:" . $error->code . " — ";
            $message .= trim($error->message);
            /* xslt error could be other than message
            if ($error->code == 1) { // <xsl:message>
                Log::info("<xsl:message> " . trim($error->message));
            } */
            if ($error->level == LIBXML_ERR_WARNING) {
                Log::warning($message);
            } else if ($error->level == LIBXML_ERR_ERROR) {
                Log::error($message);
            } else if ($error->level ==  LIBXML_ERR_FATAL) {
                Log::critical($message);
            }
        }
        libxml_clear_errors();
    }

    /**
     * Returns a DOM object
     */
    public static function loadXML(string $xml, ?DOMDocument $doc = null): ?DOMDocument
    {
        if ($doc == null) $doc = self::DOM();
        // suspend error reporting, libxml messages are better
        $ret = $doc->loadXML($xml, self::LIBXML_OPTIONS);
        self::logLibxml(libxml_get_errors());
        if (!$ret) return null;
        // if default documentURI is working directory, not a file
        if (is_file($doc->documentURI)) {
            $doc->xinclude(); // resolve xincludes
        }
        return $doc;
    }


    /**
     * Returns an empty DOM with nice options for indented xsl
     */
    public static function DOM(): DOMDocument
    {
        $DOM = new DOMDocument();
        $DOM->substituteEntities = true;
        $DOM->preserveWhiteSpace = false;
        $DOM->formatOutput = true;
        return $DOM;
    }

    /**
     * Get first child element of a node (when element)
     */
    public static function firstElementChild(DOMNode $node): ?DOMElement
    {
        if(XML_ELEMENT_NODE != $node->nodeType ) return null;
        if (!$node->hasChildNodes()) return null;
        for ($i = 0, $count = $node->childNodes->count(); $i < $count; $i++) {
            $el = $node->childNodes->item($i);
            if(XML_ELEMENT_NODE == $el->nodeType) return $el; 
        }
    }

    /**
     * xsl:tranform, result as a DOM document
     */
    public static function transformToDOM(
        string $xslfile,
        DOMDocument $DOM,
        ?array $pars = null
    ) {
        return self::transform(
            $xslfile,
            $DOM,
            null, // local code to say not a a string
            $pars
        );
    }

    /**
     * xsl:tranform, result as an XML string
     */
    public static function transformToXML(
        string $xslfile,
        DOMDocument $DOM,
        ?array $pars = null
    ) {
        return self::transform(
            $xslfile,
            $DOM,
            "", // local code to say fill the string
            $pars
        );
    }
    /**
     * xsl:tranform, result to a file
     */
    public static function transformToURI(
        string $xslfile,
        DOMDocument $DOM,
        string $URI,
        ?array $pars = null
    ) {
        return self::transform(
            $xslfile,
            $DOM,
            $URI,
            $pars
        );
    }

    /**
     * An xslt transformer with cache
     */
    private static function transform(
        string $xsl_file,
        DOMDocument $DOM,
        ?string $dst = null,
        ?array $pars = null
    ) {
        $pref = __CLASS__ . "::" . __FUNCTION__ . " ";
        if (strpos($xsl_file, "http") === 0) {
            $key = $xsl_file;
        } else {
            $key = realpath($xsl_file);
        }
        if (!$key) {
            Log::error("\"$xsl_file\" XSLT file not found");
            return null;
        }
        // cache compiled xsl
        if (!isset(self::$transcache[$key])) {
            $trans = new XSLTProcessor();
            $trans->registerPHPFunctions();
            // allow generation of <xsl:document>
            if (defined('XSL_SECPREFS_NONE')) {
                $prefs = constant('XSL_SECPREFS_NONE');
            } else if (defined('XSL_SECPREF_NONE')) {
                $prefs = constant('XSL_SECPREF_NONE');
            } else {
                $prefs = 0;
            }

            if (method_exists($trans, 'setSecurityPrefs')) {
                $oldval = $trans->setSecurityPrefs($prefs);
            } /* historic
            else if (method_exists($trans, 'setSecurityPreferences')) {
                $oldval = $trans->setSecurityPreferences($prefs);
            } */ else {
                ini_set("xsl.security_prefs",  $prefs);
            }
            // for xsl through http://, allow net download of resources 
            $xslDOM = new DOMDocument();
            if (false === $xslDOM->load($xsl_file)) {
                self::logLibxml(libxml_get_errors());
                Log::error("$pref load impossible:\n\"$xsl_file\"");
                return false;
            }
            if (!$trans->importStyleSheet($xslDOM)) {
                self::logLibxml(libxml_get_errors());
                Log::error("$pref compile impossible:\n\"$xsl_file\"");
                return false;
            }
            self::$transcache[$key] = $trans;
        }
        $trans = self::$transcache[$key];
        // add params
        if (isset($pars) && count($pars)) {
            foreach ($pars as $key => $value) {
                if (!$value) $value = "";
                $trans->setParameter("", strval($key), strval($value));
            }
        }
        // return a DOM document for efficient piping
        if ($dst === null) {
            $ret = $trans->transformToDoc($DOM);
        }
        // return XML as a string
        else if ($dst === '') {
            $ret = $trans->transformToXML($DOM);
        }
        // write to uri
        else {
            Filesys::mkdir(dirname($dst));
            $trans->transformToUri($DOM, $dst);
            $ret = $dst;
        }
        // here we should have XSL message only
        $errors = libxml_get_errors();
        if (count($errors)) self::logLibxml($errors);

        // reset parameters ! or they will kept on next transform if transformer is reused
        if (isset($pars) && count($pars)) {
            foreach ($pars as $key => $value) {
                $trans->removeParameter("", $key);
            }
        }
        return $ret;
    }
    /**
     * Replace tags of html file by spaces,
     * to get text with same offset index of words
     * allowing indexation and highlighting. Keep line breaks for line numbers.
     * Support of some html5 tag to strip not indexable content.
     * 2x faster than a char loop
     */
    static public function detag(string $html): string
    {
        // preg_replace_callback is safer and 2x faster than the /e modifier
        $html = preg_replace_callback(
            array(
                // s flag so that '.' could match \n
                // .*? ungreedy
                '@<!.*?>@s', // exclude doctype and comments
                '@<\?.*?\?>@s', // exclude PI
                '@<(head|header|footer|nav|noindex)[ >].*?</\1>@s', // suppress nav, let <aside> for notes
                '@<(small|tt|a)[^>]*>[0-9]+</\1>@', // line or note of number
                '@<a class="noteref".*?</a>@', // suppress footnote call
                '@<[^>]+>@' // wash tags
            ),
            // blanking a string, keeping new lines
            function ($matches) {
                return preg_replace("/[^\n]/u", " ", $matches[0]);
            },
            $html
        );
        return $html;
    }

    /**
     * Get an xpath processor from a DOM with registred namespaces for root
     */
    static public function xpath(DOMDocument $DOM): DOMXPath
    {
        $xpath = new DOMXPath($DOM);
        foreach ($xpath->query('namespace::*', $DOM->documentElement) as $node) {
            $xpath->registerNamespace($node->prefix, $node->namespaceURI);
        }
        return $xpath;
    }

    /**
     * get XML from a DOM sent by xsl
     */
    static function nodesetXML($nodeset, $inner = false)
    {
        $xml = '';
        if (!is_array($nodeset)) $nodeset = array($nodeset);
        foreach ($nodeset as $doc) {
            if($doc->firstChild === null) {
                continue;
            }
            if (get_class($doc->firstChild) === 'DOMText') {
                $xml .= $doc->textContent;
                continue;
            }
            // if (!$doc->textContent) // not seen after upper
            $doc->formatOutput = true;
            $doc->substituteEntities = true;
            $doc->encoding = "UTF-8";
            $doc->normalize();
            $xml .= $doc->saveXML($doc->documentElement);
        }
        if (!$xml) return null;
        // do not del root ns here
        // $xml = preg_replace('@ xmlns="http://www.w3.org/1999/xhtml"@', '', $xml);
        // cut the root element
        if ($inner) {
            $xml = substr($xml, strpos($xml, '>') + 1);
            $xml = substr($xml, 0, strrpos($xml, '<'));
        }
        return $xml;
    }
}
Xt::init();
