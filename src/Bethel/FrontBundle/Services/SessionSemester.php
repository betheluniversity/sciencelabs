<?php

namespace Bethel\FrontBundle\Services;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;

class SessionSemester {

    private $session;
    private $em;

    public function __construct(EntityManager $em) {
        $this->em = $em;
    }

    public function create(Session $session) {
        $this->session = $session;

        return $this;
    }

    /**
     * @param \Bethel\EntityBundle\Entity\Semester $semester
     */
    public function setSessionSemester($semester) {
        $id = $semester->getId();
        $this->session->set('semesterId', $id);
    }

    /**
     * @return \Bethel\EntityBundle\Entity\Semester
     */
    public function getSessionSemester() {

        $semesterRepository = $this->em->getRepository('BethelEntityBundle:Semester');
        if ($this->session->has('semesterId')) {
            $semester = $semesterRepository->findOneBy(array(
                    'id' => $this->session->get('semesterId')
                )
            );
        } else {
            $semester = $semesterRepository->findOneBy(array('active' => true));
        }
        return $semester;
    }
} 