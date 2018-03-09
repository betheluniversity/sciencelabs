<?php

namespace Bethel\CourseViewBundle\Services;

use Bethel\EntityBundle\Entity\CourseCode;
use Bethel\EntityBundle\Services\PopulateProfessorService;
use Bethel\EntityBundle\Services\PopulateSemesterService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Bethel\WSAPIBundle\Controller\WSAPIController;
use Symfony\Component\HttpFoundation\Session\Session;

class PopulateCourseCodesService {

    private $em;
    private $session;
    private $wsapi;
    private $populateCourseService;
    private $populateSemesterService;
    private $populateProfessorService;

    private $courseCodes;

    public function __construct(
        EntityManager $em,
        Session $session,
        WSAPIController $wsapi,
        PopulateCourseService $populateCourseService,
        PopulateSemesterService $populateSemesterService,
        PopulateProfessorService $populateProfessorService
    ) {
        $this->em = $em;
        $this->session = $session;
        $this->wsapi = $wsapi;
        $this->populateCourseService = $populateCourseService;
        $this->populateSemesterService = $populateSemesterService;
        $this->populateProfessorService = $populateProfessorService;
    }

    /**
     * @param ArrayCollection $courseCodes
     * @return ArrayCollection|null
     */
    public function populate(ArrayCollection $courseCodes) {
        $this->courseCodes = $courseCodes;
        $semesterRepository = $this->em->getRepository('BethelEntityBundle:Semester');
        /** @var \Bethel\EntityBundle\Entity\Semester $activeSemester */
        $activeSemester = $semesterRepository->findOneBy(array('active'=>true));
        $semesterStart = $activeSemester->getStartDate();

        $createdCourses = new ArrayCollection();
        /** @var \Bethel\EntityBundle\Entity\CourseCode $courseCode */
        foreach($this->courseCodes as $courseCode) {
            $validCourseCode = $this->validateAndPersistCourseCode($courseCode);
            if($validCourseCode) {
                $courses = $this->createCourses($courseCode, $semesterStart);
                foreach($courses as $course) {
                    $createdCourses->add($course);
                }
            }
        }

        return count($createdCourses) > 0 ? $createdCourses : null;
    }

    private function validateAndPersistCourseCode(CourseCode $courseCode) {
        $courseSubject = $courseCode->getDept();
        $courseNumber = $courseCode->getCourseNum();
        // Connected to the WSAPIController
        $apiCourseCode = $this->wsapi->getCourseCodeAndName($courseSubject, $courseNumber);

        if($apiCourseCode && count($apiCourseCode) == 1) {

            $apiCourseTitle = $apiCourseCode[0]['title'];
            if(!$courseCode->getCourseName()) {
                $courseCode->setCourseName($apiCourseTitle);
                $this->em->persist($courseCode);
                $this->em->flush($courseCode);
            }
            return true;

        } else {
            // no result
            $this->session->getFlashBag()->add(
                'warning',
                $courseCode->__toString() . ' is not a valid course code.'
            );

            $this->em->remove($courseCode);
            $this->em->flush($courseCode);

            return false;
        }
    }

    /**
     * @param CourseCode $courseCode
     * @param DateTime $semesterStart
     * @return array
     */
    private function createCourses(CourseCode $courseCode, DateTime $semesterStart) {
        $courseSubject = $courseCode->getDept();
        $courseNumber = $courseCode->getCourseNum();

        // If these courses are being created before the start of the active semester
        // we need to make sure we make our API call with the appropriate time interval
        $currDate = new \DateTime('now');
        $interval = $currDate->diff($semesterStart);

        if($interval->invert == 1) {
            $apiCourses = $this->wsapi->getCoursesByCourseCode($courseSubject, $courseNumber);
        } else {
            $apiCourses = $this->wsapi->getCoursesByCourseCode($courseSubject, $courseNumber, $interval->d);
        }

        $courses = new ArrayCollection();

        if(is_array($apiCourses)) {
            if( sizeof($apiCourses) != 0){
                foreach($apiCourses as $apiCourse) {
                    $semester = $this->populateSemesterService->populate($apiCourse['term']);
                    $professor = $this->populateProfessorService->populate($apiCourse['instructorUsername'], $apiCourse['instructor']);
                    $course = $this->populateCourseService->populate($apiCourse, $semester, $courseCode, $professor);
                    $courses->add($course);
                    
                }
            } 
            // else {
            //     $this->session->getFlashBag()->add(
            //             'warning',
            //             $courseCode->__toString() . ' is not a valid course code for the current term. '
            //         );
            // }
        } 


        


        return $courses;
    }
}