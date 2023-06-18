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
    /** the docx transformer, may have templates configured */
    static $docx;

    public static function help()
    {
        $help = '
Tranform docx files in tei
    php docx_tei.php (-d dst_dir)? "work/*.doc"+
';
        return $help;
    }

    public static function cli()
    {
        global $argv;
        Log::setLogger(new LoggerCli(LogLevel::DEBUG));
        $shortopts = '';
        $shortopts .= "d:"; // output directory
        $shortopts .= "t:"; // todo, set a template
        $shortopts .= "h"; // help
        $optindex = 1;
        $options = getopt($shortopts, [], $optindex);
        $count = count($argv);
        // no args, probably not correct
        if ($optindex >= $count) exit(self::help());
        if (isset($options['h']) && $options['h']) {
            exit(self::help());
        }
        $dst_dir = null;
        if (isset($options['d'])) {
            $dst_dir = $options['d'];
            $dst_dir = Filesys::normdir($dst_dir);
            Filesys::mkdir($dst_dir);
        }
        // todo template
        self::$docx = new Docx();

        // loop on globs
        for ( ; $optindex < $count; $optindex++) {
            self::crawl(
                $argv[$optindex],
                $dst_dir,
            );
        }
    }

    public static function crawl($glob, $dst_dir = null)
    {
        foreach (glob($glob) as $src_file) {
            $dst_name = pathinfo($src_file, PATHINFO_FILENAME);
            if ($dst_dir === null) {
                $dst_file = Filesys::normdir(dirname($rc_file)) . $dst_name . ".xml";
            }
            else {
                $dst_file = Filesys::normdir($dst_dir) . $dst_name . ".xml";
            }
            self::transform($src_file, $dst_file);
        }
    }



    static function transform($docx_file, $tei_file)
    {
        Log::info($docx_file . " > " . $tei_file);
        self::$docx->load($docx_file);
        self::$docx->pkg(); // open the docx
        self::$docx->teilike(); // apply a first tei layer
        // for debug
        file_put_contents($tei_file .'_teilike.xml', self::$docx->tei());

        self::$docx->pcre(); // apply regex, custom re may break XML
        // for debug write this step
        file_put_contents($tei_file .'_pcre.xml', self::$docx->tei());
        self::$docx->tmpl();

        // project images
        $name = pathinfo($tei_file, PATHINFO_FILENAME);
        list($name) = explode('_', $name);
        $href_prefix = $name . '/' . $name . '_ill';
        self::$docx->images($tei_file, $href_prefix);
        file_put_contents($tei_file, self::$docx->tei());
    }

}
Teinte::cli();