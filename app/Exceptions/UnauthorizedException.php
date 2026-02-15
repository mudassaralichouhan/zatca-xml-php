<?php

namespace App\Exceptions;

use App\Logging\Logger;
use Throwable;

class UnauthorizedException extends \Exception
{
    protected array $errors;

    public function __construct(array $errors, string $message = "UnAuthorized", int $code = 401, ?Throwable $previous = null)
    {
        $this->errors = $errors;

        parent::__construct($message, $code, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
