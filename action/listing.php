<?php declare(strict_types=1);

namespace Oeuvres\Teinte;

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use DirectoryIterator, Exception;
use Psr\Log\{LogLevel};
use Oeuvres\Kit\{FileSys, I18n, Http, Log, LoggerWeb, Route};
use Oeuvres\Teinte\Format\{Docx, Epub, File, Markdown, Tei};


Log::setLogger(new LoggerWeb(LogLevel::INFO));
Listing::init();


class Listing
{
    const WORK_DIR = 'work_dir';
    const WORK_HREF = 'work_href';
    const _SRC = '0_sources';
    /** Avoid multiple initialisation */
    static private bool $init = false;
    /** Configuration */
    static private $config=[];
    /** Export formats */
    static private $exports = [
        'docx',
        'html',
        'markdown',
        'tei',
    ];
    /** Supported sources */
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
        self::$config[self::WORK_HREF] = "work/";
        // this code should be in one place
        do {
            $config_file = dirname(__DIR__).'/config.php';
            if (!is_readable($config_file)) break;
            $config = require($config_file);
            if (!isset($config[Work::DIR])) break;
            self::$config = array_merge(self::$config, $config);
        } while(false);
        self::$config[self::WORK_DIR] = rtrim(self::$config[self::WORK_DIR], '/\\.') . '/';

        $src_dir = self::$config[self::WORK_DIR] . self::_SRC;
        self::$init = true;
    }
    /**
     * Loop on simple file in workdir
     */
    static function crawl()
    {
        $ths = '';
        foreach (self::$exports as $format ) {
            $ths .= "      <th class=\"format\">$format</th>\n";
        }
        echo '
<table>
  <thead>
    <tr>
      <th>Source</th>
' . $ths . '
    </tr>
  </thead>
  <tbody>
';
        // zip générés
        echo '
    <tr>
      <td>Exports zippés, par format</td>
        ';
        foreach (self::$exports as $format ) {
            $href = self::$config[self::WORK_HREF];
            echo "<td><a href=\"$href/teinte_$format.zip\"><img width=\"48\" src=\"theme/icon_$format.svg\"/></a></td>\n";
        }
        echo '
    </tr>
    <tr>
        <td> </td>
    </tr>
';

        $src_dir = self::$config[self::WORK_DIR] . self::_SRC;

        $dirit = new DirectoryIterator($src_dir);
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
            echo "    <tr>\n";
            $href = self::$config[self::WORK_HREF]
                  . self::_SRC . '/' . $fileinfo->getBasename();
            ;
            $format = File::path2format($fileinfo->getBasename());
            echo "      <td><a target=\"_blank\" href=\"$href\"><img width=\"48\" src=\"theme/icon_$format.svg\"/>" . $fileinfo->getBasename() . "</a></td>\n";


            $src_name = $fileinfo->getBasename(".$ext");
            foreach (self::$exports as $format ) {
                $dst_filename = $src_name . '.' . File::format2ext($format);
                $dst_file = self::$config[self::WORK_DIR] . $format . '/' 
                . $dst_filename;
                if (!file_exists($dst_file)) continue;
                $href = self::$config[self::WORK_HREF] .  $format . '/' 
                . $dst_filename;

                echo "<td><a target=\"_blank\" href=\"$href\"><img width=\"48\" src=\"theme/icon_$format.svg\"/></a></td>\n";
            }

            echo "    </tr>\n";
        }
        echo '
  </tbody>
</table>
';
    }
}

$main = function() {
    Listing::crawl();
};

?>
