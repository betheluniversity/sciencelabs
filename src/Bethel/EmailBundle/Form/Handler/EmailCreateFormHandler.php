<?php

namespace Bethel\EmailBundle\Form\Handler;

use Bethel\EntityBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RequestStack;

class EmailCreateFormHandler {
    protected $em;
    protected $mailer;
    protected $appTitle;
    protected $requestStack;

    public function __construct(EntityManager $em, \Swift_Mailer $mailer, $appTitle, RequestStack $requestStack) {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->appTitle = $appTitle;
        $this->request = $requestStack->getCurrentRequest();
    }

    public function process(Form $form) {
        if('POST' !== $this->request->getMethod()) {
            return false;
        }

        $form->submit($this->request);

        $data = $form->getData();
        if(
            count($data['role']) == 0
            && count($data['cc']) == 0
            && count($data['bcc']) == 0
        ) {
            $form->addError(new FormError('You must select some recipients.'));
        }

        if($form->isValid()) {
            return $this->processValidForm($form);
        }

        return $form;
    }

    /**
     * Processes the valid form
     *
     * @param Form $form
     * @return \Bethel\EntityBundle\Entity\Session
     */
    public function processValidForm(Form $form) {

        $userRepository = $this->em->getRepository('BethelEntityBundle:User');
        /** @var $session \Bethel\EntityBundle\Entity\Session */
        $emailData = $form->getData();

        // text
        $subject = $emailData['subject'];
        // text
        $message = $emailData['message'];

        $roleRecipients = array();
        /** @var \Bethel\EntityBundle\Entity\Role $role */
        foreach($emailData['role'] as $role) {
            $users = $userRepository->getUsersByRole(
                $role->getName()
            );

            $roleUserEmails = $this->buildEmailList($users);
            $roleRecipients = array_merge($roleRecipients,$roleUserEmails);
        }

        $roleRecipients = array_unique($roleRecipients);
        $ccUsers = $this->buildEmailList($emailData['cc']);
        $bccUsers = $this->buildEmailList($emailData['bcc']);

        $allUsers = array_merge($bccUsers,$roleRecipients,$ccUsers);
        $allUsers = array_unique($allUsers);

        foreach( $allUsers as $user){
            $message = \Swift_Message::newInstance()
                ->setSubject('{' . $this->appTitle . '} ' . $subject)
                ->setFrom('noreply@bethel.edu')
                ->setTo($user)
                ->setBody($message,'text/plain')
            ;
            $this->mailer->send($message);
        }

        return $form;
    }

    private function buildEmailList($users) {
        $emailList = array();
        /** @var \Bethel\EntityBundle\Entity\User $user */
        foreach($users as $user) {
            if( strpos($user->getEmail(), '@') !== false )
                array_push($emailList, $user->getEmail());
        }
        return $emailList;
    }

}