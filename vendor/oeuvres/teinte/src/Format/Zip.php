<?php declare(strict_types=1);
/**
 * Part of Teinte https://github.com/oeuvres/teinte_php
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Teinte\Format;

use Exception, ZipArchive;
use Oeuvres\Kit\{Check, Filesys, I18n, Log};
// required extension
Check::extension('zip');




/**
 * A teiDOM exporter.
 */
class Zip extends File
{
    /** zip object opened */
    protected ?ZipArchive $zip = null;

    /**
     * Open zip archive with tests
     */
    public function open(string $file, ?int $flags = 0): bool
    {
        if (!parent::open($file)) {
            return false;
        }
        $this->zip = new ZipArchive();
        if (($code = $this->zip->open($file, $flags)) !== TRUE) {
            Log::warning(self::message($code, $file));
            return false;
        }
        return true;
    }

    static public function message(int $code, string $file): string
    {
        if ($code == ZipArchive::ER_EXISTS) return I18n::_('ZipArchive::ER_EXISTS', $file);
        else if ($code == ZipArchive::ER_INCONS) return I18n::_('ZipArchive::ER_INCONS', $file);
        else if ($code == ZipArchive::ER_INVAL) return I18n::_('ZipArchive::ER_INVAL', $file);
        else if ($code == ZipArchive::ER_MEMORY) return I18n::_('ZipArchive::ER_MEMORY', $file);
        else if ($code == ZipArchive::ER_NOENT) return I18n::_('ZipArchive::ER_NOENT', $file);
        else if ($code == ZipArchive::ER_NOZIP) return I18n::_('ZipArchive::ER_NOZIP', $file);
        else if ($code == ZipArchive::ER_OPEN) return I18n::_('ZipArchive::ER_OPEN', $file);
        else if ($code == ZipArchive::ER_READ) return I18n::_('ZipArchive::ER_READ', $file);
        else if ($code == ZipArchive::ER_SEEK) return I18n::_('ZipArchive::ER_SEAK', $file);
        else return I18n::_("“%s”, load error", $file);
    }

    /**
     * Close zip archive
     */
    public function close()
    {
        return $this->zip->close();
    }


    /**
     * Return zip object
     */
    public function zip():?ZipArchive
    {
        return $this->zip;
    }

    /**
     * Try to get entry, log nicely if error,
     */
    public function get(string $name):?string
    {
        $name = Filesys::pathnorm($name);
        if (false === $this->zip->statName($name)) {
            Log::warning(I18n::_('Zip.404', $this->file, $name));
            return null;
        }
        // check if entry is empty ?
        return $this->zip->getFromName($name);

    }

    /**
     * Put content (normalize path), return previous content if available,
     * or "" if no content, or null on error
     */
    public function put(string $name, string $content)
    {
        $name = ltrim(Filesys::pathnorm($name), '/');
        $ret = "";
        if (true === $this->zip->statName($name)) {
            $ret = $this->zip->getFromName($name);
            $this->zip->deleteName($name); // needed ?
        }
        if (!$this->zip->addFromString($name, $content)) {
            Log::warning(I18n::_('Zip.writefail', $this->file, $name, $this->zip->getStatusString()));
            return false;
        }
        return $ret;
    }

    /**
     * filtered list of entries, by formats
     */
    public function flist($formats = [])
    {
        $formats = array_flip($formats);
        // list entries
        $ls = [];
        for($i = 0, $num = $this->zip->numFiles; $i < $num; $i++) 
        {
            $stat = $this->zip->statIndex($i);
            $format = File::path2format($stat['name']);
            if (!isset($formats[$format])) continue;


            $rec = [];
            $rec['path'] = $stat['name'];
            $pathinfo = pathinfo($rec['path']);
            $name = $pathinfo['filename'];
            // group files by key
            if (!isset($ls[$name])) $ls[$name] = [];
            $rec['name'] = $name;
            $rec['format'] = $format;
            $rec['ext'] = ltrim($pathinfo['filename'], '.');
            $rec['bytes'] = $stat['size'];
            $rec['size'] = Filesys::bytes_human($rec['bytes']);
            $ls[$name][] = $rec;
        }
        ksort($ls);
        return $ls;
    }

    /**
     * Check if a zip file contains an entry by regex pattern
     * Extract it to a dest dir.
     * Ex : check if an epub collection contains a kind of entry
     * and show it
     */
    static function select($file, $pattern, $dst_dir)
    {
        echo $file;
        $epub_name = pathinfo($file, PATHINFO_FILENAME);
        $zip = new ZipArchive();
        if (($err = $zip->open($file)) !== TRUE) {
            // http://php.net/manual/fr/ziparchive.open.php
            if ($err == ZipArchive::ER_NOZIP) {
                // log
                // $this->log(E_USER_ERROR, $this->_basename . " is not a zip file");
                return;
            }
            // $this->log(E_USER_ERROR, $this->_basename . " impossible ton open");
            return;
        }
        $found = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $zip_entry = $zip->getNameIndex($i);
            $basename = basename($zip_entry);
            if (!preg_match($pattern, $basename)) continue;
            $dst_file = $dst_dir . '/' . $epub_name . '_' . basename($zip_entry);
            $cont = $zip->getFromName($zip_entry);
            file_put_contents($dst_file, $cont);
            $found= true;
        }
        $zip->close();
        if (!$found) echo " 404";
        echo "\n";
    }


}

