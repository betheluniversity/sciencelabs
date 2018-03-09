<?php

namespace Bethel\EntityBundle\Form\Handler;

use Bethel\EntityBundle\Entity\TutorSession;
use Bethel\WSAPIBundle\Controller\WSAPIController;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;

class UserCreateFormHandler {
    protected $em;
    protected $requestStack;
    protected $wsapi;

    public function __construct(EntityManager $em, RequestStack $requestStack, WSAPIController $wsapi) {
        $this->em = $em;
        $this->request = $requestStack->getCurrentRequest();
        $this->wsapi = $wsapi;
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
        $userData = $form->getData();
        $userRoleCollection = $userData['roles'];
        $userRoles = $userRoleCollection->toArray();

        /** @var $userRepository \Bethel\EntityBundle\Entity\UserRepository */
        $userRepository = $this->em->getRepository('BethelEntityBundle:User');

        $user = $userRepository->createUser($userData['username'],$userRoles,array());

        return $user;
    }
}