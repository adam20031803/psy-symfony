<?php

declare(strict_types=1);

namespace App\StaticSamples\Module03_Messenger;

final class SampleErrors
{
    public function errorOne(): void
    {
        $msg = 'hello';
        $msg->send();
    }

    public function errorTwo(bool $ok): int
    {
        if ($ok) {
            return 1;
        }
    }
}
