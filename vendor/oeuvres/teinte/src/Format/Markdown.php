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
    static $init = false;
    static private ParsedownExtra $parser;
    /** keep last html */
    private string $html;
    
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
    public function html():string
    {
        $contents = $this->contents();
        if (!$contents) {
            throw new Exception(I18n::_('File.noload', $this->file));
        }
        $html = self::$parser->text($contents);
        /*
        $html = "<article xmlns=\"http://www.w3.org/1999/xhtml\">\n"
            ."  <section>\n"
            . $html
            . "  </section>\n"
            ."</article>\n";
        */
        // restore hirearchy of title
        $last = -1;
        $html =  preg_replace_callback(
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
            $html
        );
        $html = "<article xmlns=\"http://www.w3.org/1999/xhtml\">\n"
        . "  <section>\n"
        . $html
        . "  </section>\n";
        for ($i = 1; $i < $last; $i++) {
            $html .= "  </section>\n";
        }
        $html.= "</article>\n";

        $this->html = $html;
        return $this->html;
    }
    /**
     * Output tei from html from contents
     */
    public function tei():string
    {
        if (!isset($this->html) || !$this->html) {
            $this->html();
        }
        // is it xml conformant ? let’s see
        $dom = Xt::loadXml($this->html);
        $tei = Xt::transformToXml(
            Xpack::dir() . 'html_tei/html_tei.xsl', 
            $dom
        );
        return $tei;
    }

}
Markdown::init();