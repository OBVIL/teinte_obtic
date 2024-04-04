<?php declare(strict_types=1);

include_once(dirname(__DIR__ ) . '/vendor/autoload.php');

use Psr\Log\LogLevel;
use Oeuvres\Kit\{Cliglob, Filesys, Log, Parse, Xt};
use Oeuvres\Kit\Logger\{LoggerCli};
use Oeuvres\Teinte\Format\{Docx, Tei};

class Docx2Tei extends Cliglob {
    private static Docx $docx;

    /** work */
    static public function cli()
    {
        // requested destination extension
        self::put('dst_ext', ".xml");
        // doc for help
        self::put('dst_ext', ".xml"); 
        self::put('src_format', "DOCX");
        self::put('dst_format', "XML/TEI");
        self::$docx = new Docx();
        Log::setLogger(new LoggerCli(LogLevel::DEBUG));
        self::glob([__CLASS__, 'export']);
    }

    static function export($src_file, $dst_file)
    {
        Log::info($src_file . " > " . $dst_file);
        $pars = [];
        $pars['template.xml'] = self::get('t', null);
        self::$docx->open($src_file);
        self::$docx->teiURI($dst_file);
    }
}
if (Cliglob::isCli()) {
    Docx2Tei::cli();
}
