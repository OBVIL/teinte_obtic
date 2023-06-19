<?php declare(strict_types=1);

namespace Oeuvres\Teinte;

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use DirectoryIterator, Exception;
use Psr\Log\{LogLevel};
use Oeuvres\Kit\{FileSys, I18n, Http, Log, LoggerWeb, Route};
use Oeuvres\Teinte\Format\{Docx, Epub, File, Markdown, Tei};


Log::setLogger(new LoggerWeb(LogLevel::INFO));
Working::init();


class Working
{
    const WORK_DIR = 'work_dir';
    const _SRC = '0_sources';
    /** Avoid multiple initialisation */
    static private bool $init = false;
    /** Configuration */
    static private $config=[];
    /** Export formats */
    static private $src_ext = [
        'docx' => true,
        'epub' => true,
        'md' => true,
        'tei' => true,
        'txt' => true,
        'xml' => true,
    ];
        /**
     * Inialize static variables
     */
    static function init(): void
    {              
        if (self::$init) return;
        self::$config[self::WORK_DIR] = dirname(__DIR__) . "/work/";
        // try external config file
        do {
            $config_file = dirname(__DIR__).'/config.php';
            if (!is_readable($config_file)) break;
            $config = require($config_file);
            if (!isset($config[Work::DIR])) break;
            // try to create dir and alert dev if it doesn’t work
            if (! Filesys::writable($config[Work::DIR])) {
                exit("[work.php] Configuration problem. Folder not writable: " . $config[Work::DIR]);
            }
            self::$config = array_merge(self::$config, $config);
        } while(false);
        self::$config[self::WORK_DIR] = rtrim(self::$config[self::WORK_DIR], '/\\.') . '/';
        $src_dir = self::$config[self::WORK_DIR] . self::_SRC;
        if (!Filesys::mkdir($src_dir)) {
                exit("[work.php] Installation problem. Folder not writable: " . $src_dir);
        }
        self::$init = true;
    }
    /**
     * Loop on simple file in workdir
     */
    static function crawl($force = false)
    {
        $src_dir = self::$config[self::WORK_DIR] . self::_SRC;
        Log::info("{datetime} force=" . var_export($force, true) );
        $dirit = new DirectoryIterator($src_dir);
        // populate with found src files to clean generated
        $todo = [];
        $src_list = [];
        foreach ($dirit as $fileinfo) {
            if($fileinfo->isDot()) {
                continue;
            }
            if ($fileinfo->isDir()) {
                continue;
            }
            $ext = strtolower($fileinfo->getExtension());
            // not supported file
            if (!isset(self::$src_ext[$ext])) continue;
            $name = $fileinfo->getBasename(".$ext");
            $src_file = $fileinfo->getRealPath();
            // for all files, needed for cleaning
            $src_list[$name] = $src_file;

            $tei_file = self::$config[self::WORK_DIR] . 'tei/' . $name . '.xml';
            if ($force && file_exists($tei_file)) {
                Log::info('Forcer, "' . $fileinfo->getBasename() . '"');
                $todo[$name] = $src_file;
            }
            if (!file_exists($tei_file)) {
                // dst_file do not exists, generate
                Log::info('Nouveau, "' . $fileinfo->getBasename() . '"');
            }
            else if (filemtime($src_file) <= filemtime($tei_file)) {
                continue;
            }
            else {
                Log::info('Raffraîchir, "' . $fileinfo->getBasename() . '"');
            }
            // only for files to work on
            $todo[$name] = $src_file;
        }
        // delete old files in dirs
        $dir_list = [
            'docx' => 'docx', 
            'html' => 'html', 
            'markdown' => 'md', 
            'tei' => 'xml'
        ];
        foreach ($dir_list as $dir_name => $dst_ext) {
            $dir_path = self::$config[self::WORK_DIR] . $dir_name .'/';
            if (!is_dir($dir_path)) continue;
            $dirit = new DirectoryIterator($dir_path);
            foreach ($dirit as $fileinfo) {
                if($fileinfo->isDot()) {
                    continue;
                }
                $ext = $fileinfo->getExtension();
                $name = $fileinfo->getBasename('.' . $fileinfo->getExtension());
                if (isset($src_list[$name]) && $ext == $dst_ext) continue;
                Log::info('Supprimer "' . $dir_name . '/' .  $fileinfo->getFilename() . '"');
                Filesys::rmdir($fileinfo->getRealPath());
            }
        }
        if (!count($todo)) {
            Log::info('Tout est à jour');
            return;
        }
        // loop on the todo list to produce tei
        $tei_dir = self::$config[self::WORK_DIR] . 'tei/';
        $tei = new Tei();
        $docx = new Docx();
        $md = new Markdown();
        $epub = new Epub();
        foreach ($todo as $src_name => $src_file) {
            $format = File::path2format($src_file);
            Log::info(' -- Chargement de "' . basename($src_file) . '" [' . $format . ']');
            $ext = strtolower(pathinfo($src_file, PATHINFO_EXTENSION));
            $tei_file = $tei_dir . $src_name . '.xml';
            try {
                if ($format === "docx") {
                    // check if docx ?
                    $docx->load($src_file);
                    $tei->loadDoc($docx->teiDoc());
                }
                else if ($format === "tei") {
                    $tei->load($src_file);
                }
                else if ($format === "markdown") {
                    $md->load($src_file);
                    $tei->loadDoc($md->teiDoc());
                }
                else if ($format === "epub") {
                    $epub->load($src_file);
                    $tei->loadDoc($epub->teiDoc());
                }    
            }
            catch (Exception $e) {
                Log::error($e->getMessage());
                continue;
            }
            file_put_contents($tei_file, $tei->tei());
            // transform tei in requested formats and dir
            foreach (['docx', 'html', 'markdown'] as $format) {
                $dst_file = self::$config[self::WORK_DIR] . $format . '/' 
                    . $src_name . '.' . File::format2ext($format);
                try {
                    $tei->toUri($format, $dst_file);
                }
                catch (Exception $e) {
                    Log::error($e->getMessage());
                    continue;
                }
            }
            flush();
        }
        // zip dirs
        foreach (['docx', 'html', 'markdown', 'tei'] as $format) {
            Filesys::zip(
                self::$config[self::WORK_DIR] . "teinte_" . $format . ".zip",
                self::$config[self::WORK_DIR] . $format
            );
        }
        Log::info('Fini');
        flush();
    }
}

$main = function() {
    // test if force
    $force = (isset($_REQUEST['force']) && $_REQUEST['force']);
    Working::crawl($force);
};

?>
