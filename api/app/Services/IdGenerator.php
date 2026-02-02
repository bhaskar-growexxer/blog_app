<?php

namespace App\Services;

class IdGenerator
{
    protected string $prefix;

    public function __construct(string $prefix = 'id_')
    {
        $this->prefix = $prefix;
    }

    public function generate(): string
    {
        return $this->prefix . uniqid('', true);
    }
}
