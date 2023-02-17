<?php

namespace App\Exceptions;

use Exception;

class CustomException extends Exception
{
    protected $errors;

    protected $status;

    public function __construct($errors, $status)
    {
        parent::__construct();

        $this->errors = $errors;

        $this->status = $status;
    }

    /**
     * Get error messages.
     *
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Get error status.
     *
     * @param  int  $status
     * @return $this
     */
    public function status()
    {
        return $this->status;
    }
}