<?php

namespace App\Components\Console;

class ProgressBar
{
    /** @var string */
    private $label;

    public function __construct(string $label)
    {
        $this->label = $label;
    }

    public function showProgress($current, $total): void
    {
        $percent = round(($current / $total) * 100, 2);
        echo "\r{$this->label} " . str_pad($percent . '%', 7, ' ') . ' (' . str_pad(
                $current . ' / ' . $total . ')',
                15,
                ' '
            );
    }
}