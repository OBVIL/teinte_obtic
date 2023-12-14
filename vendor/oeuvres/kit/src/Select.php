<?php

/**
 * Part of Teinte https://github.com/oeuvres/teinte
 * MIT License https://opensource.org/licenses/mit-license.php
 * Copyright (c) 2020 frederic.Glorieux@fictif.org
 * Copyright (c) 2013 Frederic.Glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 Frederic.Glorieux@fictif.org
 * Copyright (c) 2010 Frederic.Glorieux@fictif.org 
 *                    & École nationale des chartes
 */

declare(strict_types=1);

namespace Oeuvres\Kit;

use Oeuvres\Kit\{Http};

/**
 * Helper class to print multiple value form control,
 * dealing with php parameters oddities
 */
class Select
{
    const SELECT = 'select';
    const MULTIPLE = 'multiple';
    const RADIO = 'radio';
    const CHECKBOX = 'checkbox';
    /** display type */
    private $type;
    /** Name of the select, required */
    private $name;
    /** Id of the element, optional */
    private $id;
    /** List value => label, unicity ensured */
    private $options = [];
    /** Values to check */
    private $pars;
    /** Has checked */
    private $checked;


    /** Construct a radio set */
    function __construct($name, $type = self::SELECT, $id=null)
    {
        $this->type = $type;
        $this->name = $name;
        if ($id !== null) $this->id = $id;
        // get hhtp pars
        $pars = Http::pars($name);
        if (count($pars)) {
            $this->pars = array_flip($pars);
        }
    }
    /** Add a value */
    function add(
        string $value, 
        ?bool $checked, 
        ?string $label=null,
        ?string $title=null
    ): Select
    {
        // if value contains bad chars for html attribute or regex, let it go ?
        if ($label === null) {
            $label = $value;
        }
        if ($this->pars) {
            $checked = isset($this->pars[$value]);
        }
        $this->options[$value] = [
            "checked" => $checked, 
            "label" => $label, 
            "title" => $title
        ];
        if ($checked) $this->checked = true;
        return $this;
    }
    /**
     * Return html to display with correct params
     */
    function __toString(): string
    {
        // Warn developper here ?
        if (!count($this->options)) {
            return '';
        }
        else if ($this->type == self::CHECKBOX) {
            return $this->checkbox();
        }
        else if ($this->type == self::RADIO) {
            return $this->radio();
        }
        else {
            return $this->select();
        }
    }


    /**
     * Return html to display with correct params
     */
    function checkbox(): string
    {
        $html = '';
        $n = 1;
        foreach($this->options as $value => $row) {
            $id = $this->name . $n;
            $html .= "\n<div class=\"checkbox {$this->name}\"";
            if ($row['title']) {
                $html .= " title=\"{$row['title']}\"";
            }
            $html .= ">\n";
            $html .= "  <input type=\"checkbox\" name=\"{$this->name}\" value=\"{$value}\" id=\"$id\"";
            if ($row['checked']) $html .= " checked=\"checked\"";
            $html .= "/>\n";
            $html .= "  <label for=\"$id\">{$row['label']}</label>\n";
            $html .= "</div>\n";
            $n++;
        }
        return $html;
    }


    /**
     * Return html to display with correct params
     */
    private function select(): string
    {
        $html = '<select name="' . $this->name .'"';
        if ($this->id != null) $html .= ' id="' . $this->id . '"';
        $html .= '>';
        foreach($this->options as $value => $row) {
            $checked = '';
            if ($row['checked']) $checked = ' selected="selected"';
            $html .= '
    <option value="' . $value . '"' . $checked . '>' . $row['label'] . '</option>';
        }
        $html .= '
</select>';
        return $html;
    }

    private function radio(): string
    {
        $n = 1;
        $html = "";
        foreach($this->options as $value => $row) {
            $id = $this->name . $n;
            $checked = '';
            if ($row['checked']) $checked = ' checked="checked"';
            $html .= '
    <span class="radio">
        <input onchange="this.form.submit()" type="radio" id="' . $id . '" name="' . $this->name 
            . '" value="' . $value . '"' 
            . $checked . '/>
        <label class="radio" for="' . $id . '">' . $row['label'] . '</label>
    </span>
';
            $n++;
        }
        return $html;
    }


}