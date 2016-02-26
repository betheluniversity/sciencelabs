<?php

namespace Bethel\ReportViewBundle\Services;

use Bethel\EntityBundle\Entity\Session;
use Bethel\EntityBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class CalebTestService {

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

    private function getTutorCheckinTimes($semester) {
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

        return $semester;
    }
}