<?php declare(strict_types=1);

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use Psr\Log\LogLevel;
use Oeuvres\Kit\{Cliglob, Filesys, Log, Parse, Xt};
use Oeuvres\Kit\Logger\{LoggerCli};
use Oeuvres\Teinte\Format\{Docx, Tei};

class Docx2Tei extends Cliglob {
    private static Docx $docx;
    const SRC_FORMAT = "docx";
    const SRC_EXT = ".docx";
    const DST_FORMAT = "xml";
    const DST_EXT = ".xml";

    /** work */
    static public function cli()
    {
        self::$docx = new Docx();
        Log::setLogger(new LoggerCli(LogLevel::DEBUG));
        self::glob([__CLASS__, 'export']);
    }

    static function export($src_file, $dst_file)
    {
        Log::info($src_file . " > " . $dst_file);
        $pars = [];
        if (isset(self::$options['t'])) $pars['template.xml'] = self::$options['t'];
        self::$docx->open($src_file);
        self::$docx->teiURI($dst_file);
    }
}
if (Cliglob::isCli()) {
    Docx2Tei::cli();
}
