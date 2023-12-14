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

use Oeuvres\Kit\{Xt};
use Oeuvres\Xsl\{Xpack};

/**
 * An Html text for content import
 */
class Html extends File
{
    use Teiable;
    use Htmlable;

    /**
    * Efficient cut of head
    */
    public static function headSub($html) {
        if (!$start=stripos($html, "<head")) return "";
        $start=strpos($html, ">", $start)+1;
        $to=stripos($html, "</head>");
        if ($to) return substr($html, $start, $to - $start);
        else return substr($html, $start);
    }

    /**
     * Cut an html string to give only a body
     */
    public static function bodySub($html) {
        if (!$start=stripos($html, "<body")) return $html;
        $start=strpos($html, ">", $start)+1;
        $to=stripos($html, "</body>");
        if ($to) return substr($html, $start, $to - $start);
        else return substr($html, $start);
    }

    /**
     * Extract meta from an html <head>, especially some Dublin
     * Core practices with <link>
     * 
     * <title>Article 13. III. Paix de Longjumeau.
     * Édit de Paris. Édits de pacification.</title>
     * <meta name="label" content="III, 13"/>
     * <link rel="dc:isPartOf" href="." title="Édits de pacification"/>
     * <link rel="DC.isPartOf" href="edit_03" 
     *      title="III. Paix de Longjumeau. Édit de Paris"/>
     */
    public static function meta($html) {
        $head=self::headSub($html);
        $props=array();
        // keep title in memory
        $title=array("");
        preg_match('/<title>([^<]+)<\/title>/i', $head, $title);
        if (isset($title[1])) $props['title'][]=array(0=>$title[1], "string"=>$title[1]);
        // grab all tags candidates
        preg_match_all("/<(meta|link)[^>]+>/i", $head, $meta, PREG_PATTERN_ORDER);
        // filter tags kown to not be metas
        $meta=preg_grep( "/stylesheet|http-equiv|icon/", $meta[0], PREG_GREP_INVERT);
        // loop on meta to populate the array
        foreach ($meta as $line) {
            preg_match('/(name|rel)="([^"]+)"/i', $line, $key);
            preg_match('/(content|title)="([^"]+)"/i', $line, $string);
            preg_match('/(scheme|href)="([^"]+)"/i', $line, $uri);
            if (!isset($key[2])) continue;
            // strip namespace prefix of property
            if ($pos=strpos($key[2], '.')) $key[2]=substr($key[2], $pos+1);
            if ($pos=strpos($key[2], ':')) $key[2]=substr($key[2], $pos+1);
            // all props supposed repeat
            if(isset($uri[2]) && isset($string[2])) $props[$key[2]][]=array(0=>$string[2], "string"=>$string[2], 1=>$uri[2], "uri"=>$uri[2]);
            else if(isset($uri[2])) $props[$key[2]][]=array(0=>$uri[2], "uri"=>$uri[2]);
            else if(isset($string[2])) $props[$key[2]][]=array(0=>$string[2], "string"=>$string[2]);
        }
        // rebuild a clean meta block ready to include in HTML
        $meta="\n    " . @$title[0] . "\n    " . implode("\n    ", $meta);
        return $meta;
      }
    
    /**
     * Hilite, build a regexp with an array of words
     * $a : tableau de termes à chercher
     */
    static public function hi_re ($a) {
        if (!is_array($a)) $a=array($a);
        // transformer une requête en expression régulière
        $re_q=array (
            '/[+\-<>~$^\[\]{},\.\"\\|\'\n\t\r]/u'=>' ', // échapper les caractères regexp un peu spéciaux
            '/([\(\)])/' => '\\\\$1', // protéger les parenthèses
            '/^ +/'=>'', // trim
            '/ +$/'=>'', // trim
            '/ +/'=>' ', // normaliser les espaces
            '/^\*+/'=>"", // supprimer les jokers en début de mot
            '/ /'=>'[\-\s\(\)\'’,_]+', // savoir passer un peu de ponctuation entre les mots d'un terme
            '/\*/'=>'[^ \.\)\],<" ”»=]*',  //  joker '*' = caractère qui n'est pas un séparateur ou une balise
            '/\?/'=>'[^ \.\)\],<" ”»=]',   // en classe unicode \pL, [^ \.\)\],<" ”»=]
            '/a/' => '[aáàÁ]',
            '/e/' => '[eéèêë]',
            '/i/' => '[iíïî]',
            '/o/' => '[oóôö]',
            '/u/' => '[uúûü]',
            '/n/' => '[nñ]',
        );
        $a=preg_replace(array_keys($re_q), array_values($re_q), $a);
        // supprimer les valeurs vides
        $a=array_diff($a, array(""));
        if (count($a) < 1) return;
        // re pour surligner dans du texte
        $keys=preg_replace('/^(.*)$/', '/(?<=[\s >\.,\*\(\[\'’\-])($1)(?=[\s \., <\*\)\- \]:])/iu', $a);
        $re=array_combine($keys, array_fill  ( 0 , count($keys) , '<mark>$1</mark>' ));
        // re pour surligner des attributs title="mon mot" > title="mon mot" class="hi"
        $keys=preg_replace('/^(.*)$/', '/<([^\/> ]+)[^>]*(title="$1")[^>]*>/iu', $a);
        $re=array_merge ($re, array_combine($keys, array_fill  ( 0 , count($keys) , '<$1 $2 class="mark">' )) );
        return $re;
    }

    /**
     * Make html
     */
    public function htmlMake(?array $pars = null):void
    {
        $this->html = $this->contents();
    }

    /**
     * Make tei, Not well tested
     */
    public function teiMake(?array $pars = null):void
    {
        // ensure html making
        $htmlDOM = $this->htmlDOM();
        $this->teiDOM = Xt::transformToDOM(
            Xpack::dir() . 'html_tei/html_tei.xsl', 
            $htmlDOM
        );
    }

}

