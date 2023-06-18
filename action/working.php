<?php declare(strict_types=1);

namespace Oeuvres\Teinte;

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use DirectoryIterator, Exception;
use Psr\Log\{LogLevel};
use Oeuvres\Kit\{FileSys, I18n, Http, Log, LoggerWeb, Route};


Log::setLogger(new LoggerWeb(LogLevel::INFO));

Working::init();
class Working
{
    const WORK_DIR = 'work_dir';
    const _SRC = '_src';
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
        Log::info("{datetime}");
        $dirit = new DirectoryIterator($src_dir);
        // populate with found src files to clean generated
        $found = [];
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
            $found[$name] = $src_file;
            $tei_file = self::$config[self::WORK_DIR] . 'tei/' . $name . '.xml';
            if ($force && file_exists($tei_file)) {
                Log::info('Refaire "' . $name . '"');
            }
            if (!file_exists($tei_file)) {
                // dst_file do not exists, generate
                Log::info('Créer "' . $name . '"');
            }
            else if (filemtime($src_file) <= filemtime($dst_file)) {
                continue;
            }
            else {
                Log::info('Raffraîchir "' . $name . '"');
            }

            
        }
        if (!count($found)) {
            Log::info('Tout est à jour');
        }
        // delete files old files
        $dir_list = [
            'docx' => 'docx', 
            'html' => 'html', 
            'md' => 'md', 
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

                if (isset($found[$name]) && $ext == $dst_ext) continue;
                Log::info('Supprimer "' . $dir_name . '/' .  $fileinfo->getFilename() . '"');
                Filesys::rmdir($fileinfo->getRealPath());
            }
        }
        // zip dirs
    }
}

$main = function() {
    // test if force
    $force = (isset($_GET['force']) && $_GET['force'] = 'majeure');
    Working::crawl($force);
};
?>
