<?php

namespace App\Contracts;

interface LoggerInterface
{
    public function log(string $message): void;
}
