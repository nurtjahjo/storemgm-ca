<?php

namespace Nurtjahjo\StoremgmCA\Domain\Service;

interface LoggerInterface
{
    public function log(string $message, string $level = 'INFO', string $filename = 'storemgm.log'): void;
}
