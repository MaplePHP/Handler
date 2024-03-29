<?php

namespace MaplePHP\Handler\Interfaces;

interface UrlInterface
{
    public function withType(array $type);
    public function getVars();
    public function filterParts($vars): array;
}
