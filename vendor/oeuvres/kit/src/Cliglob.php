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

abstract class Cliglob
{
    /** Options */
    public static $options;
    /** The source format name */
    const SRC_FORMAT = self::SRC_FORMAT;
    /** The prefered source format name */
    const SRC_EXT = self::SRC_EXT;
    /** The destination format name */
    const DST_FORMAT = self::DST_FORMAT;
    /** A destination extension for generated files */
    const DST_EXT = self::DST_EXT;
    /** An optional destination prefix */
    const DST_PREFIX = "";

    /**
     * Parse command line arguments and process files
     */
    public static function glob($action)
    {
        global $argv;
        $shortopts = "";
        $shortopts .= "h"; // help message
        $shortopts .= "f"; // force transformation
        $shortopts .= "v"; // verbose messages
        $shortopts .= "d:"; // output directory
        $shortopts .= "t:"; // template file
        $rest_index = null;
        static::$options = getopt($shortopts, [], $rest_index);
        $pos_args = array_slice($argv, $rest_index);
        if (count($pos_args) < 1) {
            exit(static::help());
        }
        if (isset(static::$options['v'])) {
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
                $dst_file = static::destination($src_file);
                // test freshness
                if (isset(static::$options['f'])); // force
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
Tranform ".static::SRC_FORMAT." files in ".static::DST_FORMAT."
    php ".basename($called)." (options)* \"src_dir/*".static::SRC_EXT."\"

PARAMETERS
globs           : + files or globs

OPTIONS
-h              : ? print this help
-f              : ? force deletion of destination file (no test of freshness)
-d dst_dir      : ? destination directory for generated files
-t template".static::DST_EXT." : * template files
-v              : ? verbose mode
";
        return $help;
    }

    /**
     * For simple export, default destination file
     */
    static public function destination($src_file): string
    {
        if (!isset(static::$options['d'])) {
            $dst_dir = dirname($src_file) . DIRECTORY_SEPARATOR;
        } else {
            $dst_dir = Filesys::normdir(static::$options['d']);
        }
        $dst_name =  pathinfo($src_file, PATHINFO_FILENAME);
        $dst_file = $dst_dir . static::DST_PREFIX . $dst_name . static::DST_EXT;
        return $dst_file;

    }
}