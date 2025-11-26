<?php

namespace Nurtjahjo\StoremgmCA\Infrastructure\Logger;

use Nurtjahjo\StoremgmCA\Domain\Service\LoggerInterface;

class NullLogger implements LoggerInterface
{
    public function log(string $message, string $level = 'INFO', string $filename = 'storemgm.log'): void {}
}
