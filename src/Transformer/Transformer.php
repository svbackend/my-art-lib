<?php

namespace App\Transformer;


interface Transformer
{
    public function process(array $data): array;
}