<?php

namespace Bethel\CourseViewBundle\Services;

use Bethel\EntityBundle\Entity\CourseCode;
use Doctrine\ORM\EntityManager;
use Bethel\WSAPIBundle\Controller\WSAPIController;

class ValidateCourseCode {

    private $em;
    private $wsapi;

    public function __construct(
        EntityManager $em,
        WSAPIController $wsapi
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
        // Connected to the WSAPIController
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