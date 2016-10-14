<?php

namespace Bethel\SessionViewBundle\Services;

use Bethel\EntityBundle\Entity\Session;
use Bethel\EntityBundle\Entity\User;
use Bethel\ReportViewBundle\Services\SessionEmail;
use Doctrine\ORM\EntityManager;

class SessionClose {

    private $em;
    private $sessionEmail;

    private $session;

    public function __construct(EntityManager $em, SessionEmail $sessionEmail) {
        $this->em = $em;
        $this->sessionEmail = $sessionEmail;
    }

    /**
     * @param Session $session
     * @param null|string $comment
     * @return Session
     */
    public function close(Session $session, $comment = null) {

        if( $session->getOpen() ) {
            $this->session = $session;
            $this->session->setEndTime(new \DateTime("now"));
            $this->session->setOpen(false);
            if ($comment) {
                $this->session->setComments($comment);
            }
            $this->em->persist($this->session);
            $this->em->flush();

            /** @var $userRepository \Bethel\EntityBundle\Entity\UserRepository */
            $userRepository = $this->em->getRepository('BethelEntityBundle:User');
            $emailRecipients = $userRepository->findBy(array('sendEmail' => true));
            /** @var $sessionEmailer \Bethel\ReportViewBundle\Services\SessionEmail */
            /** @var \Bethel\EntityBundle\Entity\User $emailRecipient */
            foreach ($emailRecipients as $emailRecipient) {
                // Make sure we're only sending to admins and professors
                if ($this->userHasRole($emailRecipient, 'ROLE_ADMIN')) {
                    $sessionEmailer = $this->sessionEmail->create($session, $emailRecipient);
                    $sessionEmailer->sendEmail(null, true);
                } elseif ($this->userHasRole($emailRecipient, 'ROLE_PROFESSOR')) {
                    /** @var \Bethel\EntityBundle\Entity\CourseRepository $courseRepository */
                    $courseRepository = $this->em->getRepository('BethelEntityBundle:Course');

                    $reportCourses = array();
                    $sessionCourses = $courseRepository->getAttendedSessionCourses($session);
                    foreach ($sessionCourses as $course) {
                        if ($course->getProfessors()->contains($emailRecipient))
                            array_push($reportCourses, $course);
                    }

                    // We only want to send professors an email if they teach a course
                    // that at least one student attended for at this session
                    if ($reportCourses) {
                        $sessionEmailer = $this->sessionEmail->create($session, $emailRecipient, $reportCourses);
                        $sessionEmailer->sendEmail();
                    } else {
                        continue;
                    }
                }
            }
            return $session;
        }
        return false;
    }

    private function userHasRole(User $user, $roleString) {
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
}