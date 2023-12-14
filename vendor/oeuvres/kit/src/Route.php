<?php declare(strict_types=1);
/**
 * Part of Teinte https://github.com/oeuvres/teinte
 * MIT License https://opensource.org/licenses/mit-license.php
 * Copyright (c) 2022 frederic.Glorieux@fictif.org
 * Copyright (c) 2013 Frederic.Glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 Frederic.Glorieux@fictif.org
 * Copyright (c) 2010 Frederic.Glorieux@fictif.org
 *                    & École nationale des chartes
 */

/**
 * A simple router designed for full control on URLs,
 * and efficiency. Let work whot wants to work as soon
 * as possible, try to not retain bytes flow.
 */

namespace Oeuvres\Kit;

use Psr\Log\{LogLevel};
use Oeuvres\Kit\{I18n, Log};
use Exception;

Route::init();

class Route {
    /** root directory of the app when outside site */
    static private $lib_dir;
    /** Href to app resources */
    static private $lib_href;
    /** Home dir where is the index.php answering */
    static private $home_dir;
    /** Home href for routing */
    static private $home_href;
    /** Default php template */
    static private $tmpl_file;
    /** Template applied to the content */
    static private ?string $template = null; 
    /** Html content to include, may be to parse */
    static private $html;
    /** Contents captured */
    static private $cont = '';
    /** A string or a callable for main contents to include */
    static private $main;
    /** A string or a callable for a title */
    static private $title;
    /** A string or a callable for meta in html <head> */
    static private $meta;
    /** Path relative to the root app */
    static private $url_request;
    /** Split of url parts */
    static $url_parts;
    /** The resource to deliver */
    private static $resource;
    /** Has a routage been done ? */
    static $routed;
    /** A read/write set of key:value for communication between de Route users */
    static private $atts = [];

    /**
     * Initialisation of static vatriables, done one time on initial loading 
     * cf. Route::init()
     */
    public static function init()
    {
        // suppose path like lib/php/Oeuvres/Kit/Route.php
        self::$lib_dir = dirname(__DIR__, 3). DIRECTORY_SEPARATOR ;
        $url_request = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
        $url_request = strtok($url_request, '?'); // old


        // get a path relative to app
        // [REQUEST_URI] => /app_name/cat/resource
        // $url_request = /cat/resource
        // simple case, no redirection       
        $php_prefix = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        if ($php_prefix && strpos($url_request, $php_prefix) !== FALSE) {
            $url_request = substr($url_request, strlen($php_prefix));
        }
        // other case, redirection app_name <> app_folder       
        // [PHP_SELF] => /app_folder/index.php
        // this rule suppose same level between app_name and app_folder 
        else {
            $slash_nb = substr_count($_SERVER['PHP_SELF'], '/');
            for ($i = 0; $i < $slash_nb; $i++) {
                $pos = strpos($url_request, '/');
                // bad case
                if ($pos === false) break;
                $url_request = substr($url_request, $pos + 1);
            }
            $url_request = '/' . $url_request;
        }

        
        self::$url_request = $url_request;
        self::$url_parts = explode('/', ltrim($url_request, '/'));
        // quite robust on most server, work directory is the answering index.php
        self::$home_dir = getcwd() . DIRECTORY_SEPARATOR;
        self::$home_href = str_repeat('../', count(self::$url_parts) - 1);
        if (!self::$home_href) self::$home_href = './';

        // get relative path from index.php caller to the root of app to calculate href for resources in this folder
        self::$lib_href = self::$home_href . Filesys::relpath(
            dirname($_SERVER['SCRIPT_FILENAME']), 
            self::$lib_dir
        );
    }

    /**
     * Relative path to the root of the website for href links in templates
     * (where is the initial index.php caller)
     * Set by init()
     */
    static public function home_href(): string
    {
        return self::$home_href;
    }

    /**
     * Absolute file path of the website.
     * Set by init()
     */
    static public function home_dir(): string
    {
        return self::$home_dir;
    }

    /**
     * Relative path to the root of the library containing this Route.php,
     * usually home_href() = lib_href(),
     * but it could be interesting to share this library and
     * resources (ex: css, js…) among different sites.
     * For href links in templates.
     * Set by init()
     */
    static public function lib_href(): string
    {
        return self::$lib_href;
    }

    /**
     * Absolute file path of the library
     * Set by init()
     */
    static public function lib_dir(): string
    {
        return self::$lib_dir;
    }

    /**
     * Return the path requested
     */
    static public function url_request(): string
    {
        return self::$url_request;
    }

    /**
     * Return the last calculated path for resource (maybe ueful for debug)
     */
    static public function resource(): ?string
    {
        return self::$resource;
    }

    /**
     * Try a route with GET method 
     */
    public static function get(...$args)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            self::route(...$args);
        }
    }

    /**
     * Try a route with POST method 
     */
    public static function post(...$args)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            self::route(...$args);
        }
    }

    /**
     * Try a route
     */
    public static function route(
        string $route, 
        string $resource, 
        ?array $pars=null, 
        ?string $tmpl_file=''
    ):bool {
        // the catchall
        if ($route == "/404") {
            http_response_code(404);
        }
        else {
            $search = "@^" . trim($route, '^$') . "$@";
            if(!preg_match($search, self::$url_request, $matches)) {
                return false;
            }
            // rewrite resource according to the route capturing pattern
            // be careful here 
            if (strpos($resource, '$') !== false) {
                for ($i = 1, $count = count($matches); $i < $count; $i++) {
                    $resource =  str_replace('$'.$i, $matches[$i], $resource);
                }
            }
        }

        if (!Filesys::isabs($resource)) {
            // resolve links from welcome page
            $resource = self::$home_dir . $resource;
        }
        Log::debug(__FUNCTION__ . ' ' . file_exists($resource) . ' ' . $resource);
        // file not found, let chain continue
        if (!file_exists($resource)) {
            self::$resource = $resource;
            return false;
        }
        // modyfy parameters according to route
        if ($pars != null) {
            preg_match('@'.$route.'@', self::$url_request, $route_match);
            foreach($pars as $key => $value) {
                $pars[$key] = urldecode(self::replace($value, $route_match));
            }
            $_REQUEST = array_merge($_REQUEST, $pars);
            $_GET = array_merge($_GET, $pars);
        }

        $ext = pathinfo($resource, PATHINFO_EXTENSION);
        // should be routed
        self::$routed = true;
        self::$resource = $resource;

        // no template registred, no template recorded
        if ($tmpl_file === '' && !self::$tmpl_file) {
            $tmpl_file = null;
        }
        // no template requested, send first one
        else if ($tmpl_file === '') {
            $tmpl_file = self::$tmpl_file;
        }
        // explitly no template requested
        else if ($tmpl_file === null || $tmpl_file === false) {
            $tmpl_file = null;
        }
        // template file not available, inform dev
        else if (!Filesys::readable($tmpl_file)) {
            throw new Exception(
                "DEV error. Template not found $tmpl_file. " . Log::last()
            );
            exit();
        }


        // php in template
        if ($tmpl_file !== null && $ext == 'php') {
            // inform resource.php of template used for this request
            self::$template = $tmpl_file;
            // capture content if it is direct php
            ob_start();
            $ok = include($resource);
            // default return of an inlcude is true
            // php resource may return false and do its own tests
            if (!$ok) {
                @ob_end_clean();
                return false;
            }
            // store generated content if any
            self::$cont = ob_get_contents();
            @ob_end_clean();
            // just after include of php 
            // check if contents is generated by a function in a var
            if (isset($main)) {
                self::$main = $main;
            }
            // same for title
            if (isset($title)) {
                self::$title = $title;
            }
            // same for meta in head
            if (isset($meta)) {
                self::$meta = $meta;
            }
            // include the template that will execute those things
            include_once($tmpl_file);
            exit();
        }
        // html in template
        else  if ($tmpl_file !== null && ($ext == 'html' || $ext == 'htm')) {
            // load html and let main and meta function deal with that
            self::$html = file_get_contents($resource);
            include_once($tmpl_file);
            exit();
        }
        // no template, include php as direct
        else if ($ext == 'php') {
            include_once($resource);
            // maybe there is a main
            if (!isset($main));
            else if (is_callable($main)) echo $main();
            else echo $main;
            exit();
        }
        // no template, read html as direct
        else if ($ext == 'html' || $ext == 'htm') {
            readfile($resource);
            exit();
        }
        // static resource like css or image, serve it with headers for cache
        else {
            Http::readfile($resource);
            exit();
        }
    }

    private static function html_inner($tag)
    {
        $start = strpos(self::$html, "<$tag");
        // no body to slice, return all
        if ($start === false && $tag == 'body') {
            echo self::$html;
            return;
        }
        // other tag, show nothing
        else if ($start === false) {
            return;
        }
        // body tag with possible attributes
        $start = strpos(self::$html, ">", $start);
        if ($start === false) {
            Log::warning("Route, html malformed, found <$tag (without a >) in " . self::$resource);
            return;
        }
        // end of tag
        $end = strpos(self::$html, "</$tag>", $start);
        if ($end === false) {
            echo substr(self::$html, $start +1);
        }
        else {
            echo substr(self::$html, $start+1, $end - $start - 1);
        }
    
    }

    /**
     * Populate a page with content
     */
    public static function main(): void
    {
        // echo captured contents
        echo self::$cont;
        // a static content to include
        if (self::$html) {
            echo self::html_inner('body');
        }
        if (!isset(self::$main)) {
            // strange, log it ? desired ?
            // this is bad, but be nice
            if (function_exists('main')) {
                echo call_user_func('main');
            }
            return;
        }
        // main is a callable, call it
        else if (is_callable(self::$main)) {
            $fun = self::$main; // found required to execute callable
            echo $fun();
            return;
        }
        // a contents captured
        else {
            echo self::$main;
            return;
        }
        // obsolete, global function can’t be redefined
        if (function_exists('main')) {
            echo call_user_func('main');
        }
    }

    /**
     * Populate a page with content
     */
    public static function meta($default): void
    {
        // maybe some head in static html to include
        if (self::$html) {
            echo self::html_inner('head');
        }
        // no meta in requested resource;
        else if (!isset(self::$meta)) {
        }
        // a callable
        else if (is_callable(self::$meta)) {
            $fun = self::$meta; // found required to execute callable
            echo $fun();
            return;
        }
        // metadata
        else if (self::$meta) {
            echo self::$meta;
            return;
        }
        else if ($default) {
            echo $default;
        }
        else {
            echo "<title>" . I18n::_('title') . "</title>";
        }
    }

    /**
     * Display a <title> for the page 
     */
    public static function title($default=null): string
    {
        $s = '';
        // when php producer is loaded, a global variable $title is recorded in self::$title
        // if $title is a callable, call it 
        if (isset(self::$title) && is_callable(self::$title)) {
            // bug php to execute a callable
            $fun = self::$title;
            $s = $fun();
        }
        else if (isset(self::$title)) {
            $s = self::$title;
        }
        // very, very, obsolete
        else if (function_exists('title')) {
            $s = call_user_func('title');
        }
        if ($s) return $s;
        if ($default) {
            return $default;
        }
        return I18n::_('title');
    }

    /**
     * Draw an html tab for a navigation with test if selected 
     */
    public static function tab($href, $text)
    {
        $page = self::$url_parts[0];
        $selected = '';
        if ($page == $href) {
            $selected = " selected";
        }
        if(!$href) {
            $href = '.';
        }
        return '<a class="tab'. $selected . '"'
        . ' href="'. self::home_href(). $href . '"' 
        . '>' . $text . '</a>';
    }

    /**
     * Check if a route match url
     */
    public static function match($route):bool
    {
        $search = "@^" . trim($route, '^$') . "$@";
        if(!preg_match($search, self::$url_request)) {
            return false;
        }
        return true;
    }

    /**
     * Append a template
     */
    static public function template( string $tmpl_file): ?string
    {
        if (!Filesys::readable($tmpl_file)) {
            throw new Exception(
                "DEV error. Template not found $tmpl_file. " . Log::last()
            );
        }
        $old = self::$tmpl_file;
        // register template as new default
        self::$tmpl_file = $tmpl_file;
        // return old template
        return $old;
    }

    /**
     * Replace $n by $values[$n]
     */
    static public function replace($pattern, $values)
    {
        if (!$values && !count($values)) {
            return $pattern;
        }
        $ret = preg_replace_callback(
            '@\$(\d+)@',
            function ($var_match) use ($values) {
                $n = $var_match[1];
                if (!isset($values[$n])) {
                    return $var_match[0];
                }
                $filename = $values[$n]; 
                // shall we ensure no slash here ?  
                // $filename = preg_replace('@\.\.|/|\\\\@', '', $filename);
                return $filename;
            },
            $pattern
        );
        return $ret;
    }

    /**
     * Get an attribute value
     */
    static public function getAtt($key)
    {
        if (!isset(self::$atts[$key])) return null;
        return self::$atts[$key];
    }
    /**
     * Set an attribute value
     */
    static public function setAtt($key, $value)
    {
        $ret = null;
        if (isset(self::$atts[$key])) $ret = self::$atts[$key];
        self::$atts[$key] = $value;
        return $ret;
    }

}
