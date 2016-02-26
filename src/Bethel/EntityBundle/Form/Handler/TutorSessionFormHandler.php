<?php

namespace Bethel\EntityBundle\Form\Handler;

use Bethel\EntityBundle\Entity\TutorSession;
use Bethel\EntityBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class TutorSessionFormHandler {
    protected $em;
    protected $requestStack;
    protected $session;

    public function __construct(EntityManager $em, RequestStack $requestStack, Session $session) {
        $this->em = $em;
        $this->request = $requestStack->getCurrentRequest();
        $this->session = $session;
    }

    public function process(Form $form, User $tutor) {
        if('POST' !== $this->request->getMethod()) {
            return false;
        }

        $form->submit($this->request);

        // If the current user is a tutor, trying to add someone other than
        // herself to the session, we need to disallow that
        if($tutor->hasRole('ROLE_TUTOR') && !$tutor->hasRole('ROLE_LEAD_TUTOR') && $form->getData()->getTutor() != $tutor) {
            $this->session->getFlashBag()->add(
                'warning',
                'You may not add someone other than yourself to a session.'
            );
            return false;
        }

        if($form->isValid()) {
            return $this->processValidForm($form);
        }

        return false;
    }

    /**
     * Processes the valid form
     *
     * @param Form $form
     * @return \Bethel\EntityBundle\Entity\TutorSession
     */
    public function processValidForm(Form $form) {
        /** @var $session \Bethel\EntityBundle\Entity\Session */
        $tutorSession = $form->getData();

        $this->em->persist($tutorSession);
        $this->em->flush();

        return $tutorSession;
    }
} 