<?php

declare(strict_types=1);

namespace Nurtjahjo\StoremgmCA\Domain\Exception;

use Exception;

class InvalidSignatureException extends Exception
{
    protected $message = 'Invalid payment signature.';
    protected $code = 403;
}
