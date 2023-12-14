<?php
/**
 * Part of Teinte https://github.com/oeuvres/teinte
 * Copyright (c) 2020 frederic.glorieux@fictif.org
 * Copyright (c) 2013 frederic.glorieux@fictif.org & LABEX OBVIL
 * Copyright (c) 2012 frederic.glorieux@fictif.org
 * BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

declare(strict_types=1);

namespace Oeuvres\Kit\Logger;

use Psr\Log\LogLevel;

/**
 * A PSR-3 compliant logger as light as possible
 * Used to store in a string
 *
 * @see https://www.php-fig.org/psr/psr-3/
 */
class LoggerMem extends Logger
{
    private $buff = '';
    protected function write($level, $message)
    {
        $this->buff .= $message . "\n";
    }

    /**
     * Returns logged mmessages
     */
    public function messages($empty = true): string
    {
        $ret =  $this->buff;
        if ($empty) $this->buff = '';
        return $ret;
    }

    public function __construct(
        ?string $level = LogLevel::INFO, 
        ?string $prefix = "{level} {duration} {lapse} — ",
        ?string $suffix = ""
    ) {
        parent::__construct($level, $prefix, $suffix);
    }

}
