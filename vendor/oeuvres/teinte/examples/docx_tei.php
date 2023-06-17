<?php declare(strict_types=1);

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use Psr\Log\LogLevel;
use Oeuvres\Kit\{Filesys, Log, LoggerCli, Xt};
use Oeuvres\Teinte\Format\{Docx};
use Oeuvres\Xsl\{Xpack};


class Teinte
{
    /** where generated files are projected */
    static $tei_dir;
    /** the docx transformer */
    static $docx;

    public static function cli()
    {
        global $argv;
        Log::setLogger(new LoggerCli(LogLevel::DEBUG));
        if (!isset($argv[1])) {
            die("usage: php docx_tei.php work/*.docx\n");
        }
        // drop $argv[0], $argv[1…] should be file
        array_shift($argv);
        self::$docx = new Docx();
        // local xml template
        // self::$docx->user_template(__DIR__ . '/tmpl.xml');

        // loop on arguments to get files of globs
        foreach ($argv as $glob) {
            foreach (glob($glob) as $docx_file) {
                $src_name = pathinfo($docx_file, PATHINFO_FILENAME);
                $tei_file = dirname($docx_file) . '/' . $src_name . '.xml';
                if (file_exists($tei_file)) {
                    Log::warning("File already exists: " . $tei_file);
                }
                self::transform($docx_file, $tei_file);
            }
        }
                
    }


    static function transform($docx_file, $tei_file)
    {
        Log::info($docx_file . " > " . $tei_file);
        self::$docx->load($docx_file);
        self::$docx->pkg(); // open the docx
        self::$docx->teilike(); // apply a first tei layer
        // for debug
        file_put_contents($tei_file .'_teilike.xml', self::$docx->xml());

        self::$docx->pcre(); // apply regex, custom re may break XML
        // for debug write this step
        file_put_contents($tei_file .'_pcre.xml', self::$docx->xml());
        self::$docx->tmpl();

        // project images
        $name = pathinfo($tei_file, PATHINFO_FILENAME);
        list($name) = explode('_', $name);
        $href_prefix = $name . '/' . $name . '_ill';
        self::$docx->images($tei_file, $href_prefix);
        file_put_contents($tei_file, self::$docx->xml());
    }

}
Teinte::cli();