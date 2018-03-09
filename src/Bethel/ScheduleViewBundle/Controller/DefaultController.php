<?php

namespace Bethel\ScheduleViewBundle\Controller;

use Bethel\EntityBundle\Entity\Schedule;
use Bethel\EntityBundle\Entity\TutorSchedule;
use Bethel\EntityBundle\Form\ScheduleType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Bethel\FrontBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/schedule")
 */
class DefaultController extends BaseController
{
    /**
     * @Route("/populatescheduleswithsessiondata", name="populate_schedules_with_session_data")
     */

    // XXX: TEMP solution to retroactively populate schedules
    // with session data for tutors and course codes
    public function populateSchedulesWithSessionDataAction() {
        $em = $this->getEntityManager();
        $scheduleRepository = $em->getRepository('BethelEntityBundle:Schedule');
        $sessionRepository = $em->getRepository('BethelEntityBundle:Session');

        $schedules = $scheduleRepository->findAll();

        $retString = 'Updated: ';
        foreach($schedules as $schedule) {
            $sessions = $sessionRepository->findBy(array(
                'schedule' => $schedule
            ));

            if(count($sessions) > 0) {
                $session = $sessions[0];
                /** @var \Bethel\EntityBundle\Entity\TutorSession $tutorSession */
                foreach($session->getTutorSessions() as $tutorSession) {
                    if($tutorSession->getSchedTimeIn() && $tutorSession->getSchedTimeOut()) {
                        $tutorSchedule = new TutorSchedule();
                        $tutorSchedule->setTutor($tutorSession->getTutor());
                        $tutorSchedule->setSchedTimeIn($tutorSession->getSchedTimeIn());
                        $tutorSchedule->setSchedTimeOut($tutorSession->getSchedTimeOut());
                        $tutorSchedule->setLead($tutorSession->getLead());
                        $schedule->addTutorSchedule($tutorSchedule);
                        $em->persist($tutorSchedule);
                        $em->flush();
                    }
                }

                /** @var \Bethel\EntityBundle\Entity\CourseCode $courseCode */
                foreach($session->getCourseCodes() as $courseCode) {
                    $schedule->addCourseCode($courseCode);
                }
            }

            $em->persist($schedule);
            $em->flush();

            $retString .= $schedule->__toString() . ', ';
        }

        return new Response($retString, 200);
    }

    /**
     * @Route("/", name="schedule")
     * @Template("BethelScheduleViewBundle:Default:index.html.twig")
     */
    public function viewAction() {
        $em = $this->getEntityManager();
        $semester = $this->getActiveSemester();

        $schedules = $em->getRepository('BethelEntityBundle:Schedule')->getSemesterSchedules($semester);
        $scheduleContainer = array();
        $em->getFilters()->disable('softdeleteable');
        /** @var \Bethel\EntityBundle\Entity\Schedule $schedule */
        foreach($schedules as $schedule) {
            $scheduleId = $schedule->getId();
            $semesterId = $semester->getId();
            $tutorSchedules = $schedule->getTutorSchedules();
            $tutors = array();
            $leadTutors = array();

            $em->getFilters()->enable('softdeleteable');
            $userRepository = $em->getRepository('BethelEntityBundle:User');
            $tutors = $userRepository->getNonLeadScheduleTutors($schedule);
            $leadTutors = $userRepository->getLeadScheduleTutors($schedule);
            $em->getFilters()->disable('softdeleteable');
            
            $scheduleContainer[] = array(
                'tutors' => $tutors,
                'leadTutors' => $leadTutors,
                'schedule' => $schedule
            );
        }
        $em->getFilters()->enable('softdeleteable');
        return array(
            'user' => $this->getUser(),
            'scheduleContainer' => $scheduleContainer,
            'semester' => $semester
        );
    }

    /**
     * @Route("/edit/{id}", name="schedule_edit", defaults={"id" = null})
     * @Route("/create", name="schedule_create", defaults={"id" = null})
     * @ParamConverter("schedule", class="BethelEntityBundle:Schedule")
     * @Template("BethelScheduleViewBundle:Default:edit.html.twig")
     * @param Request $request
     * @param null|Schedule $schedule
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Request $request, $schedule = null) {
        // create a new entity if we're not editing one
        if(!$schedule) {
            $schedule = new Schedule();
        }

        $em = $this->getEntityManager();
        $tutorScheduleRepository = $em->getRepository('BethelEntityBundle:TutorSchedule');
        $userRepository = $em->getRepository('BethelEntityBundle:User');

        $form = $this->createForm(new ScheduleType($this->get('term_validator'), $this->getActiveSemester()), $schedule);

        // Manually setting the data for tutors, since we can't bind directly to the
        // session entity to get this data.
        // Binding entities to query parameters only allowed for entities that have an identifier.
        if($schedule->getId()) {
            $schedLeads = $userRepository->getLeadScheduleTutors($schedule);
            if($schedLeads) {
                $form->get('leadTutors')->setData($schedLeads);
            }

            $schedTutors = $userRepository->getNonLeadScheduleTutors($schedule);

            if($schedTutors) {
                $form->get('tutors')->setData($schedTutors);
            }
        }

        // Handle a POSTed form
        if($request->getMethod() == 'POST') {
            /** @var $formHandler \Bethel\EntityBundle\Form\Handler\ScheduleFormHandler */
            $formHandler = $this->get('schedule_form_handler');

            $submissionResult = $formHandler->process($form);

            if(!$submissionResult['success']) {
                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $submissionResult['message']
                );

                // Return the partially completed form from the handler and render it
                return array(
                    'form' => $submissionResult['form']->createView(),
                    'user' => $this->getUser()
                );
            } else if($submissionResult['success']) {
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $submissionResult['message']
                );

                return $this->redirect($this->generateUrl('schedule'));
            }

            $this->get('session')->getFlashBag()->add(
                'warning',
                'An error occurred. Please contact your administrator.'
            );
        }

        $em->getFilters()->enable('softdeleteable');
        return array(
            'form' => $form->createView(),
            'user' => $this->getUser(),
            'activeSemester' => $this->getActiveSemester()
        );
    }

    /**
     * @Route("/delete/{id}", name="schedule_delete", defaults={"id" = null})
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($id) {
        // TODO: Check schedule usage on SESSIONS before deletion
        $em = $this->getEntityManager();

        $scheduleRepository = $em->getRepository("BethelEntityBundle:Schedule");
        $schedule = $scheduleRepository->find($id);

        if($schedule) {
            $sessionRepository = $em->getRepository('BethelEntityBundle:Session');
            $scheduledSessions = $sessionRepository->findBy(array(
                'schedule' => $schedule,
                'startTime' => null
            ));
            foreach($scheduledSessions as $session) {
                $em->remove($session);
            }
            $em->remove($schedule);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',
                'The ' . $schedule->__toString() . ' Schedule was deleted!'
            );
        } else {
            $this->get('session')->getFlashBag()->add(
                'warning',
                'Schedule does not exist.'
            );
        }

        return $this->redirect($this->generateUrl('schedule'));
    }


}
