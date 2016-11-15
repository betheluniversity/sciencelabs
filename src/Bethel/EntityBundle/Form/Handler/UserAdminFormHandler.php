<?php

namespace Bethel\EntityBundle\Form\Handler;

use Bethel\EntityBundle\Entity\Schedule;
use Bethel\EntityBundle\Entity\Session;
use Bethel\EntityBundle\Entity\TutorSchedule;
use Bethel\EntityBundle\Entity\TutorSession;
use Bethel\EntityBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;

class UserAdminFormHandler {
    protected $em;
    protected $requestStack;
    protected $sessionFormHandler;

    public function __construct(EntityManager $em, RequestStack $requestStack) {
        $this->em = $em;
        $this->request = $requestStack->getCurrentRequest();
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
        $userData = $form->getData();

        $username = $form->get('username')->getData();

        $userRepository = $this->em->getRepository('BethelEntityBundle:User');
        /** @var $user \Bethel\EntityBundle\Entity\User */
        $user = $userRepository->findOneByUsername($username);

        $courses = $form->get('courses')->getData();

        /** @var $course \Bethel\EntityBundle\Entity\Course */
        $courseRepository = $this->em->getRepository('BethelEntityBundle:Course');
        $user->removeAllCourseViewers();
        $this->em->persist($user);
        $this->em->flush();
        foreach( $courses as $course ) {
            $course->addCourseViewer($user);
        }

        $scheduleCreationResult = array(
            'success' => true,
        );
        return $scheduleCreationResult;
    }
} 