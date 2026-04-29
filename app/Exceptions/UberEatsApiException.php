<?php

namespace App\Exceptions;

use RuntimeException;

class UberEatsApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $action,
        private readonly int $status,
        private readonly string $responseBody = '',
    ) {
        parent::__construct($message);
    }

    public function action(): string
    {
        return $this->action;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function responseBody(): string
    {
        return $this->responseBody;
    }

    public function isUnauthorized(): bool
    {
        return in_array($this->status, [401, 403], true);
    }
}
