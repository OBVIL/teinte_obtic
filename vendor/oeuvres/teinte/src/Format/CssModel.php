<?php declare(strict_types=1);
/**
 * Part of Teinte https://github.com/oeuvres/teinte_php
 * Copyright (c) 2022 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace Oeuvres\Teinte\Format;

/**
 * This is not a full css parser but a tool
 * to catch semantic rules in CSS mess that may come
 * with a generated EPub, to make html more semantic.
 * 
 * ``` html
 * <p>This is an <span class="autostyling002">italic segment</span>.</p>
 * <style>
 * .autostyling002 {font-style: italic}
 * </style>
 * ```
 *  
 * If you need a full CSS parser, prefers
 * https://github.com/sabberworm/PHP-CSS-Parser
 * 
 */
class CssModel
{
    /** keep contents */
    private string $contents = '';
    /** A filter of rules */
    private array $filter = [
        "font-style" => ["italic"],
        "text-align" => ["center", "right"],
        "font-variant"  => ['small-caps']
    ];
    /** The model to append rules */
    private array $model = [];

    /**
     * Append new properties to keep 
     */
    public function filter(array $props)
    {
        $this->filter = $props;
    }

    /**
     * Return model in its state
     */
    public function asArray(): array
    {
        return $this->model;
    }

    /**
     * Return model as an XML 
     */
    public function asXml(): string
    {
        $xml = "<css>\n";
        foreach($this->model as $selector => $decls) {
            $xml .= "  <rule selector=\"$selector\">\n";
            foreach($decls as $property => $value) {
                $xml .= "    <declaration property=\"$property\" value=\"$value\"/>\n";
            }
            $xml .= "  </rule>\n";
        }
        $xml .= "</css>\n";
        return $xml;
    }

    /**
     * Load and parse a file
     */
    public function open($css_file)
    {
        $css = file_get_contents($css_file);
        $this->parse($css);
    }

    /**
     * Return all contents loaded
     */
    public function contents(): string
    {
        return $this->contents;
    }

    /**
     * parse a css content
     */
    public function parse($css_string)
    {
        if (!$css_string) return;
        $this->contents .= $css_string . "\n\n";
        // a complete css parser should here detailed compact declarations like font: …
        preg_match_all( '/(?ims)([a-z0-9\s\.\:#_\-@,]+)\{([^\}]*)\}/', $css_string, $rules);
        foreach ($rules[0] as $i => $x){
            $decls_kept = [];
            $decls = explode(';', trim($rules[2][$i]));
            foreach ($decls as $declaration){
                if (empty(trim($declaration))) continue;
                if (false === strpos($declaration, ':')) continue;
                list($property, $value) = explode(":", $declaration);
                $property = trim($property);
                $value = trim($value);

                // not in the waited properties
                if (!isset($this->filter[$property])) continue;
                // get the value waited
                $value_waited = $this->filter[$property];
                // just keep value
                if ($value_waited === true || $value_waited === null || empty($value_waited)) {
                    $decls_kept[$property] = $value;
                }
                // simple value
                else if (
                    is_string($value_waited)
                    && $value !== $value_waited
                ) {
                    $decls_kept[$property] = $value;
                }
                // set of values
                else if (
                    is_array($value_waited)
                    && in_array($value, $value_waited)
                ){
                    $decls_kept[$property] = $value;
                }
                // property is waited, but no the value, could be used nullify previous
                else {
                    $decls_kept[$property] = null;
                } 
            }
            // nothing kept
            if (count($decls_kept) < 1) continue;
            // split selectors
            $selectors = trim($rules[1][$i]);
            $selectors = explode(',', $selectors);
            foreach ($selectors as $selector){
                $selector = trim($selector);
                // selector known, merge props
                if (isset($this->model[$selector])) {
                    $props = array_merge($this->model[$selector], $decls_kept);
                    $props = array_filter($props);
                    if (count($props) < 1) {
                        unset($this->model[$selector]);
                    }
                    else {
                        $this->model[$selector] = $props;
                    }
                }
                else {
                    $props = array_filter($decls_kept);
                    if (count($props) < 1) continue;
                    $this->model[$selector] = $props;
                }
            }
        }
    } 

}

