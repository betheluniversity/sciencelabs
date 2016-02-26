<?php

namespace Bethel\ScheduleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Bethel\EntityBundle\Entity\Schedule;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ScheduleController extends Controller
{
    // Inserting a new Session into the DB
    // INSERT INTO Session (date, startTime, endTime, students, tutors, leadTutor, schedule, open)
    // VALUES (CURDATE(), '12:00:00', '3:00:00', 'Student', 'Tutor', 'Lead Tutor', 'Schedule', 1);

    /**
     * @Rest\View
     */
    public function allAction() {
        $scheduleRepo = $this->getDoctrine()->getRepository('BethelEntityBundle:Schedule');
        $schedules = $scheduleRepo->findAll();

        return array('schedules' => $schedules);
    }

    // XXX: the MaxDepth annotation is actually a huge pain.
    // https://github.com/schmittjoh/serializer/issues/61
    // https://github.com/FriendsOfSymfony/FOSRestBundle/pull/523

    // Take a look at this documentation about exclusion strategies for the
    // serializer that FOSRestBundle uses.
    // http://jmsyst.com/libs/serializer/master/cookbook/exclusion_strategies

    /**
     * @Rest\View(serializerGroups={"scheduleList"})
     */
    public function getAction($id) {
        $scheduleRepo = $this->getDoctrine()->getRepository('BethelEntityBundle:Schedule');
        $schedule = $scheduleRepo->findOneById($id);

        if (!$schedule instanceof Schedule) {
            throw new NotFoundHttpException('Schedule not found');
        }

        return array('schedule' => $schedule);
    }

}