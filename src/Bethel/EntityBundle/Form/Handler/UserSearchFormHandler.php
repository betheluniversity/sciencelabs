<?php

namespace Bethel\EntityBundle\Form\Handler;

use Bethel\EntityBundle\Entity\TutorSession;
use Bethel\WsapiBundle\Wsapi\WsRestApi;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;

class UserSearchFormHandler {
    protected $em;
    protected $requestStack;
    protected $wsapi;

    public function __construct(EntityManager $em, RequestStack $requestStack, WsRestApi $wsapi) {
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
        $searchTerms = $form->getData();

        $encode_percent = urlencode('%');
        $usernameResults = $this->wsapi->getUsername($encode_percent . $searchTerms['firstName'], $encode_percent . $searchTerms['lastName'] . $encode_percent);
        return $usernameResults;
    }
}