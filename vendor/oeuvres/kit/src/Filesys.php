<?php declare(strict_types=1);

/**
 * Part of Teinte https://github.com/oeuvres/teinte
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */



namespace Oeuvres\Kit;

use ZipArchive;

/**
 * code convention https://www.php-fig.org/psr/psr-12/
 */

class Filesys
{

    /**
     * Normalize a directory filepath with a last /
     */
    static function normdir($dir)
    {
        if (!$dir) return $dir;
        $dir = rtrim(trim($dir), "\\/");
        if (!$dir) {
            return "";
        }
        return $dir . DIRECTORY_SEPARATOR;
    }

    /**
     * Normalize dots in a path that may be relative
     */
    static function pathnorm($path) {
        $path = str_replace('\\', '/', $path);
        $root = ($path[0] === '/') ? '/' : '';

        $segments = explode('/', trim($path, '/'));
        $ret = array();
        foreach($segments as $segment){
            if (($segment == '.') || strlen($segment) === 0) {
                continue;
            }
            if ($segment == '..') {
                array_pop($ret);
            } else {
                array_push($ret, $segment);
            }
        }
        return $root . implode('/', $ret);
    }

    /**
     * Check if a file is writable, if it does not exists
     * go to the parent folder to test if it is possible to create.
     * 
     * @param string  $path
     *
     * @return mixed true if Yes, "message" if not
     */
    public static function writable(string $path):bool
    {
        if (is_writable($path)) return true;
        // if not file exists, go up to parents
        $parent = $path;
        while (!file_exists($parent)) {
            $parent = dirname($parent);
        }
        if (is_link($parent)) {
            Log::warning(I18n::_('Filesys.writable.is_link', $path, $parent));
            return false;
        }
        if (is_writable($parent)) return true;
        if (is_readable($parent)) {
            Log::warning(I18n::_('Filesys.writable.is_readable', $path, $parent, self::owner($parent) ));
            return false;
        }
        return self::readable($parent);
    }

    /**
     * Is a file path absolute ?
     */
    public static function isabs(string $path): bool
    {
        if (!$path) {
            return false;
        }
        // true if file exists
        if (realpath($path) == $path) {
            return true;
        }
        // ./* relpath
        if (strlen($path) == 0 || '.' === $path[0]) {
            return false;
        }
        // url protocol
        if (preg_match('#^file:#', $path)) {
            return true;
        }
        // Windows drive pattern
        if (preg_match('#^[a-zA-Z]:\\\\#', $path)) {
            return true;
        }
        // A path starting with / or \ is absolute
        return ('/' === $path[0] || '\\' === $path[0]);
    }

    /**
     * Human readable bytes
     */
    public static function bytes_human($bytes)
    {
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), [0,0,2,2,3][$i]).['B','kB','MB','GB','TB'][$i];
    }

    /**
     * Get relative path between 2 absolute file path
     */
    public static function relpath(string $from, string $to): string
    {
        // some compatibility fixes for Windows paths
        $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
        $to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
        // normalize paths
        $from = preg_replace('@[\\\\/]+@', '/', $from);
        $to   = preg_replace('@[\\\\/]+@', '/', $to);

        $from     = explode('/', $from);
        $to       = explode('/', $to);
        $relpath  = $to;

        foreach ($from as $depth => $dir) {
            // find first non-matching dir
            if ($dir === $to[$depth]) {
                // ignore this directory
                array_shift($relpath);
            } else {
                // get number of remaining dirs to $from
                $remaining = count($from) - $depth;
                if ($remaining > 1) {
                    // add traversals up to first matching dir
                    $padLength = (count($relpath) + $remaining - 1) * -1;
                    $relpath = array_pad($relpath, $padLength, '..');
                    break;
                } else {
                    $relpath[0] =  $relpath[0];
                }
            }
        }
        $href =  implode('/', $relpath);
        // if path with ../
        // we can have things like galenus/../verbatim/verbatim.css
        // is it safe ? let’s try
        $re = '@\w[^/]*/\.\./@';
        while (preg_match($re, $href)) {
            $href = preg_replace($re, '', $href);
        }
        return $href;
    }

    /**
     * Check existence of a file to read.
     *
     * @return mixed true if Yes, "message" on error
     */
    public static function readable(string $file):bool
    {
        if (is_readable($file)) return true;
        if (is_file($file)) {
            Log::warning(I18n::_('Filesys.readable.is_file', $file));
            return false;
        }
        if (file_exists($file)) {
            Log::warning(I18n::_('Filesys.readable.exists', $file));
            return false;
        }
        Log::warning(I18n::_('Filesys.readable.404', $file));
        return false;
    }
    /**
     * A safe mkdir dealing with rights
     * 
     * @return true if done, false on error (message logged)
     */
    static function mkdir(string $dir):bool
    {
        // nothing done, ok.
        if (is_dir($dir)) {
            return true;
        }
        if (!self::writable($dir)) {
            return false;
        }
        if (!mkdir($dir, 0775, true)) {
            Log::warning(I18n::_('Filesys.mkdir.error', $dir));
            return false;
        }
        // let @, if www-data is not owner but allowed to write
        @chmod($dir, 0775);  
        return true;
    }

    /**
     * Ensure an empty dir with no contents, create it if not exist
     *
     * @return mixed true if done, "message" on error
     */
    static public function cleandir(string $dir):bool
    {
        if (!self::writable($dir)) {
            return false;
        }
        // attempt to create the folder we want empty
        if (!file_exists($dir)) {
            return self::mkdir($dir);
        }
        // rmdir with keep parent
        if (!self::rmdir($dir, true)) {
            return false;
        }
        touch($dir); // change timestamp
        return true;
    }

    /**
     * Recursive deletion of a directory
     * If $keep = true, base directory is kept with its acl
     * Return true if OK (deleted or already deleted).
     * Report is logged in one multi-line message
     */
    static public function rmdir(string $path, bool $keep = false):bool
    {
        // nothing to delete, go away
        if (!file_exists($path)) {
            Log::debug("Path not found, remove impossible:\n\"$path\"");
            return true;
        }
        $log = [];
        self::rm_recurs($path, $log, $keep);
        if (count($log) > 0) {
            Log::warning(implode("\n", $log));
            return false;
        }
        // everything has been OK
        return true;
    }

    /**
     * Private remove recursion
     */
    static private function rm_recurs($path, &$log, $keep = false)
    {
        if (is_link($path) || is_file($path)) {
            // retain warnings for first attempt
            if (!@unlink($path)) {
                // it is reported that php processes can hanfreeze files
                gc_collect_cycles();
                if (!@unlink($path)) {
                    $log[] = "File impossible to delete (handled by a process ?):\n\"$path\"";
                }
            }
            return true;
        }
        if (!($handle = opendir($path))) {
            $log[] = "Dir impossible to open for remove:\n\"$path\"";
            return false;
        }
        $count= count($log);
        while (false !== ($entry = readdir($handle))) {
            if ($entry == "." || $entry == "..") {
                continue;
            }
            $path_entry = $path . DIRECTORY_SEPARATOR . $entry;
            self::rm_recurs($path_entry, $log);
        }
        closedir($handle);
        // if dir not kept, and no more logged error, container could be deleted 
        if (!$keep && count($log) == $count) {
            if (true !== rmdir($path)) {
                $log[] = "Dir empty but impossible to remove:\n\"$path\"";
            }
        }
    }

    /**
     * Copy file, crating needed directories, with logged information
     * if something went wrong 
     */
    public static function copy(
        string $src_file,
        string $dst_file
    ): bool {
        if (!Filesys::mkdir(dirname($dst_file))) {
            return false;
        }
        if (!Filesys::writable($dst_file)) {
            return false;
        }
        if (!copy($src_file, $dst_file)) {
            return false;
        }
        return true;
    }

    /**
     * Recursive copy of folder
     */
    public static function rcopy(
        string $src_dir,
        string $dst_dir
    ) {
        $pref = __CLASS__ . "::" . __FUNCTION__ . "  ";
        $src_dir = rtrim($src_dir, "/\\") . DIRECTORY_SEPARATOR;
        $dst_dir = rtrim($dst_dir, "/\\") . DIRECTORY_SEPARATOR;
        if (true !== ($ret = self::mkdir($dst_dir))) return $ret;
        $dir = opendir($src_dir);
        $log = [];
        while (false !== ($src_name = readdir($dir))) {
            // no copy of hidden files (what about .htaccess ?)
            if ($src_name[0] == '.') {
                continue;
            }
            $src_path = $src_dir . $src_name;
            $dst_path = $dst_dir . $src_name;
            if (is_dir($src_path)) {
                $ret = self::rcopy($src_path, $dst_path);
                if (true !== $ret) $log[] = $ret;
            } else {
                $ret = copy($src_path, $dst_path);
                if (true !== $ret) {
                    $log[] = $pref . "copy failed \"$src_path\" X \"$dst_path\"";
                }
            }
        }
        closedir($dir);
        if (count($log) > 0) return implode("\n", $log);
        // everything has been OK
        return true;
    }

    /**
     * Get contents of an url from different schemes, especially base64 
     * return an array
     * [
     *    "bytes" => [binary data],
     *    "ext" => extension,
     *    "name" => filename without extension (when relevant)
     * ]
     */
    static function loadURL($url, $baseDir = null)
    {
        if (!$url) return null;
        if ($baseDir) $baseDir = ltrim($baseDir, '/\\') . '/';
        $data = [
            "bytes" => null,
            "ext" => null,
            "name" => null,
        ];
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme == 'data') {
            // data:image/png;base64,AAAFBfj42Pj4
            // 0123456789 123456789 123456789
            $col_pos = strpos($url, ';');
            if (substr($url, $col_pos + 1, 6) != 'base64') {
                Log::warning(substr($url, 30) . "… seems not encoded in base64");
                return null;
            }
            $slash_pos = strpos($url, '/');
            $data['ext'] = substr($url, $slash_pos + 1, $col_pos - $slash_pos);
            $base64 = substr($url, strpos($url, ',') + 1);
            $base64 = str_replace( ' ', '+', $base64);
            $data['bytes'] = @base64_decode($base64);
            if ($data['bytes'] === false) {
                $error = error_get_last();
                Log::warning(
                    substr($url, 30) 
                    . "… base64 decoding failed: "
                    . $error['message']
                );
                return null;
            }
            return $data;
        }
        // should work for 
        else {
            $path4name = $url;
            if (in_array($scheme, ['zip'])) {
                $path4name = parse_url($url, PHP_URL_FRAGMENT);
            }
            else if (in_array($scheme, ['http', 'https'])) {
                $path4name = parse_url($url, PHP_URL_PATH);
            }
            else if (self::isabs($url)) {
                // OK
            }
            else { // relative file path ?
                $url = $baseDir . $url;
            }
            $data['bytes'] = @file_get_contents($url);
            if ($data['bytes'] === false) {
                $error = error_get_last();
                Log::warning("$url, load error: " . $error['message']);
                return null;
            }
            $data['ext'] = strtolower(pathinfo($path4name, PATHINFO_EXTENSION));
            $data['name'] = pathinfo($path4name, PATHINFO_FILENAME);
            return $data;
        }
    }


    /**
     * Zip folder to a zip file
     */
    static public function zip(
        string $zipFile,
        string $srcDir
    ) {
        $zip = new ZipArchive();
        if (!file_exists($zipFile)) {
            $zip->open($zipFile, ZIPARCHIVE::CREATE);
        } else {
            $zip->open($zipFile);
        }
        self::zipDir($zip, $srcDir);
        $zip->close();
    }


    /**
     * The recursive method to zip dir
     * start with files (especially for mimetype epub)
     */
    static private function zipDir(
        object $zip,
        string $srcDir,
        string $entryDir = ""
    ) {
        $srcDir = rtrim($srcDir, "/\\") . '/';
        // files
        foreach (array_filter(glob($srcDir . '/*'), 'is_file') as $srcPath) {
            $srcName = basename($srcPath);
            if ($srcName == '.' || $srcName == '..') continue;
            $entryPath = $entryDir . $srcName;
            $zip->addFile($srcPath, $entryPath);
        }
        // dirs
        foreach (glob($srcDir . '/*', GLOB_ONLYDIR) as $srcPath) {
            $srcName = basename($srcPath);
            if ($srcName == '.' || $srcName == '..') continue;
            $entryPath = $entryDir . $srcName;
            $zip->addEmptyDir($entryPath);
            self::zipDir($zip, $srcPath, $entryPath);
        }
    }

    /**
     * Get owner of a file by name
     */
    static public function owner(string $file):?string
    {
        if (self::readable($file)) return null;
        $userinfo = posix_getpwuid(fileowner($file));
        return $userinfo['name'];
    }

}
