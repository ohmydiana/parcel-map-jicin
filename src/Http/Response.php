<?php

declare(strict_types=1);

namespace App\Http;

interface Response
{
    public function send(): void;
}