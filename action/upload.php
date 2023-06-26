<?php declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

include_once(__DIR__ . '/inc.php');

use Psr\Log\LogLevel;
use Oeuvres\Kit\{Config, Filesys, Http, I18n, Log, LoggerWeb};
use Oeuvres\Teinte\Format\{Docx, Epub, File, Markdown, Tei, Zip};
use Oeuvres\Teinte\Tei2\{Tei2article};


// supposed required to output logging line by line
header( 'Content-type: text/html; charset=utf-8' );

function cookie($cookie)
{
    $cookie_options = [
        'expires' => time() + (3600), // 1 hour
        // 'path' => '/', local path should be OK
        // 'secure' => false,
        'httponly' => true,
        'samesite' => 'Strict',
    ];
    setcookie(TEINTE, json_encode($cookie), $cookie_options);
}

// output ERRORS to http client
Log::setLogger(new LoggerWeb(LogLevel::ERROR));
// default options for created cookie


// get file
$upload = Http::upload();
// web upload should log more
if (!$upload || !count($upload) && !isset($upload['tmp_name'])) {
    // TODO, better messages here
    http_response_code(400 );
    exit();
}

// make a session dir
$cookie = [];
if (isset($_COOKIE[TEINTE])) {
    $cookie = json_decode($_COOKIE[TEINTE], true);
}
if (!isset($cookie['id'])) {
    $cookie['id'] = uniqid();
}


$temp_dir = Config::get(TEMP_DIR) . $cookie['id'] . "/";
if (!Filesys::mkdir($temp_dir)) {
    // if debug message will be oututed
    Log::error("Impossible de créer le dossier temporaire :" . $temp_dir);
    die();
}

if (!isset($upload['name']) || !$upload['name']) {
    // no original name ? 
    echo I18n::_('upload.noname');
    die();
}

$src_file = $temp_dir .$upload['name'];
if (!move_uploaded_file($upload["tmp_name"], $src_file)) {
    // upload went wrong
    echo I18n::_('upload.nomove', $upload["tmp_name"],  $src_file);
    die();
}
$cookie['src_filename'] = $upload['name'];
$format = File::path2format($upload['name']);

// if zip, loop on entries, shows them
// supported input formats
$formats_in = ['docx', 'epub', 'markdown', 'tei'];
if ($format === "zip") {
    cookie($cookie);
    ob_flush();
    flush();
    $zip = new Zip();
    if (!$zip->load($src_file))  {
        echo Log::last();
        die();
    }
    $entries = $zip->flist($formats_in);
    $n = 0;
    echo "
<table class=\"sortable\">
  <caption>Textes convertibles dans {$upload['name']}</caption>
  <thead>
    <tr>
      <th>#</th>
      <th>clé</th>
      <th>format</th>
      <th>taille</th>
      <th>chemin</th>
    </tr>
  </thead>
  <tbody>\n";
    foreach ($entries as $name => $rows) {
        $class = "";
        if (count($rows) > 1) {
            $class .= 'duplicate';
        }
        foreach ($rows as $f) {
            $n++;
            echo "<tr class=\"$class\">
    <th class=\"n\">$n</th>
    <td class=\"key\">{$name}</td>
    <td class=\"format\">{$f['format']}</td>
    <td class=\"size\">{$f['size']}</td>
    <td class=\"path\">{$f['path']}</td>
  </tr>\n";
        }
    }
    echo "</tbody>
    </table>\n";
    require(__DIR__ . '/zipform.php');
    die();
}

$src_name =  pathinfo($upload['name'], PATHINFO_FILENAME);
$cookie['name'] =  $src_name;

// format compatible TEI

$tei_file = $temp_dir . $src_name . ".xml"; 
$cookie['tei_basename'] = basename($tei_file);
$tei = new Tei();


if ($format === "docx") {
    $docx = new Docx();
    $docx->load($src_file);
    $tei->loadDoc($docx->teiDoc());
}
else if ($format === "tei") {
    $tei->load($src_file);
}
else if ($format === "markdown") {
    $md = new Markdown();
    $md->load($src_file);
    $tei->loadDoc($md->teiDoc());
}
else if ($format === "epub") {
    $epub = new Epub();
    $epub->load($src_file);
    $tei->loadDoc($epub->teiDoc());
}
else {
    // no tei loaded
    echo I18n::_('upload.format404', $upload["name"],  $format);
    die();
}

// information to send to download
cookie($cookie);
ob_flush();
flush();
// put file in temp dir
file_put_contents($tei_file, $tei->tei());
// display html 
echo  $upload['name'] . "<br/>";
echo $tei->toXml('article');

