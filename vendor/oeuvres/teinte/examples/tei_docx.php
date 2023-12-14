<?php declare(strict_types=1);

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use Oeuvres\Teinte\{Tei2docx, TeiExporter};
use Psr\Log\LogLevel;
use Oeuvres\Kit\{Filesys, Log};
use Oeuvres\Kit\Logger\{LoggerCli};

use Oeuvres\Teinte\Format\{Tei};

Log::setLogger(new LoggerCli(LogLevel::DEBUG));
Tei_docx::init();
Tei_docx::cli();

class Tei_docx {
    private static Tei $tei;
    private static $init;
    /**
     * Get an md parser
     */
    static public function init():void
    {
        if (self::$init) return;
        self::$tei = new Tei();
        self::$init = true;
    }

    static public function cli()
    {
    
        $help = '
    Tranform tei files from xml/tei to docx
        php tei_doc.php (options)* (file or glob)+
    
    PARAMETERS
    globs            : + files or globs
    
    OPTIONS
    -d dst_dir       : ? destination directory for generated files
    -t template.docx : ? a docx file as a template
    ';
        // -f          : ? force deletion of destination file (no test of freshness)
        global $argv;
        $shortopts = "";
        $shortopts .= "h"; // help message
        $shortopts .= "f"; // force transformation
        $shortopts .= "d:"; // output directory
        $shortopts .= "t:"; // template file
        $rest_index = null;
        $options = getopt($shortopts, [], $rest_index);
        $pos_args = array_slice($argv, $rest_index);

        if (count($pos_args) < 1) exit($help);
        $dst_dir = "";
        if (isset($options['d'])) {
            $dst_dir = rtrim($options['d'], '\\/') . '/';
            Filesys::mkdir($dst_dir);
        }
        if (isset($options['t'])) {
            self::$tei->template('docx', $options['t']);
        }    
        // loop on arguments to get files of globs
        foreach ($pos_args as $arg) {
            $glob = glob($arg);
            if (count($glob) > 1) {
                Log::info("=== " . $arg . " ===");
            }
            foreach ($glob as $src_file) {
                $src_name = pathinfo($src_file, PATHINFO_FILENAME);
                $dst_file = $dst_dir. $src_name .'.docx';
                if (file_exists($dst_file)) {
                    // test freshness ?
                }
                self::export($src_file, $dst_file);
                /*
                $source->load($docx_file);
                // for debug
                $source->pkg(); // open the docx
                $source->teilike(); // apply a first tei layer
                // file_put_contents($dst_dir. $src_name .'_teilike.xml', $source->xml());
                $source->pcre(); // apply regex, custom re may break XML
                // for debug write this step
                // file_put_contents($dst_dir. $src_name .'_pcre.xml', $source->xml());
                $source->tmpl();
                // finalize with personal xslt
                $xml = Xt::transformToXml(
                    __DIR__ . '/galenusgrc.xsl',
                    $source->dom(),
                    ['filename' => $src_name]
                );
                file_put_contents($dst_file, $xml);
                */
            }
        }
    }
    
    
    
    static function export($src_file, $dst_file)
    {
        Log::info($src_file . " > " . $dst_file);
        self::$tei->open($src_file);
        self::$tei->toURI('docx', $dst_file);
    }
        
}
