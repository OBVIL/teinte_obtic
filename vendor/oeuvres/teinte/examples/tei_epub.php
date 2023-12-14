<?php declare(strict_types=1);

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use Oeuvres\Kit\{Cliglob, Log};
use Oeuvres\Kit\Logger\{LoggerCli};

use Oeuvres\Teinte\Format\{Tei};

class Tei_epub extends Cliglob {
    private static Tei $tei;
    const SRC_FORMAT = "tei";
    const SRC_EXT = ".xml";
    const DST_FORMAT = "epub";
    const DST_EXT = ".epub";

    /** work */
    static public function cli()
    {
        self::$tei = new Tei();
        self::glob([__CLASS__, 'export']);
    }

    static function export($src_file, $dst_file)
    {
        Log::info($src_file . " > " . $dst_file);
        self::$tei->open($src_file);
        $template = null;
        if (isset(self::$options['t'])) {
            if (!is_array(self::$options['t'])) self::$options['t'] = [self::$options['t']];
            foreach (self::$options['t'] as $t) {
                $ext = pathinfo($t, PATHINFO_EXTENSION);
                echo $t." ".$ext;
                if ($ext != 'epub') continue;
                $template = $t;
                break;
            }
        }
        self::$tei->toURI(
            'epub', 
            $dst_file,
            [
                'template.epub' => $template,
            ]
        );
    }
        
}
if (Tei_epub::isCli()) {
    Tei_epub::cli();
}
