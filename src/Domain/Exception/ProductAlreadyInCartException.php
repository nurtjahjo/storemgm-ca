<?php

namespace Nurtjahjo\StoremgmCA\Domain\Exception;

class ProductAlreadyInCartException extends StoreManagementException
{
    protected $message = 'This product is already in your cart.';
    protected $code = 409; // Conflict
}
