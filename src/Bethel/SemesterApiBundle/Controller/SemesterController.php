<?php

namespace Bethel\SemesterApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Bethel\EntityBundle\Entity\Semester;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SemesterController extends Controller
{
    // Inserting a new Session into the DB
    // INSERT INTO Session (date, startTime, endTime, students, tutors, leadTutor, schedule, open)
    // VALUES (CURDATE(), '12:00:00', '3:00:00', 'Student', 'Tutor', 'Lead Tutor', 'Schedule', 1);

    /**
     * @Rest\View
     */
    public function allAction() {
        $semesterRepo = $this->getDoctrine()->getRepository('BethelEntityBundle:Semester');
        $semesters = $semesterRepo->findAll();

        return array('semesters' => $semesters);
    }

    /**
     * @Rest\View
     */
    public function getAction($id) {
        $semesterRepo = $this->getDoctrine()->getRepository('BethelEntityBundle:Semester');
        $semester = $semesterRepo->findOneById($id);

        if (!$semester instanceof Semester) {
            throw new NotFoundHttpException('Semester not found');
        }

        return array('semester' => $semester);
    }

    /**
     * @Rest\View
     */
    public function getActiveAction() {
        $semesterRepo = $this->getDoctrine()->getRepository('BethelEntityBundle:Semester');
        $semester = $semesterRepo->findOneByActive(true);

        if (!$semester instanceof Semester) {
            throw new NotFoundHttpException('Semester not found');
        }

        return array('semester' => $semester);
    }

    /**
     * get the current view semester
     *
     * @Rest\View
     */
    public function getViewAction() {

        $semesterRepository = $this->getDoctrine()->getRepository('BethelEntityBundle:Semester');
        if ($this->get('session')->has('semesterId')) {
            $semester = $semesterRepository->findOneBy(array(
                    'id' => $this->get('session')->get('semesterId')
                )
            );
        } else {
            $semester = $semesterRepository->findOneBy(array('active' => true));
        }
        return array('semester' => $semester);
    }

    /**
     * @Rest\View
     */
    public function getByYearAndTermAction($year, $term) {
        $semesterRepo = $this->getDoctrine()->getRepository('BethelEntityBundle:Semester');
        $semester = $semesterRepo->findOneBy(array('year' => $year, 'term' => $term));

        if (!$semester instanceof Semester) {
            throw new NotFoundHttpException('Semester not found');
        }

        return array('semester' => $semester);
    }

}