<?php
/**
 * Created by PhpStorm.
 * User: pms63443
 * Date: 1/30/14
 * Time: 11:06 AM
 */

namespace Bethel\FrontBundle\Controller;

use Bethel\EntityBundle\Entity\Course;
use Bethel\EntityBundle\Entity\Semester;
use Bethel\EntityBundle\Entity\Session;
use Bethel\EntityBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;

class BaseController extends Controller {

    // TODO: update existing entities based on the fresh API information
    // TODO: look into where to handle persistence and flushing
    // right now i'm persisting and flushing after each entity creation

    /**
     * @param string $apiSemester
     * @return Semester
     */
    private function handleApiSemester($apiSemester) {
        // look up the semester in the database
        // brittle string manipulation
        $semesterArray = explode(" ", $apiSemester);
        $semesterTerm = $semesterArray[0];
        $semesterYear = intval($semesterArray[1]);
        /** @var $semesterRepository \Bethel\EntityBundle\Entity\SemesterRepository */
        $semesterRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:Semester');
        /** @var $semester \Bethel\EntityBundle\Entity\Semester */
        $semester = $semesterRepository->findOneBy(
            array(
                'term' => $semesterTerm,
                'year' => $semesterYear
            )
        );

        // if the semester doesn't exist, we need to add it and persist it
        if(!$semester) {
            $semester = new Semester();
            $semester->setYear($semesterYear);
            $semester->setTerm($semesterTerm);

            $this->getEntityManager()->persist($semester);
            $this->getEntityManager()->flush();
        }

        return $semester;
    }


    /**
     * @param string $apiProfessorUsername
     * @param string $apiProfessorName
     * @return User
     */
    private function handleApiProfessor($apiProfessorUsername, $apiProfessorName) {

        // look up the professor in the database
        /** @var $userRepository \Bethel\EntityBundle\Entity\UserRepository */
        $userRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:User');
        $professor = $userRepository->findOneBy(
            array(
                'username' => $apiProfessorUsername
            )
        );

        // if the professor isn't in the database, we need to create a new user
        //  and persist it
        if(!$professor) {
            $professor = new User();

            // get the professor role
            $roleRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:Role');
            $professorRole = $roleRepository->findOneBy(
                array(
                    'name' => 'Professor'
                )
            );

            // Names are in the following format: First M. Last
            $profNameArray = explode(" ", $apiProfessorName);

            $professor->setFirstName($profNameArray[0]);
            $profLastName = '';
            for($i = 2; $i < count($profNameArray); $i ++) {
                // Handle last names containing spaces.
                $profLastName += $profNameArray[$i];
            }
            $professor->setLastName($profNameArray[2]);
            $professor->setUsername($apiProfessorUsername);
            $professor->addRole($professorRole);
            $professor->setEmail($apiProfessorUsername . '@bethel.edu');

            $this->getEntityManager()->persist($professor);
            $this->getEntityManager()->flush();
        }

        return $professor;
    }


    /**
     * @param array $apiCourse
     * @param User $professor
     * @param Semester $semester
     * @return Course
     */
    private function handleApiCourse($apiCourse, $professor, $semester) {
        // look up the course in the database
        /** @var $courseRepository \Bethel\EntityBundle\Entity\CourseRepository */
        $courseRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:Course');
        $course = $courseRepository->findOneBy(
            array(
                'crn' => $apiCourse['crn'],
                'semester' => $semester->getId()
            )
        );

        // if it doesn't already exist, we need to add it and persist it
        if(!$course) {
            //begin_date: "03-FEB-14",
            //begin_time: "0830",
            //course_num: "355Z",
            //crn: "1021",
            //dept: "COM",
            //end_date: "23-MAY-14",
            //end_time: "1010",
            //instructor: "Lawrence R. Smith",
            //instructorUsername: "rsmith",
            //meeting_day: "TR",
            //term: "Spring 2014 - CAS",
            //title: "Intercultural Communication"
            $course = new Course();
            $course
                ->setBeginDate(new \DateTime($apiCourse['beginDate']))
                ->setCourseNum($apiCourse['cNumber'])
                ->setCrn($apiCourse['crn'])
                ->setDept($apiCourse['subject'])
                ->setEndDate(new \DateTime($apiCourse['endDate']))
                ->addProfessor($professor)
                ->setMeetingDay($apiCourse['meetingDay'])
                ->setSemester($semester)
                ->setTitle($apiCourse['title'])
                ->setSection($apiCourse['section']);

            $this->getEntityManager()->persist($course);
            $this->getEntityManager()->flush();
        }

        return $course;
    }

    /**
     * @param array $apiCourses
     * @param Session $session
     * @return ArrayCollection
     */
    public function handleCourseApiCall($apiCourses, $session) {
        $courses = new ArrayCollection();
        foreach($apiCourses as $apiCourse) {
            // Only allow a course to be added if the corresponding course
            // code is active in the system
            /** @var $courseCodeRepository \Bethel\EntityBundle\Entity\CourseCodeRepository */
            $courseCodeRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:CourseCode');
            /** @var $courseCode \Bethel\EntityBundle\Entity\CourseCode */
            $courseCode = $courseCodeRepository->findOneBy(array('courseNum'=>$apiCourse['cNumber'],'dept'=>$apiCourse['subject']));

            if($courseCode) {
                $courseCodeInSession = $courseCodeRepository->getCourseCodeInSession($session,$courseCode);

                if($courseCodeInSession) {
                    /** @var $semester \Bethel\EntityBundle\Entity\Semester */
                    //$semester = $this->handleApiSemester($apiCourse['term']);
                    $populateSemesterService = $this->get('bethel.populate_semester');
                    $semester = $populateSemesterService->populate($apiCourse['term']);

                    // $professor = $this->handleApiProfessor($apiCourse['instructorUsername'], $apiCourse['instructor']);
                    $populateProfessorService = $this->get('bethel.populate_professor');
                    $professor = $populateProfessorService->populate($apiCourse['instructorUsername'], $apiCourse['instructor']);

                    // $course = $this->handleApiCourse($apiCourse, $professor, $semester);
                    $populateCourseService = $this->get('bethel.populate_course');
                    $course = $populateCourseService->populate($apiCourse, $semester, $courseCode, $professor);

                    $courses->add($course);
                }
            }
        }

        return $courses;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager() {
        $em = $this->getDoctrine()->getManager();
        return $em;
    }

    /**
     * @return \Bethel\EntityBundle\Entity\User|null
     */
    public function getUser() {
        /** @var \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $sessionToken */
        $sessionToken = $this->get('security.context')->getToken();
        if($sessionToken) {
            /** @var \Bethel\EntityBundle\Entity\User $user */
            $user = $sessionToken->getUser();
        } else {
            $user = null;
        }

        return $user;
    }


    /**
     * @param \Bethel\EntityBundle\Entity\Semester $semester
     */
    public function setSessionSemester($semester) {
        $id = $semester->getId();
        $this->get('session')->set('semesterId', $id);
    }

    /**
     * @return \Bethel\EntityBundle\Entity\Semester
     */
    public function getSessionSemester() {
        $semesterRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:Semester');
        if ($this->get('session')->has('semesterId')) {
            $semester = $semesterRepository->findOneBy(array(
                'id' => $this->get('session')->get('semesterId')
                )
            );
        } else {
            $semester = $semesterRepository->findOneBy(array('active' => true));
        }
        return $semester;
    }

    /**
     * @return \Bethel\EntityBundle\Entity\Semester
     */
    public function getActiveSemester() {
        $activeSemester = $this->getEntityManager()->getRepository('BethelEntityBundle:Semester')->findOneBy(array('active' => true));

        return $activeSemester;
    }

    // This selects a user by role
    // e.g. ROLE_TUTOR
    public function userHasRole(User $user, $roleString) {

        $roles = $user->getRoles();
        $hasRole = false;
        /** @var \Bethel\EntityBundle\Entity\Role $role */
        foreach($roles as $role) {
            if($role->getRole() == $roleString) {
                $hasRole = true;
            }
        }

        return $hasRole;
    }

    static function sessionMonthSort($a,$b) {

        $monthSortArray = array();
        for($i = 0; $i < 12; $i++) {
            $firstDate = \DateTime::createFromFormat('n j', strval($i+1) . ' ' . 1);
            $monthSortArray[$firstDate->format('F')] = $i;
        }

        return $monthSortArray[$a] - $monthSortArray[$b];
    }

    static function sessionDateSort($a,$b) {
        $aDateTime = $a['session']->getStartDateTime();
        $bDateTime = $b['session']->getStartDateTime();
        $retVal = $aDateTime->getTimeStamp() - $bDateTime->getTimestamp() > 0 ? 1 : -1;
        return $retVal;
    }

    static function scheduleDowSort($a,$b) {
        // We want to sort smaller values first
        /** @var \Bethel\EntityBundle\Entity\Schedule $a */
        $aDayOfWeek = $a->getDayOfWeek();
        /** @var \Bethel\EntityBundle\Entity\Schedule $b */
        $bDayOfWeek = $b->getDayOfWeek();
        $retVal = $aDayOfWeek - $bDayOfWeek > 0 ? 1 : -1;
        return $retVal;
    }

    static function userLastNameSort($a,$b) {
        // We want to sort smaller values first
        /** @var \Bethel\EntityBundle\Entity\User $a */
        $aLastName = $a->getLastName();
        /** @var \Bethel\EntityBundle\Entity\User $b */
        $bLastName = $b->getLastName();
        $retVal = strcmp($aLastName,$bLastName);
        return $retVal;
    }

    static function userLastNameArraySort($a,$b) {
        $retVal = strcmp($a['lastName'],$b['lastName']);
        return $retVal;
    }
} 