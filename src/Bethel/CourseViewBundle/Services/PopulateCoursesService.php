<?php
/**
 * Created by PhpStorm.
 * User: pms63443
 * Date: 2/5/15
 * Time: 11:26 AM
 */

namespace Bethel\CourseViewBundle\Services;


use Bethel\EntityBundle\Entity\Session;
use Bethel\EntityBundle\Services\PopulateProfessorService;
use Bethel\EntityBundle\Services\PopulateSemesterService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

class PopulateCoursesService {

    private $em;
    private $populateSemesterService;
    private $populateProfessorService;
    private $populateCourseService;

    public function __construct(
        EntityManager $em,
        PopulateSemesterService $populateSemesterService,
        PopulateProfessorService $populateProfessorService,
        PopulateCourseService $populateCourseService
    ) {
        $this->em = $em;
        $this->populateSemesterService = $populateSemesterService;
        $this->populateProfessorService = $populateProfessorService;
        $this->populateCourseService = $populateCourseService;
    }

    /**
     * @param array $apiCourses
     * @param Session $session
     * @return ArrayCollection
     */
    public function populate($apiCourses, $session = null) {
        $courses = new ArrayCollection();
        if(is_array($apiCourses)) {
            foreach($apiCourses as $apiCourse) {
                // Only allow a course to be added if the corresponding course
                // code is active in the system
                /** @var $courseCodeRepository \Bethel\EntityBundle\Entity\CourseCodeRepository */
                $courseCodeRepository = $this->em->getRepository('BethelEntityBundle:CourseCode');
                /** @var $courseCode \Bethel\EntityBundle\Entity\CourseCode */
                $courseCode = $courseCodeRepository->findOneBy(
                    array(
                        'courseNum' => $apiCourse['cNumber'],
                        'dept' => $apiCourse['subject'],
                        'active' => true
                    )
                );

                if($courseCode) {
                    $semester = $this->populateSemesterService->populate($apiCourse['term']);
                    $professor = $this->populateProfessorService->populate($apiCourse['instructorUsername'], $apiCourse['instructor']);
                    $course = $this->populateCourseService->populate($apiCourse, $semester, $courseCode, $professor);
                    $courses->add($course);
                }
            }
        }

        return $courses;
    }
}