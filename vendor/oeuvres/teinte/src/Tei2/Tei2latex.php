<?php declare(strict_types=1);

/**
 * Part of Teinte https://github.com/oeuvres/teinte_php
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Teinte\Tei2;

use DOMDocument, DOMNode;
use Oeuvres\Kit\{Filesys, Log, Xt};
use Oeuvres\Teinte\Format\{Tei};
use Oeuvres\Xsl\{Xpack};



/**
 * Transform an XML/TEI file in LaTeX
 */
class Tei2latex  extends AbstractTei2
{
    
    private string $template = "";
    const EXT = '.tex';
    const NAME = "latex";



    public $dom; // dom prepared for TeX (escapings, images)
    protected $srcfile;
    static protected $latex_xsl;
    static protected $latex_meta_xsl;
    // escape all text nodes, use on text node in dom, not through xml &
    const LATEX_ESC = array(
        '@\\\@u' => '\textbackslash ', // before adding \ for escapings
        '@([%\$#&_{}])@u' => '\\\$1', // be careful of & through XML
        '@~@u' => '\textasciitilde{}',
        '@\^@u' => '\textasciicircum{}',
        // '@(\p{Han}[\p{Han} ]*)@u' => '\zh{$1}',
        '@\s+@' => ' ', // not unicode \s, keep unbreakable space
    );


    /**
     * resolve tex inclusions and image links
     *
     * $tex_file: ! path to a tex file
     * $work_dir: ? destination dir place where the tex will be processed.
     * $img_dir: ? a path where to put graphics, relative to workdir or absolute.
     *
     * If resources are found, they are copied in img_dir, and tex is rewrite in consequence.
     * No suport for \graphicspath{⟨dir⟩+}
     */
    static function includes($tex_file, $work_dir = "", $img_dir = "")
    {
        $src_dir = dirname($tex_file);
        if ($src_dir) $src_dir = rtrim($src_dir, '/\\') . '/';
        if ($work_dir) $work_dir = rtrim($work_dir, '/\\') . '/';
        if ($img_dir) $img_dir = rtrim($img_dir, '/\\') . '/';
        $tex = file_get_contents($tex_file);

        // rewrite graphics links and copy resources
        // \includegraphics[width=\columnwidth]{bandeau.pdf}
        $img_abs = null;
        if ($work_dir) {
            // same folder
            if (!$img_dir);
            // absolute path
            else if (Filesys::isabs($img_dir)) {
                $img_abs = $img_dir;
            } else {
                $img_abs = $work_dir . $img_dir;
            }
        }
        $tex = preg_replace_callback(
            '@(\\\includegraphics[^{]*){(.*?)}@',
            function ($matches) 
            use ($src_dir, $work_dir, $img_dir, $img_abs) {
                $img_file = $matches[2];
                if (!Filesys::isabs($img_file)) {
                    $img_file = $src_dir . $img_file;
                }
                $img_basename = basename($img_file);
                $replace = $matches[0];
                if (!file_exists($img_file)) {
                    fwrite(STDERR, "graphics not found: $img_file\n");
                } 
                else if ($work_dir) {
                    // create img folder only if needed
                    Filesys::mkdir($img_abs);
                    copy($img_file, $img_abs . $img_basename);
                    $replace = $matches[1] . '{' . $img_dir . $img_basename . '}';
                }
                return $replace;
            },
            $tex
        );
        // \input{../latex/teinte}
        $tex = preg_replace_callback(
            '@\\\input *{(.*?)}@',
            function ($matches) use ($src_dir, $work_dir, $img_dir) {
                $inc = $src_dir . $matches[1] . '.tex';
                return self::includes($inc, $work_dir, $img_dir);
            },
            $tex
        );

        return $tex;
    }

    /**
     * Return a configured template or default
     */
    static private function template(?array $pars=null):string
    {
        if ($pars && isset($pars['template'])) {
            return $pars['template'];
        }
        return Xpack::dir() . '/tei_latex/template.tex';
    }

    /**
     * @ override
     */
    static public function toURI(
        DOMDocument $docOrig, 
        string $latex_file, 
        ?array $pars = null
    ) {
        Log::debug("Tei2" . static::NAME ." $latex_file");

        // install template in the destination directory where TeX will work
        $latex_name = pathinfo($latex_file, PATHINFO_FILENAME);
        $latex_template = self::template($pars);
        $latex_dir = dirname($latex_file) . "/";
        $img_href = "img/";
        $img_dir = $latex_dir . $img_href;


        // get latex as a string installed in $latex_dir
        $latex_string = self::includes($latex_template, $latex_dir, $img_dir);


        // clone dom, clean text nodes
        $doc = $docOrig->cloneNode(true);
        Xt::replaceText(
            $doc, 
            array_keys(self::LATEX_ESC), 
            array_values(self::LATEX_ESC),
            ['formula'] // formula may contain LaTeX
        );
        Tei::imagesCopy($doc, $img_dir, $img_href);
        // for debug, copy of XML
        Filesys::mkdir($latex_dir);
        file_put_contents("$latex_dir/$latex_name.xml", $doc->saveXML());

        $meta = Xt::transformToXml(
            Xpack::dir() . 'tei_latex/tei_meta_latex.xsl',
            $doc,
            $pars,
        );
        $xsl = Xpack::dir() . 'tei_latex/tei_latex.xsl';
        if (isset($pars['latex.xsl'])) $xsl = $pars['latex.xsl'];
        $text = Xt::transformToXml(
            $xsl,
            $doc,
            $pars,
        );
        $latex_string = str_replace(
            array('%meta%', '%text%'),
            array($meta, $text),
            $latex_string,
        );
        file_put_contents($latex_file, $latex_string);
    }

    /**
     * @ override
     */
    static function toDOM(DOMDocument $dom, ?array $pars=null):?\DOMDocument
    {
        Log::error(__METHOD__." dom export not relevant");
        return null;
    }
    /**
     * @ override
     */
    static function toXML(DOMDocument $dom, ?array $pars=null):?string
    {
        Log::error(__METHOD__." xml export not relevant");
        return null;
    }

}
