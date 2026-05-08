<?php

declare(strict_types=1);

namespace App\StaticSamples\Module04_Admin;

final class SampleErrors
{
    public function errorOne(): void
    {
        $row = ['id' => 1];
        echo $row['title'];
    }

    public function errorTwo(): void
    {
        if (1 === 2) {
            echo 'unreachable branch';
        }
    }
}
