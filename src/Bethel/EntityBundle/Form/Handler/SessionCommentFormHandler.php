<?php

namespace Bethel\EntityBundle\Form\Handler;

use Bethel\EntityBundle\Entity\TutorSession;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;

class SessionCommentFormHandler {
    protected $em;
    protected $requestStack;

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
     * @return \Bethel\EntityBundle\Entity\Session
     */
    public function processValidForm(Form $form) {
        /** @var $session \Bethel\EntityBundle\Entity\Session */
        $session = $form->getData();

        $this->em->persist($session);
        $this->em->flush();

        return $session;
    }
} 