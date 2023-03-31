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



namespace Oeuvres\Kit;

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
    static private $templates = [];
    /** An html file to include as main */
    static private $main_inc;
    /** A string or callable for contents */
    static private $main_cont;
    /** A title */
    static private $title;
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
        // maybe not robust, this should interpret path relative to the webapp
        // domain.com/subdir/verbatim/path/perso -> /path/perso
        $url_prefix = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        if ($url_prefix && strpos($url_request, $url_prefix) !== FALSE) {
            $url_request = substr($url_request, strlen($url_prefix));
        }
        self::$url_request = $url_request;
        self::$url_parts = explode('/', ltrim($url_request, '/'));
        // quite robust on most server, work directory is the answering index.php
        self::$home_dir = getcwd() . DIRECTORY_SEPARATOR;
        self::$home_href = str_repeat('../', count(self::$url_parts) - 1);
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
    public static function get($route, $resource, $pars=null)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            self::route($route, $resource, $pars);
        }
    }

    /**
     * Try a route with POST method 
     */
    public static function post($route, $resource, $pars=null)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            self::route($route, $resource, $pars);
        }
    }

    /**
     * Try a route
     */
    public static function route(
        string $route, 
        string $resource, 
        ?array $pars=null, 
        ?string $tmpl_key=''
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

        // choose a template in which embed content
        $tmpl_php = null;
        // no template registred, no template requested, OK
        if ($tmpl_key === '' && count(self::$templates) < 1) {
        }
        // no template requested, send first one
        else if ($tmpl_key === '') {
            $tmpl_php = self::$templates[array_key_first(self::$templates)];
        }
        // explitly no template requested
        else if ($tmpl_key === null) {
        }
        // inform developper there is a problem
        else {
            if (count(self::$templates) < 1) {
                throw new Exception(
                    "Developement error.
No templates registred, template '$tmpl_key' not found.
Use Route::template('tmpl_my.php', '$tmpl_key');"
                );
                exit();
            }
            if (!isset(self::$templates[$tmpl_key])) {
                throw new Exception(
                    "Developement error.
Template '$tmpl_key' not found.
Use Route::template('tmpl_my.php', '$tmpl_key');"
                );
                exit();
            }
            $tmpl_php = self::$templates[$tmpl_key];
        }


        // php in template
        if ($tmpl_php !== null && $ext == 'php') {
            // capture content if it is direct php
            ob_start();
            $ok = include($resource);
            // default return of an inlcude is true, allow script to do its tests and return false
            if (!$ok) {
                ob_end_clean();
                return false;
            }
            self::$main_cont = ob_get_contents();
            ob_end_clean();
            // maybe content, or a callable
            if (isset($main)) {
                self::$main_cont = $main;
            }
            if (isset($title)) {
                self::$title = $title;
            }
            // include the template that will include teh content
            include_once($tmpl_php);
            exit();
        }
        // html in template
        else  if ($tmpl_php !== null && ($ext == 'html' || $ext == 'htm')) {
            self::$main_inc = $resource;
            include_once($tmpl_php);
            exit();
        }
        // no template, include html or php as direct
        if ($ext == 'php' || $ext == 'html' || $ext == 'htm') {
            include_once($resource);
            exit();            
        }
        // static resource like css or image, serve
        else {
            Http::readfile($resource);
            exit();
        }
    }


    /**
     * Populate a page with content
     */
    public static function main(): void
    {

        // a static content to include
        if (self::$main_inc) {
            include_once(self::$main_inc);
            return;
        }
        // a callable
        if (isset(self::$main_cont) && is_callable(self::$main_cont)) {
            $main = self::$main_cont; // found required to execute callable
            echo $main();
            return;
        }
        // a contents captured
        if (isset(self::$main_cont) && self::$main_cont) {
            echo self::$main_cont;
            return;
        }

        // obsolete, global function can’t be redefined
        if (function_exists('main')) {
            echo call_user_func('main');
        }
    }

    /**
     * Display a <title> for the page 
     */
    public static function title($default=null): string
    {
        $s = '';
        if (isset(self::$title) && is_callable(self::$title)) {
            $s = self::$title();
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
     * Display metadata for a page
     */
    public static function meta($default=null): string
    {
        if (function_exists('meta')) {
            $meta = call_user_func('meta');
            if ($meta) return $meta;
        }
        if ($default) {
            return $default;
        }
        return "<title>" . I18n::_('title') . "</title>";
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
    static public function template(
        string $tmpl_php,
        ?string $key=null
    ):void
    {
        if (!Filesys::readable($tmpl_php)) {
            // will send exceptions if template is not readable
            return;
        } 
        else if ($key !== null && $key !== '') {
            self::$templates[$key] = $tmpl_php;
        } 
        else {
            self::$templates[] = $tmpl_php;
        }
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
                // ensure no slash, to dangerous
                $filename = $values[$n]; 
                $filename = preg_replace('@\.\.|/|\\\\@', '', $filename);
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
