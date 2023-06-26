<?php declare(strict_types=1);
/**
 * Part of Teinte https://github.com/oeuvres/teinte_php
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Teinte\Tei2;

use Exception, DOMDocument, ZipArchive;
use Oeuvres\Kit\{Check, Log, Filesys, Xt};
use Oeuvres\Xsl\{Xpack};
Check::extension('zip');

/**
 * Output a MS.Word docx document from TEI.
 * code convention https://www.php-fig.org/psr/psr-12/
 */
class Tei2docx extends AbstractTei2
{
    /** A docx file used as a template */
    const EXT = '.docx';
    const NAME = "docx";

    /** Some mapping between 3 char iso languange code to 2 char */
    const ISO639_3char = [
        'eng' => 'en',
        'fra' => 'fr',
        'lat' => 'la',    
    ];

    public static function init()
    {
        parent::init();
    }


    /**
     * Return a configured template or default
     */
    static private function template(?array $pars=null):string
    {
        if ($pars && isset($pars['template'])) {
            return $pars['template'];
        }
        return Xpack::dir() . '/tei_docx/template.docx';
    }

    /**
     * @ override
     */
    static function toDoc(DOMDocument $dom, ?array $pars=null):?\DOMDocument
    {
        Log::error(__METHOD__." dom export not relevant");
        return null;
    }
    /**
     * @ override
     */
    static function toXml(DOMDocument $dom, ?array $pars=null):?string
    {
        Log::error(__METHOD__." xml export not relevant");
        return null;
    }

    /**
     * @ override
     */
    static function toUri($dom, $dst_file, ?array $pars=null)
    {
        Log::debug("Tei2" . static::NAME ." $dst_file");
        $template = self::template($pars);
        if (!Filesys::readable($template)) {
            throw new Exception("“{$template}” not readble as a template file");
        }
        if (!Filesys::copy($template, $dst_file)) {
            throw new Exception("“{$dst_file}” not writable. May be this destination is open in Ms.Word\n" . Log::last());
        }
        // documentURI works for xml loader from file
        // $name = pathinfo($dom->documentURI, PATHINFO_FILENAME);
        $zip = new ZipArchive();
        $zip->open($dst_file);

        // get a default lang from the source TEI, set it in the style.xml
        $xpath = Xt::xpath($dom);
        $entries = $xpath->query("/*/@xml:lang");
        $lang = null;
        foreach ($entries as $node) {
            $lang = $node->value;
        }
        // template is supposed to have a default language
        if ($lang) {
            if (isset(self::ISO639_3char[$lang])) $lang = self::ISO639_3char[$lang];
            $name = 'word/styles.xml';
            $xml = $zip->getFromName($name);
            $xml = preg_replace(
                '@<w:lang[^>]*/>@', 
                '<w:lang w:val="'. $lang . '"/>', 
                $xml
            );
            $zip->deleteName($name);
            $zip->addFromString( $name, $xml);
        }


        $re_clean = array(
            '@(<w:rPr/>|<w:pPr/>)@' => '',
        );

        // extract template to 
        $templPath = tempnam(sys_get_temp_dir(), "teinte_docx_");
        $templPath = "file:///" 
            . str_replace(DIRECTORY_SEPARATOR, "/", $templPath);
        // $this->logger->debug(__METHOD__.' $templPath='.$templPath);

        $xml = Xt::transformToXml(
            Xpack::dir() . '/tei_docx/tei_docx_comments.xsl', 
            $dom,
        );
        $zip->addFromString('word/comments.xml', $xml);

        // generation of word/document.xml needs some links
        // from template, espacially for head and foot page.
        file_put_contents($templPath, $zip->getFromName('word/document.xml'));
        $xml = Xt::transformToXml(
            Xpack::dir() . '/tei_docx/tei_docx.xsl',
            $dom,
            array(
                'templPath' => $templPath,
            )
        );
        $xml = preg_replace(
            array_keys($re_clean), 
            array_values($re_clean), 
            $xml
        );
        $zip->addFromString('word/document.xml', $xml);

        // keep some relations in footnotes from temèplate
        file_put_contents(
            $templPath, 
            $zip->getFromName('word/_rels/document.xml.rels')
        );
        $xml = Xt::transformToXml(
            Xpack::dir() . '/tei_docx/tei_docx_rels.xsl',
            $dom,
            array(
                'templPath' => $templPath,
            )
        );
        $zip->addFromString('word/_rels/document.xml.rels', $xml);


        $xml = Xt::transformToXml(
            Xpack::dir() . '/tei_docx/tei_docx_fn.xsl',
            $dom,
        );
        $xml = preg_replace(
            array_keys($re_clean), 
            array_values($re_clean), 
            $xml
        );
        $zip->addFromString('word/footnotes.xml', $xml);


        $xml = Xt::transformToXml(
            Xpack::dir() . '/tei_docx/tei_docx_fnrels.xsl',
            $dom,
        );
        $zip->addFromString('word/_rels/footnotes.xml.rels', $xml);
        // 
        if (!$zip->close()) {
            Log::error(
                "Tei2docx ERROR writing \033[91m$dst_file\033[0m\nMaybe an app has an handle on this file. Is this docx open in MS.Word or LibreO.Writer?"
            );
        }
    }

}

Tei2docx::init();