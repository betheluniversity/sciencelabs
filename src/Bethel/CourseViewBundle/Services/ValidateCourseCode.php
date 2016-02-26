<?php

namespace Bethel\CourseViewBundle\Services;

use Bethel\EntityBundle\Entity\CourseCode;
use Doctrine\ORM\EntityManager;
use Bethel\WsapiBundle\Wsapi\WsRestApi;

class ValidateCourseCode {

    private $em;
    private $wsapi;

    public function __construct(
        EntityManager $em,
        WsRestApi $wsapi
    ) {
        $this->em = $em;
        $this->wsapi = $wsapi;
    }

    /**
     * @param $courseSubject
     * @param $courseNumber
     * @return bool|array
     */
    public function validate($courseSubject, $courseNumber) {
        $apiCourseCode = $this->wsapi->getCourseCodeAndName($courseSubject, $courseNumber);
        if($apiCourseCode && count($apiCourseCode) == 1) {

            return $apiCourseCode[0];
        } else if ($apiCourseCode) {
            // Multiple Course Codes exist with this subject and number
            // This shouldn't happen
            return false;
        } else {
            return false;
        }
    }
}