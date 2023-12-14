<?php declare(strict_types=1);

namespace Oeuvres\Teinte;

include_once(__DIR__ . '/inc.php');


use DirectoryIterator, Exception;
use Psr\Log\{LogLevel};
use Oeuvres\Kit\{Config, FileSys, I18n, Http, Log, Route};
use Oeuvres\Kit\Logger\{LoggerWeb};
use Oeuvres\Teinte\Format\{Docx, Epub, File, Markdown, Tei, Zip};


Log::setLogger(new LoggerWeb(LogLevel::INFO));
Zipwork::init();


class Zipwork
{
    /** Avoid multiple initialisation */
    static private bool $init = false;
    /** list of supported formats for export */
    const EXPORTS = [
        'docx',
        'html',
        'markdown',
        'tei',
    ];
    const IMPORTS = [
        'docx',
        'epub',
        'markdown',
        'tei',
    ];
    /**
     * Inialize static variables
     */
    static function init(): void
    {              
        if (self::$init) return;
        self::$init = true;
    }
    /**
     * Loop on simple file in workdir
     */
    static function zipcrawl($src_zip, $dst_format)
    {
        // loop on zip files
        // transform them to TEI in files (if images)
        // loop on TEI files ans transform them in $format 

        $zip = new Zip();
        if (!$zip->open($src_zip))  {
            Log::error(Log::last());
            return false;
        }
        // loop on the todo list to produce tei
        $name = pathinfo($src_zip, PATHINFO_FILENAME);
        $zip_dir = dirname($src_zip) . '/'. $name . '/';
        Filesys::cleandir($zip_dir);
        $dst_dir = dirname($src_zip) . '/'. $name . '_' . $dst_format . '/';
        if (!Filesys::cleandir($dst_dir)) {
            Log::error(Log::last());
            return false;
        }
        // Extract all files of the zip (in case of linked images)
        $zip->zip()->extractTo($zip_dir);

        $tei = new Tei();
        $docx = new Docx();
        $md = new Markdown();
        $epub = new Epub();

        // loop on entries in zip_dir by a 
        $todo = $zip->flist(self::IMPORTS);
        foreach ($todo as $src_name => $entries) {
            $path = $entries[0]['path'];
            $src_file = $zip_dir . $path;
            $src_format = File::path2format($src_file);
            $tei_file = dirname($src_file) . '/' . $src_name . '.xml';
            $dst_file = $dst_dir . $src_name . '.' . File::format2ext($dst_format);
            Log::info($path . " > " . $src_name . '.' . File::format2ext($dst_format));
            try {
                if ($src_format === "tei") {
                    $tei->open($src_file);
                }
                else if ($src_format === "docx") {
                    // check if docx ?
                    $docx->open($src_file);
                    $tei->loadDOM($docx->teiDOM());
                    file_put_contents($tei_file, $tei->teiXML());
                }
                else if ($src_format === "markdown") {
                    $md->open($src_file);
                    $tei->loadDOM($md->teiDOM());
                    file_put_contents($tei_file, $tei->teiXML());
                }
                else if ($src_format === "epub") {
                    $epub->open($src_file);
                    $tei->loadDOM($epub->teiDOM());
                    file_put_contents($tei_file, $tei->teiXML());
                }
            }
            catch (Exception $e) {
                Log::error($e->getMessage());
                continue;
            }
            // transform tei in requested format
            try {
                $tei->toUri($dst_format, $dst_file);
            }
            catch (Exception $e) {
                Log::error($e->getMessage());
                continue;
            }
            flush();
        }
        // zip dst_dir
        $dst_zip = rtrim($dst_dir, '\\/') . ".zip";
        Filesys::zip($dst_zip, $dst_dir);
        Log::info('Fini');
        $filename = basename($dst_zip);
        echo "<p><a href=\"read/$filename\">$filename</a></p>";
        flush();
        return $dst_zip;
    }
}

$main = function() {
    if (!isset($_COOKIE["teinte"])) {
        Log::error(I18n::_('download.nofile'));
        return false;
    }
    $cookie = json_decode($_COOKIE["teinte"], true);
    if (!isset($cookie['id'])) {
        Log::error(I18n::_('download.nofile'));
        return false;
    }

    if (!isset($cookie['src_filename']) || !$cookie['src_filename']) {
        Log::error(I18n::_('download.nofile'));
        return false;
    }
    $src_zip = Config::get(TEMP_DIR) . $cookie['id'] . "/" . $cookie['src_filename'];

    if (!Filesys::readable($src_zip)) {
        Log::error(I18n::_("Fichier {$cookie['src_filename']}. Est-ce bien ce que vous avez téléchargé ?"));
        return false;
    }

    foreach (Zipwork::EXPORTS as $format) {
        if (isset($_REQUEST[$format])) break;
        $format = null;
    }
    if (!$format) {
        Log::error(I18n::_('ERROR_NO_FORMAT'));
        return false;
    }
    Zipwork::zipcrawl($src_zip, $format);
};

?>
