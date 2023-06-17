<?php declare(strict_types=1);

namespace Oeuvres\Teinte;

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use Psr\Log\{LogLevel};
use Oeuvres\Kit\{Route, I18n, Http, Log, LoggerWeb};


Log::setLogger(new LoggerWeb(LogLevel::INFO));


class Working
{
    const WORK_DIR = 'work_dir';
    /** Avoid multiple initialisation */
    static private bool $init = false;
    /** Configuration */
    static private $config=[];
    /** Export formats */
    static private $formats = [
        'docx',
        'html',
        'tei',
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
        if (!Filesys::mkdir(self::$config[self::WORK_DIR])) {
                exit("[work.php] Installation problem. Folder not writable: " . $config[Work::DIR]);
        }
        self::$init = true;
    }
    /**
     * Loop on simple file in workdir
     */
    static function crawl()
    {
        $dir = new DirectoryIterator(self::$config[self::WORK_DIR]);
        foreach ($dir as $fileinfo) {
            Log::info($fileinfo);
        }
    }
}

?>
<h1>Heuu Là !</h1>
