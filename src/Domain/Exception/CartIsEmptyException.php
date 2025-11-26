<?php

namespace Nurtjahjo\StoremgmCA\Domain\Exception;

class CartIsEmptyException extends StoreManagementException
{
    protected $message = 'Your cart is empty.';
    protected $code = 400;
}
