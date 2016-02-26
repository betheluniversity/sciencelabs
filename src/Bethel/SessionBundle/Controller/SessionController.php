<?php

namespace Bethel\SessionBundle\Controller;

use Bethel\EntityBundle\Entity\Session;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/session")
 */
class SessionController extends Controller
{
    /**
     * @Route("/")
     * @Template("BethelSessionBundle:Default:index.html.twig")
     */
    public function indexAction() {
        return array('user' => $this->getUser());
    }

    /**
     * @Rest\View(serializerGroups={"sessionDetails"}, serializerEnableMaxDepthChecks=false)
     * @param integer $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function getAction($id) {
        $sessionRepo = $this->getDoctrine()->getRepository('BethelEntityBundle:Session');
        $session = $sessionRepo->findOneById($id);

        if(!$session instanceof Session) {
            throw new NotFoundHttpException('Session not found');
        }

        return array('session' => $session);
    }

    /**
     * @Rest\View(serializerGroups={"tutorSchedules"}, serializerEnableMaxDepthChecks=false)
     *
     * @param $start
     * @param $end
     * @return array
     * @throws NotFoundHttpException
     */
    public function getTutorSchedulesAction($start, $end) {
        /** @var $sessionRepo \Bethel\EntityBundle\Entity\SessionRepository */
        $sessionRepo = $this->getDoctrine()->getRepository('BethelEntityBundle:Session');
        $start = new \DateTime($start);
        $end = new \DateTime($end);
        $sessions = $sessionRepo->getSessionsInDateRange($start, $end);

        $rangeTutorSessions = array();
        /** @var $session \Bethel\EntityBundle\Entity\Session */
        foreach($sessions as $session) {
            $tutorSessions = $session->getTutorSessions();
            foreach($tutorSessions as $tutorSession) {
                array_push($rangeTutorSessions,$tutorSession);
            }
        }

        if(!is_array($rangeTutorSessions)) {
            throw new NotFoundHttpException('No tutor sessions found');
        }

        return array('tutorSessions' => $rangeTutorSessions);
    }
}