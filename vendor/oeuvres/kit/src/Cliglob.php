<?php declare(strict_types=1);
/**
 * Part of Teinte https://github.com/oeuvres/teinte
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Kit;

use Psr\Log\LogLevel;
use Oeuvres\Kit\{Filesys, Log};
use Oeuvres\Kit\Logger\{LoggerCli};

class Cliglob
{
    /** Options */
    private static $options = [];

    /**
     * Get an option
     */
    public static function get(string $name, $default = null)
    {
        if (!isset(self::$options[$name])) {
            return $default;
        }
        return self::$options[$name];
    }

    /**
     * Set an option
     */
    public static function put(string $name, $value):void
    {
        self::$options[$name] = $value;
    }

    /**
     * Set multiple options
     */
    public static function putAll(array $options):void
    {
        self::$options = array_merge(self::$options, $options);
    }

    /**
     * Parse command line arguments and process files
     */
    public static function glob(callable $action)
    {
        global $argv;
        $shortopts = "";
        $shortopts .= "h"; // help message
        $shortopts .= "f"; // force transformation
        $shortopts .= "v"; // verbose messages
        $shortopts .= "d:"; // output directory
        $shortopts .= "t:"; // template file
        $rest_index = null;
        self::putAll(getopt($shortopts, [], $rest_index));
        $pos_args = array_slice($argv, $rest_index);
        if (count($pos_args) < 1) {
            exit(self::help());
        }
        if (isset(self::$options['v'])) {
            Log::setLogger(new LoggerCli(LogLevel::DEBUG));
        }
        else {
            Log::setLogger(new LoggerCli(LogLevel::INFO));
        }
        // loop on arguments to get files of globs
        foreach ($pos_args as $arg) {
            $glob = glob($arg);
            if (count($glob) > 1) {
                Log::info("=== " . $arg . " ===");
            }
            foreach ($glob as $src_file) {
                if (is_dir($src_file)) continue;
                if (!Filesys::readable($src_file)) {
                    continue;
                }
                $dst_file = self::destination($src_file);
                // test freshness
                if (isset((self::$options['f']))); // force
                else if (!file_exists($dst_file)); // destination not exists
                else if (filemtime($src_file) < filemtime($dst_file)) continue;
                $action($src_file, $dst_file);
            }
        }
    }

    /**
     * Test if script 
     */
    static public function isCli()
    {
        global $argv;
        // here, __FILE__ = Cliglob.php
        list($called) = get_included_files();
        return (
            php_sapi_name() == 'cli'
            && isset($argv[0])
            && realpath($argv[0]) == realpath($called)
        );
    }

    /**
     * An help message to display
     */
    static function help(): string
    {
        list($called) = get_included_files();
        $help = "
Tranform " . self::get('src_format')." files in ". self::get('dst_format') ."
    php ".basename($called)." (options)* \"src_dir/*" . self::get('src_ext') . "\"

PARAMETERS
globs           : + files or globs

OPTIONS
-h              : ? print this help
-f              : ? force deletion of destination file (no test of freshness)
-d dst_dir      : ? destination directory for generated files
-t template" . self::get('dst_ext') . " : * template files
-v              : ? verbose mode
";
        return $help;
    }

    /**
     * For simple export, default destination file
     */
    static public function destination($src_file): string
    {
        $dst_dir = Filesys::normdir(self::get('d', dirname($src_file) . DIRECTORY_SEPARATOR));
        $dst_name =  pathinfo($src_file, PATHINFO_FILENAME);
        $dst_file = $dst_dir . self::get('dst_prefix', '') . $dst_name . self::get('dst_ext');
        return $dst_file;

    }
}