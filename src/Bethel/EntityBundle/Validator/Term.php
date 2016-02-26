<?php

namespace Bethel\EntityBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Term extends Constraint {

    public $message = 'Term must be one of: %terms%. You entered "%string%"';

    public function validatedBy() {
        return "term_validator";
    }
} 