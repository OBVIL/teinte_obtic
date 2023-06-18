<?php
/**
 * Part of Teinte https://github.com/oeuvres/teinte
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

declare(strict_types=1);

namespace Oeuvres\Teinte\Format;

use Exception;
use ParsedownExtra;
use Oeuvres\Kit\{Filesys, I18n, Log, Xt};
use Oeuvres\Xsl\{Xpack};

/**
 * An Html text for content import
 */
class Markdown extends File
{
    use Teiable;
    use Htmlable;
    static $init = false;
    static private ParsedownExtra $parser;
    
    /**
     * Get an md parser
     */
    static public function init():void
    {
        if (self::$init) return;
        self::$parser = new ParsedownExtra();
        self::$init = true;
        // useful for dev
        // Xpack::dir() = dirname(__DIR__, 3) . '/teinte_xsl/';

    }

    /**
     * Output html from content
     */
    public function htmlMake(?array $pars = null):void
    {
        $contents = $this->contents();
        if (!$contents) {
            throw new Exception(I18n::_('File.noload', $this->file));
        }
        $xhtml = self::$parser->text($contents);
        /*
        $xhtml = "<article xmlns=\"http://www.w3.org/1999/xhtml\">\n"
            ."  <section>\n"
            . $xhtml
            . "  </section>\n"
            ."</article>\n";
        */
        // restore hirearchy of title
        $last = -1;
        $xhtml =  preg_replace_callback(
            "/<h(\d)|(<[a-z]+ [^>]*(class|id)=\"footnotes\"[^>]*>)/",
            function ($matches) use (&$last) {
                // if first header is not <h1>, force to 1
                if ($last == -1) {
                    $last = 1;
                    return $matches[0];
                }
                $hier = '';
                // footnotes, close all, put at right level
                if (isset($matches[2]) && $matches[2]) {
                    // closing
                    for ($i = 0; $i <=  $last - 1; $i++) {
                        $hier .= "</section>\n";
                    }
                    $hier .= "<section class=\"footnotes\">\n";
                    $hier .= $matches[0];
                    $last = 1;
                    return $hier;
                }
                $level = $matches[1];
                // closing
                for ($i = 0; $i <=  $last - $level; $i++) {
                    $hier .= "</section>\n";
                }
                // always open one
                $hier .= "<section>\n";
                $hier .= $matches[0];
                // maybe not consistent ?
                // h1, h3
                if ($level > $last) $last++;
                else $last = $level;
                return $hier;
            },
            $xhtml
        );
        $xhtml = "<article xmlns=\"http://www.w3.org/1999/xhtml\">\n"
        . "  <section>\n"
        . $xhtml
        . "  </section>\n";
        for ($i = 1; $i < $last; $i++) {
            $xhtml .= "  </section>\n";
        }
        $xhtml.= "</article>\n";
        $this->html = $xhtml;
    }
    /**
     * Make tei, kept internally as dom
     */
    public function teiMake(?array $pars = null):void
    {
        // ensure html making
        $htmlDoc = $this->htmlDoc();
        $this->teiDoc = Xt::transformToDoc(
            Xpack::dir() . 'html_tei/html_tei.xsl', 
            $htmlDoc
        );
    }

}
Markdown::init();