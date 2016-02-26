<?php

namespace Bethel\CourseViewBundle\Services;

use Bethel\EntityBundle\Entity\Course;
use Bethel\EntityBundle\Entity\CourseCode;
use Bethel\EntityBundle\Entity\Semester;
use Bethel\EntityBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class PopulateCourseService {

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param array $apiCourse
     * @param User|null $professor
     * @param Semester $semester
     * @param CourseCode $courseCode
     * @return Course
     */
    public function populate($apiCourse, Semester $semester, CourseCode $courseCode, User $professor = null) {
        // look up the course in the database
        /** @var $courseRepository \Bethel\EntityBundle\Entity\CourseRepository */
        $courseRepository = $this->em->getRepository('BethelEntityBundle:Course');
        $course = $courseRepository->findOneBy(
            array(
                'crn' => $apiCourse['crn'],
                'semester' => $semester->getId()
            )
        );

        // if it doesn't already exist, we need to add it and persist it
        if(!$course) {
            $course = new Course();

            $course
                ->setBeginDate(new \DateTime($apiCourse['beginDate']))
                ->setCourseNum($apiCourse['cNumber'])
                ->setCrn($apiCourse['crn'])
                ->setDept($apiCourse['subject'])
                ->setEndDate(new \DateTime($apiCourse['endDate']))
                ->setMeetingDay($apiCourse['meetingDay'])
                ->setSemester($semester)
                ->setCourseCode($courseCode)
                ->setTitle($apiCourse['title'])
                ->setSection($apiCourse['section']);

            if(isset($apiCourse['beginTime']) && isset($apiCourse['endTime'])) {
                $course
                    ->setBeginTime(new \DateTime($apiCourse['beginTime']))
                    ->setEndTime(new \DateTime($apiCourse['endTime']));
            }

            if (isset($apiCourse['enrolled']) && isset($apiCourse['room'])) {
                $course
                    ->setNumAttendees($apiCourse['enrolled'])
                    ->setRoom($apiCourse['room']);
            }

            if($professor) {
                $course->addProfessor($professor);
            }
            $this->em->persist($course);
            $this->em->flush();
        } else {
            if(
                isset($apiCourse['beginTime'])
                && isset($apiCourse['endTime'])
                && isset($apiCourse['enrolled'])
                && isset($apiCourse['room'])
            ) {
                $course
                    ->setBeginTime(new \DateTime($apiCourse['beginTime']))
                    ->setEndTime(new \DateTime($apiCourse['endTime']))
                    ->setNumAttendees($apiCourse['enrolled'])
                    ->setRoom($apiCourse['room']);
            }
            $this->em->persist($course);
            $this->em->flush();
        }

        if(!$course->getCourseCode()) {
            $course->setCourseCode($courseCode);
            $this->em->persist($course);
            $this->em->flush();
        }

        return $course;
    }

}