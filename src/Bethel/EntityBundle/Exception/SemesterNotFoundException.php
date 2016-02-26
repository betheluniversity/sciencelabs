<?php

namespace Bethel\EntityBundle\Exception;

class SemesterNotFoundException extends \DomainException implements Exception {
    protected $message;
    public function __construct($message, $code = 0, \Exception $previous = null) {
        $this->message = $message;
    }
}