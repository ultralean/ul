<?php

namespace UltraLean\Core\Exceptions;

use Exception;

class HttpException extends Exception
{
    protected int $status;
    protected array $headers;

    public function __construct(
        string $message = 'HTTP Error',
        int $status = 500,
        array $headers = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->status = $status;
        $this->headers = $headers;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
