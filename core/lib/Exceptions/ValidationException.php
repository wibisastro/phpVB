<?php

namespace Gov2lib\Exceptions;

class ValidationException extends HttpException
{
    /** @var array<string, string> */
    private array $errors;

    /**
     * @param array<string, string> $errors
     */
    public function __construct(
        string $message = 'Harap isi form dengan lengkap',
        array $errors = [],
        ?\Throwable $previous = null
    ) {
        $this->errors = $errors;
        parent::__construct(422, $message, $previous);
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
