<?php

namespace Bethel\EntityBundle\Validator;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

class TermValidator extends ConstraintValidator {

    private $terms;

    public function __construct($terms) {
        $indexedTerms = explode('|',$terms);
        $this->terms = array_combine($indexedTerms,$indexedTerms);
    }

    public function getTerms() {
        return $this->terms;
    }

    public function getTermString() {
        $termString = '';
        $termKeys = array_keys($this->terms);
        foreach($this->terms as $key => $term) {
            if(end($termKeys) == $key) {
                $termString .= $term;
            } else {
                $termString .= $term . ', ';
            }
        }
        return $termString;
    }

    public function validate($value, Constraint $constraint) {

        if (array_search($value, $this->terms) === false) {
            $this->context->addViolation(
                $constraint->message,
                array(
                    '%string%' => $value,
                    '%terms%' => $this->getTermString()
                )
            );
        }
    }
} 