<?php

namespace Bethel\ReportViewBundle\Services;

use Bethel\EntityBundle\Entity\Session;
use Bethel\EntityBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class SessionEmail {

    private $em;
    private $mailer;

    private $recipient;
    private $session;
    private $profCourses;
    private $twig;
    private $appTitle;

    public function __construct(EntityManager $em, \Swift_Mailer $mailer, \Twig_Environment $twig, $appTitle) {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->appTitle = $appTitle;
    }

    public function create(Session $session, User $recipient, array $profCourses = null) {
        $this->session = $session;
        $this->recipient = $recipient;
        $this->profCourses = $profCourses;

        return $this;
    }

    /**
     * @return array
     */
    public function sendEmail($test = null, $is_admin = false) {

        /** @var $sessionRepo \Bethel\EntityBundle\Entity\SessionRepository */
        $sessionRepo = $this->em->getRepository('BethelEntityBundle:Session');
        /** @var $userRepository \Bethel\EntityBundle\Entity\UserRepository */
        $userRepository = $this->em->getRepository('BethelEntityBundle:User');
        /** @var $studentSessionRepository \Bethel\EntityBundle\Entity\StudentSessionRepository */
        $studentSessionRepository = $this->em->getRepository('BethelEntityBundle:StudentSession');

        $attendees = $sessionRepo->getSessionAttendeeTotal($this->session);

        $scheduledTutors = $userRepository->getScheduledTutors($this->session, true);
        $attendeeTutors = $userRepository->getTutorAttendees($this->session, true);

        $tutorAttendance = array();

        // Scheduled attendees
        $tutorAttendance['scheduled'] = $this->getTutorCheckinTimes(array_intersect($attendeeTutors,$scheduledTutors), $this->session);

        // Unscheduled
        $tutorAttendance['unscheduled'] = $this->getTutorCheckinTimes(array_diff($attendeeTutors,$scheduledTutors), $this->session);

        // Absent tutors
        $tutorAttendance['absent'] = $this->getTutorCheckinTimes(array_diff($scheduledTutors,$attendeeTutors), $this->session);


        $otherSessions = null;
        $otherTotal = 0;

        $courseAttendance = array();
        $courseAttendanceTotal = array();
        $courseTotal = 0;

        // If we're dealing with a professor we'll limit the report
        // output to the courses he or she teaches
        if($this->profCourses || is_array($this->profCourses)) {
            $courses = $this->profCourses;
        } else {
            // Student signin grouped by courses
            /** @var $courseRepository \Bethel\EntityBundle\Entity\CourseRepository */
            $courseRepository = $this->em->getRepository('BethelEntityBundle:Course');
            $courses = $courseRepository->getSessionCourses($this->session);
        }

        // Student signin grouped by courses

        $otherSessions = $studentSessionRepository->getSessionOtherAttendance($this->session);
        $otherTotal = count($otherSessions);

        $studentSessions = $studentSessionRepository->getSessionAttendanceForCourses($courses, $this->session, true);

        /** @var $course \Bethel\EntityBundle\Entity\Course */
        foreach($courses as $course) {
            $courseKey = $course->getTitle();
            if($course->getSection()) {
                $courseKey .= ' (Section ' . $course->getSection() . ')';
            }
            $courseKey .= ' (' . $course->getDept() . $course->getCourseNum() . ')';
            $courseAttendance[$courseKey] = $studentSessionRepository->getSessionCourseAttendance($course, $this->session, true);
        }

        // Student signin grouped by courses
        /** @var $courseRepository \Bethel\EntityBundle\Entity\CourseRepository */
        $courseRepository = $this->em->getRepository('BethelEntityBundle:Course');
        $courses = $courseRepository->getSessionCourses($this->session);
        /** @var $course \Bethel\EntityBundle\Entity\Course */
        foreach($courses as $course) {
            $courseKey = $course->getTitle();
            if($course->getSection()) {
                $courseKey .= ' (Section ' . $course->getSection() . ')';
            }
            $courseKey .= ' (' . $course->getDept() . $course->getCourseNum() . ')';
            $sessionCourseAttendanceTotal = $studentSessionRepository->getSessionCourseAttendanceTotal($course, $this->session);

            $courseAttendanceTotal[$courseKey] = array(
                'total' => $sessionCourseAttendanceTotal,
                'id' => $course->getId(),
                'professors' => $course->getProfessorNames()
            );
            $courseTotal += $sessionCourseAttendanceTotal;
        }

        $messageBody = $this->twig->render(
            'BethelReportViewBundle:Email:email.html.twig',
            array(
                'recipient' => $this->recipient,
                'session' => $this->session,
                'studentSessions' => $studentSessions,
                'otherSessions' => $otherSessions,
                'attendees' => $attendees,
                'tutorAttendance' => $tutorAttendance,
                'courseAttendance' => $courseAttendance,
                'courseAttendanceTotal' => $courseAttendanceTotal,
                'courseTotal' => $courseTotal,
                'otherTotal' => $otherTotal,
                'profCourses' =>   $this->profCourses,
                'is_admin' => $is_admin
            )
        );

        if( $test )
            return $messageBody;

        $message = \Swift_Message::newInstance()
            ->setSubject('{' . $this->appTitle . '} ' . $this->session)
            ->setFrom('noreply@bethel.edu')
            ->setTo($this->recipient->getEmail())
            ->setBody(
                $messageBody,
                'text/html'
            )
        ;
        $this->mailer->send($message);

        return array(
            'recipient' => $this->recipient,
            'session' => $this->session,
            'attendees' => $attendees,
            'tutorAttendance' => $tutorAttendance,
            'message' => $messageBody
        );
    }

    private function getTutorCheckinTimes($attendees, $session) {
        /** @var $tutorSessionRepository  */
        $tutorSessionRepository = $this->em->getRepository('BethelEntityBundle:TutorSession');
        $attendeeGroup = array();
        foreach($attendees as $attendee) {
            /** @var \Bethel\EntityBundle\Entity\TutorSession $tutorSession */
            $tutorSession = $tutorSessionRepository->findOneBy(array(
                'tutor' => $attendee,
                'session' => $session
            ));

            array_push($attendeeGroup, array(
                'tutor' => $attendee,
                'timeIn' => $tutorSession->getTimeIn(),
                'timeOut' => $tutorSession->getTimeOut()
            ));
        }

        return $attendeeGroup;
    }
}