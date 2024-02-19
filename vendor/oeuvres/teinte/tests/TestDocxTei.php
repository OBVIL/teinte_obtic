<?php declare(strict_types=1);
include_once(dirname(__DIR__) . '/vendor/autoload.php');
use PHPUnit\Framework\TestCase;

use Psr\Log\LogLevel;
use Oeuvres\Kit\{Cliglob, Filesys, Log, Parse, Xt};
use Oeuvres\Kit\Logger\{LoggerCli};
use Oeuvres\Teinte\Format\{Docx, Tei};

class TestDocxTei {
    private static Docx $docx;

    /** work */
    static public function cli()
    {
        self::$docx = new Docx();
        Cliglob::putAll([
            'src_format' => 'DOCX',
            'src_ext' => 'docx',
            'dst_format' => 'TEI',
            'dst_ext' => 'xml',
        ]);
        Log::setLogger(new LoggerCli(LogLevel::DEBUG));
        Cliglob::glob([__CLASS__, 'export']);
    }

    static function export($src_file, $dst_file)
    {
        Log::info($src_file . " > " . $dst_file);
        $pars = [];
        if (Cliglob::get('t')) $pars['template.xml'] = Cliglob::get('t');
        self::$docx->open($src_file);
        // self::$docx->teiURI($dst_file);
        // debug
        self::$docx->pkg();
        self::$docx->teilike();
        file_put_contents($dst_file .'_teilike.xml', self::$docx->teiXML());
        self::$docx->pcre(); // apply regex, custom re may break XML
        file_put_contents($dst_file .'_pcre.xml', self::$docx->teiXML());
        self::$docx->tmpl();
        file_put_contents($dst_file, self::$docx->teiXML());
    }
}
if (Cliglob::isCli()) {
    TestDocxTei::cli();
}
