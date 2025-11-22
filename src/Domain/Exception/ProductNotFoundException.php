<?php

namespace Nurtjahjo\StoremgmCA\Domain\Exception;

use Exception;

class ProductNotFoundException extends Exception
{
    protected $message = 'The requested product was not found.';
    protected $code = 404;
}
