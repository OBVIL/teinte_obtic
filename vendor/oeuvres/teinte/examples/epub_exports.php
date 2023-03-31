<?php declare(strict_types=1);

include_once(dirname(__DIR__) . '/vendor/autoload.php');

use Psr\Log\LogLevel;
use Oeuvres\Kit\{Filesys, Log, LoggerCli};
use Oeuvres\Teinte\Format\{Epub};



function help()
{
    $help = '
    Tranform epub files in tei
        php epub_exports.php (-d dst_dir)? "epub_dir/*.epub"+
';
    return $help;
}

function cli()
{
    global $argv;
    Log::setLogger(new LoggerCli(LogLevel::DEBUG));


    $shortopts = "";
    $shortopts .= "d:"; // output directory
    $options = getopt($shortopts, [], $rest_index);
    $count = count($argv);
    // no args, probably not correct
    if ($rest_index >= $count) exit(help());
    $dst_dir = "";
    if (isset($options['d'])) {
        $dst_dir = $options['d'];
        Filesys::mkdir($dst_dir);
    }
    $dst_dir = Filesys::normdir($dst_dir);
    // loop on globs
    for (; $rest_index < $count; $rest_index++) {
        crawl(
            $argv[$rest_index],
            $dst_dir,
        );
    }
}

function crawl($glob, $dst_dir)
{
    $html_dir = rtrim($dst_dir , '\\/') . '_html/';
    Filesys::mkdir($html_dir);
    foreach (glob($glob) as $src_file) {
        Log::info($src_file);
        $dst_name = pathinfo($src_file, PATHINFO_FILENAME);
        $epub = new Epub();
        $epub->load($src_file);
        file_put_contents($html_dir. $dst_name . ".html", $epub->html());
        file_put_contents($dst_dir . $dst_name . ".xml", $epub->tei());
    }
}

cli();