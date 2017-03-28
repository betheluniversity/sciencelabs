<?php

namespace Bethel\EntityBundle\Form\Handler;

use Bethel\EntityBundle\Entity\Schedule;
use Bethel\EntityBundle\Entity\Session;
use Bethel\EntityBundle\Entity\TutorSchedule;
use Bethel\EntityBundle\Entity\TutorSession;
use Bethel\EntityBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;

class ScheduleFormHandler {
    protected $em;
    protected $requestStack;
    protected $sessionFormHandler;

    public function __construct(EntityManager $em, RequestStack $requestStack, SessionFormHandler $sessionFormHandler) {
        $this->em = $em;
        $this->request = $requestStack->getCurrentRequest();
        $this->sessionFormHandler = $sessionFormHandler;
    }

    public function process(Form $form) {
        if('POST' !== $this->request->getMethod()) {
            return false;
        }

        $form->submit($this->request);

        if($form->isValid()) {
            return $this->processValidForm($form);
        }

        return false;
    }

    /**
     * Processes the valid form
     *
     * @param Form $form
     * @return array
     */
    public function processValidForm(Form $form)
    {

        /** @var $schedule \Bethel\EntityBundle\Entity\Schedule */
        $schedule = $form->getData();

        // Handling tutors
        $submittedTutors = $form->get('tutors')->getData();
        if(!(is_array($submittedTutors))) {
            $submittedTutors = $submittedTutors->toArray();
        }
        /** @var \Doctrine\Common\Collections\ArrayCollection $leadTutors */
        $leadTutors = $form->get('leadTutors')->getData();
        if(!(is_array($leadTutors))) {
            $leadTutors = $leadTutors->toArray();
        }

        /** @var \Doctrine\Common\Collections\ArrayCollection $courseCodes */
        $courseCodes = $form->get('coursecodes')->getData();

        $scheduleRepository = $this->em->getRepository('BethelEntityBundle:Schedule');
        /** @var $anotherScheduleWithSameName \Bethel\EntityBundle\Entity\Schedule */
        $anotherScheduleWithSameName = $scheduleRepository->findBy(array('name' => $form->get('name')->getData()));

        if(count($leadTutors) < 1) {
            return array(
                'success' => false,
                'message' => 'You must select a lead tutor',
                'form' => $form
            );
        } elseif($courseCodes->isEmpty()) {
            return array(
                'success' => false,
                'message' => 'You must select at least one course code',
                'form' => $form
            );
        }
        elseif( sizeof($anotherScheduleWithSameName) > 0 ) {
            return array(
                'success' => false,
                'message' => 'You must choose a unique title',
                'form' => $form
            );
        }

        $userRepository = $this->em->getRepository('BethelEntityBundle:User');
        if($schedule->getId()) {
            $currentTutors = $userRepository->getNonLeadScheduleTutors($schedule);
            $currentLeads = $userRepository->getLeadScheduleTutors($schedule);
        } else {
            $currentTutors = null;
            $currentLeads = null;
        }

        // If everything checks out, add entity relationships to our schedule
        $this->addTutorsToSchedule($schedule, $submittedTutors, $currentTutors);
        $this->addLeadTutorsToSchedule($schedule, $leadTutors, $currentLeads);
        foreach($courseCodes as $coursecode) {
            $schedule->addCourseCode($coursecode);
        }

        $semesterRepository = $this->em->getRepository('BethelEntityBundle:Semester');

        /** @var $activeSemester \Bethel\EntityBundle\Entity\Semester */
        $activeSemester = $semesterRepository->findOneBy(array('active' => true));

        if($form->get('term')->getData() == $activeSemester->getTerm()) {
            $scheduleCreationResult = $this->createSessionsForSchedule($form, $schedule, $activeSemester);
            $scheduleCreationResult['schedule'] = $schedule;
            // We only want to save changes to the schedule if the sessions
            // associated with it were created successfully
            if($scheduleCreationResult['success'] == true) {
                $this->em->persist($schedule);
                $this->em->flush();
                return $scheduleCreationResult;
            } else {
                return $scheduleCreationResult;
            }
        } else {
            $this->em->persist($schedule);
            $this->em->flush();
            $scheduleCreationResult = array(
                'success' => true,
                'message' => 'The ' . $schedule->__toString() . ' schedule was successfully created, but no associated sessions were created',
                'schedule' => $schedule
            );
            return $scheduleCreationResult;
        }
    }

    /**
     * Logic for creating all of the necessary sessions when a schedule is created
     * we need to look at the date range that the semester covers and create sessions
     * based on that. We'll also create a TutorSession for each one of these sessions
     * based on the tutors that have been selected for the schedule.
     *
     * @param Form $form
     * @param \Bethel\EntityBundle\Entity\Schedule $schedule
     * @param \Bethel\EntityBundle\Entity\Semester $activeSemester
     * @return array
     */
    private function createSessionsForSchedule($form, $schedule, $activeSemester) {
        $scheduleDOW = $schedule->getDayOfWeek();

        // Make sure we're not creating sessions in the past
        $today = new \DateTime('now');
        $semesterStart = $activeSemester->getStartDate();
        $interval = date_diff($semesterStart, $today);

        // If the gap between today and the start of the semester is negative
        // (i.e. semester start is after today) we start at the beginning of
        // the semester. Otherwise, we start from today.
        if($interval->format('%R%a') < 0) {
            $scheduleDate = $this->getDOWDifference($semesterStart, $scheduleDOW);
        } else {
            $scheduleDate = $this->getDOWDifference($today, $scheduleDOW);
        }

        // Handling tutors
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


        if(count($leadTutors) < 1) {
            return array(
                'success' => false,
                'message' => 'You must select a lead tutor',
                'form' => $form
            );
        } elseif($courseCodes->isEmpty()) {
            return array(
                'success' => false,
                'message' => 'You must select at least one course code',
                'form' => $form
            );
        }

        // First we need to get rid of all future sessions associated with this schedule
        // since we will be populating new ones based on the new submission.

        /** @var $sessionRepository \Bethel\EntityBundle\Entity\SessionRepository */
        $sessionRepository = $this->em->getRepository('BethelEntityBundle:Session');

        $futureSessions = $sessionRepository->getSessionsInDateRange($scheduleDate, $activeSemester->getEndDate());

        if(!empty($futureSessions)) {
            // We need to coerce our results array into an ArrayCollection object
            // so that we can use Doctrine filters on it.
            $futureSessionCollection = new ArrayCollection($futureSessions);

            $criteria = Criteria::create()
                ->where(Criteria::expr()->eq('schedule', $schedule))
                ->andWhere(Criteria::expr()->isNull('startTime'));

            $futureScheduleSessions = $futureSessionCollection->matching($criteria);

            foreach($futureScheduleSessions as $session) {
                $this->em->remove($session);
            }

            $this->em->flush();
        }

        while($scheduleDate->setTime(0,0,0) <= $activeSemester->getEndDate()) {
            $session = new Session();

            $session->setSemester($activeSemester);
            $session->setName($form->get('name')->getData());
            $session->setSchedule($schedule);
            $session->setDate(clone $scheduleDate);
            foreach($courseCodes as $coursecode) {
                $session->addCourseCode($coursecode);
            }
            $session->setRoom($form->get('room')->getData());
            $session->setSchedStartTime($form->get('startTime')->getData());
            $session->setSchedEndTime($form->get('endTime')->getData());

            $this->em->persist($session);
            $this->em->flush();

            $this->sessionFormHandler->addTutorsToSession($session, $submittedTutors);
            $this->sessionFormHandler->addLeadTutorsToSession($session, $leadTutors);

            // Add 7 days
            $scheduleDate->add(new \DateInterval('P7D'));
        }

        return array(
            'success' => true,
            'message' => 'The ' . $schedule->__toString() . ' schedule has been successfully edited'
        );
    }

    /**
     * @param \DateTime $before
     * @param int $dayOfWeek
     *
     * @return \DateTime
     */
    private function getDOWDifference($before, $dayOfWeek) {
        $beforeDOW = $before->format('w');

        $dateInterval = ($beforeDOW <= $dayOfWeek)
            ? new \DateInterval('P' . ($dayOfWeek - $beforeDOW) . 'D')
            : new \DateInterval('P' . (7 - $beforeDOW + $dayOfWeek) . 'D');

        $newDate = clone $before;
        return $newDate->add($dateInterval);
    }

    /**
     * Modifies the lead tutor on a session based on a submitted lead tutor.
     * Creates a TutorSession entity with the "lead" flag set to true. If there
     * is another lead tutor already, we remove that session. If the submitted
     * tutor already has a session
     *
     * @param Schedule $schedule
     * @param array $leadTutors
     * @param array $currentLeads
     */
    private function addLeadTutorsToSchedule(Schedule $schedule, $leadTutors, $currentLeads = null) {
        $roleRepository = $this->em->getRepository('BethelEntityBundle:Role');
        $tutorScheduleRepository = $this->em->getRepository('BethelEntityBundle:TutorSchedule');

        // We can ignore the intersection of the two results, so all we need to
        // do is find the tutors who are in the database but weren't submitted ...
        $removeTutors = $currentLeads ? array_diff($currentLeads, $leadTutors) : null;
        // ... and the tutors who weren't in the database who were submitted
        $addTutors = $currentLeads ? array_diff($leadTutors, $currentLeads) : $leadTutors;

        if($removeTutors) {
            foreach($removeTutors as $removeTutor) {
                $removeTutorSchedule = $tutorScheduleRepository->findOneBy(array(
                    'schedule' => $schedule,
                    'tutor' => $removeTutor
                ));
                $this->em->remove($removeTutorSchedule);
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

            $nonLeadSchedule = $tutorScheduleRepository->findOneBy(array('schedule' => $schedule, 'tutor' => $leadTutor, 'lead' => false));

            if($nonLeadSchedule) {
                // If this tutor already was associated with the session, but not as a lead
                // we just need to switch the flag
                $leadTutorSchedule = $nonLeadSchedule;
                $leadTutorSchedule->setLead(true);
            } else {
                $leadTutorSchedule = new TutorSchedule();
                $leadTutorSchedule->setSchedule($schedule);
                $leadTutorSchedule->setTutor($leadTutor);
                $leadTutorSchedule->setSchedTimeIn($schedule->getStartTime());
                $leadTutorSchedule->setSchedTimeOut($schedule->getEndTime());
                $leadTutorSchedule->setLead(true);
            }

            $this->em->persist($leadTutorSchedule);
            $this->em->flush();
        }
    }

    /**
     * @param Schedule $schedule
     * @param array $submittedTutors
     * @param array $currentTutors the tutors currently associated with the session
     */
    private function addTutorsToSchedule(Schedule $schedule, $submittedTutors, $currentTutors = null) {

        $tutorScheduleRepository = $this->em->getRepository('BethelEntityBundle:TutorSchedule');

        // We can ignore the intersection of the two results, so all we need to
        // do is find the tutors who are in the database but weren't submitted ...
        $removeTutors = $currentTutors ? array_diff($currentTutors, $submittedTutors) : null;
        // ... and the tutors who weren't in the database who were submitted
        $addTutors = $currentTutors ? array_diff($submittedTutors, $currentTutors) : $submittedTutors;

        if($removeTutors) {
            foreach($removeTutors as $removeTutor) {
                $removeTutorSchedule = $tutorScheduleRepository->findOneBy(array('schedule' => $schedule, 'tutor' => $removeTutor));
                $this->em->remove($removeTutorSchedule);
                $this->em->flush();
            }
        }

        foreach($addTutors as $addTutor) {

            $tutorSchedule = new TutorSchedule();
            $tutorSchedule->setSchedule($schedule);
            $tutorSchedule->setTutor($addTutor);
            $tutorSchedule->setSchedTimeIn($schedule->getStartTime());
            $tutorSchedule->setSchedTimeOut($schedule->getEndTime());
            $tutorSchedule->setLead(false);

            $this->em->persist($tutorSchedule);
            $this->em->flush();
        }
    }
} 