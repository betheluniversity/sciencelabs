<?php

namespace Bethel\EntityBundle\Form\Handler;

use Bethel\EntityBundle\Entity\TutorSession;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;

class SessionFormHandler {
    protected $em;
    protected $requestStack;

    public function __construct(EntityManager $em, RequestStack $requestStack) {
        $this->em = $em;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @param Form $form
     * @param string $actionString
     * @return \Bethel\EntityBundle\Entity\Session|bool
     */
    public function process(Form $form, $actionString) {
        if('POST' !== $this->request->getMethod()) {
            return false;
        }

        $form->submit($this->request);

        if($form->isValid()) {
            return $this->processValidForm($form, $actionString);
        }

        return false;
    }

    /**
     * Processes the valid form
     *
     * @param Form $form
     * @param string $actionString
     * @return \Bethel\EntityBundle\Entity\Session
     */
    public function processValidForm(Form $form, $actionString) {
        /** @var $session \Bethel\EntityBundle\Entity\Session */
        $session = $form->getData();

        /** @var $userRepository \Bethel\EntityBundle\Entity\UserRepository */
        $userRepository = $this->em->getRepository('BethelEntityBundle:User');
        // Get the tutors who were submitted to be associated with the session
        $submittedTutors = $form->get('tutors')->getData();

        if(!(is_array($submittedTutors))) {
            $submittedTutors = $submittedTutors->toArray();
        }

        $leadTutors = $form->get('leadTutors')->getData();
        if(!(is_array($leadTutors))) {
            $leadTutors = $leadTutors->toArray();
        }

        /** @var \Doctrine\Common\Collections\ArrayCollection $courseCodes */
        $courseCodes = $form->get('coursecodes')->getData();

        // only check for lead tutors if the session has not happened
        if(count($leadTutors) < 1 && $session->getDate()->getTimestamp() > time() ) {
            return array(
                'success' => false,
                'message' => 'You must select a lead tutor',
                'form' => $form
            );
        } elseif(count($courseCodes) < 1) {
            return array(
                'success' => false,
                'message' => 'You must select at least one course code',
                'form' => $form
            );
        } elseif($form->get('date')->isEmpty()) {
            return array(
                'success' => false,
                'message' => 'You must select a date',
                'form' => $form
            );
        }
        elseif( $form->get('semester')->isEmpty() ){
            return array(
                'success' => false,
                'message' => 'You must select a Semester',
                'form' => $form
            );
        }

        $semester = $session->getSemester();
        $session->setSemester($semester);
        $this->em->persist($session);
        $this->em->flush();

        // Get all of the non-lead tutors currently associated with the session
        $currentTutors = $userRepository->getNonLeadSessionTutors($session);
        // Get all of the lead tutors currently associated with the session
        $currentLeads = $userRepository->getLeadSessionTutors($session);
        $this->addTutorsToSession($session, $submittedTutors, $currentTutors);
        $this->addLeadTutorsToSession($session, $leadTutors, $currentLeads);

        return array(
            'success' => true,
            'message' => 'The ' . $session->__toString() . ' session has been successfully ' . $actionString,
            'session' => $session
        );
    }

    /**
     * Modifies the lead tutors on a session based on an array of users.
     * Creates a TutorSession entity with the "lead" flag set to true.
     * If the submitted tutor already has a session, we flip the "lead" flag.
     *
     * @param \Bethel\EntityBundle\Entity\Session $session
     * @param array $leadTutors
     * @param array|null $currentLeads
     */
    public function addLeadTutorsToSession($session, $leadTutors, $currentLeads = null) {
        $roleRepository = $this->em->getRepository('BethelEntityBundle:Role');
        $tutorSessionRepository = $this->em->getRepository('BethelEntityBundle:TutorSession');

        // We can ignore the intersection of the two results, so all we need to
        // do is find the tutors who are in the database but weren't submitted ...
        $removeTutors = $currentLeads ? array_diff($currentLeads, $leadTutors) : null;
        // ... and the tutors who weren't in the database who were submitted
        $addTutors = $currentLeads ? array_diff($leadTutors, $currentLeads) : $leadTutors;

        if($removeTutors) {
            foreach($removeTutors as $removeTutor) {
                $removeTutorSession = $tutorSessionRepository->findOneBy(array(
                    'session' => $session,
                    'tutor' => $removeTutor
                ));
                $this->em->remove($removeTutorSession);
                $this->em->flush();
            }
        }

        /** @var \Bethel\EntityBundle\Entity\User $leadTutor */
        foreach($addTutors as $leadTutor) {
            if(!$leadTutor->hasRole('ROLE_LEAD_TUTOR')) {
                // If the designated lead tutor is not already a lead tutor
                // in the system, make it so. -- Jean Luc Picard
                $leadTutor->addRole($roleRepository->findOneBy(array('role' => 'ROLE_LEAD_TUTOR')));
                $this->em->persist($leadTutor);
                $this->em->flush();
            }

            $nonLeadSession = $tutorSessionRepository->findOneBy(array('session' => $session, 'tutor' => $leadTutor, 'lead' => false));

            if($nonLeadSession) {
                // If this tutor already was associated with the session, but not as a lead
                // we just need to switch the flag
                $leadTutorSession = $nonLeadSession;
                $leadTutorSession->setLead(true);
            } else {
                $leadTutorSession = new TutorSession();
                $leadTutorSession->setSession($session);
                $leadTutorSession->setTutor($leadTutor);
                $leadTutorSession->setSchedTimeIn($session->getSchedStartTime());
                $leadTutorSession->setSchedTimeOut($session->getSchedEndTime());
                $leadTutorSession->setLead(true);
            }

            $this->em->persist($leadTutorSession);
            $this->em->flush();
        }
    }

    /**
     * @param array $currentTutors the tutors currently associated with the session
     * @param array $submittedTutors
     * @param \Bethel\EntityBundle\Entity\Session $session
     */
    public function addTutorsToSession($session, $submittedTutors, $currentTutors = null) {

        $tutorSessionRepository = $this->em->getRepository('BethelEntityBundle:TutorSession');

        // We can ignore the intersection of the two results, so all we need to
        // do is find the tutors who are in the database but weren't submitted ...
        $removeTutors = $currentTutors ? array_diff($currentTutors, $submittedTutors) : null;
        // ... and the tutors who weren't in the database who were submitted
        $addTutors = $currentTutors ? array_diff($submittedTutors, $currentTutors) : $submittedTutors;

        if($removeTutors) {
            foreach($removeTutors as $removeTutor) {
                $removeTutorSession = $tutorSessionRepository->findOneBy(array('session' => $session, 'tutor' => $removeTutor));
                $this->em->remove($removeTutorSession);
                $this->em->flush();
            }
        }

        foreach($addTutors as $addTutor) {

            $tutorSession = new TutorSession();
            $tutorSession->setSession($session);
            $tutorSession->setTutor($addTutor);
            $tutorSession->setSchedTimeIn($session->getSchedStartTime());
            $tutorSession->setSchedTimeOut($session->getSchedEndTime());
            $tutorSession->setLead(false);

            $this->em->persist($tutorSession);
            $this->em->flush();
        }
    }
} 