<?php

namespace App\Exceptions;

use App\Logging\Logger;
use Throwable;

class BadRequestException extends \Exception
{
    protected array $errors;

    public function __construct(
        array $errors,
        string $message = 'Bad Request, something went wrong with your request.',
        ?Throwable $previous = null
    ) {
        $this->errors = $errors;

        parent::__construct($message, 400, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
