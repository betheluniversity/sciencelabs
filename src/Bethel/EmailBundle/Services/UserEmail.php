<?php
/**
 * Created by PhpStorm.
 * User: pms63443
 * Date: 3/11/15
 * Time: 1:06 PM
 */

namespace Bethel\EmailBundle\Services;


use Bethel\EntityBundle\Entity\Role;
use Doctrine\ORM\EntityManager;

class UserEmail {
    private $em;
    private $mailer;
    private $twig;
    private $appTitle;

    private $recipients;
    private $message;
    private $subject;
    private $role;

    public function __construct(EntityManager $em, \Swift_Mailer $mailer, \Twig_Environment $twig, $appTitle) {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->appTitle = $appTitle;
    }

    public function process(Form $form) {
        if('POST' !== $this->request->getMethod()) {
            return false;
        }

        if($form->isValid()) {
            return $this->sendEmail($form);
        }

        $this->session->getFlashBag()->add(
            'warning',
            'Your form was not valid.'
        );
        return false;
    }

    public function create(array $recipients, Role $role, $message, $subject) {
        $this->message = $message;
        $this->recipients = $recipients;
        $this->subject = $subject;
        $this->role = $role;

        return $this;
    }

    /**
     * @return array
     */
    public function sendEmail() {
        /** @var $userRepository \Bethel\EntityBundle\Entity\UserRepository */
        $userRepository = $this->em->getRepository('BethelEntityBundle:User');

        /** @var \Bethel\EntityBundle\Entity\User $recipient */
        foreach($this->recipients as $recipient) {
            $message = \Swift_Message::newInstance()
                ->setSubject('{' . $this->appTitle . '}' . $this->subject)
                ->setFrom('noreply@bethel.edu')
                ->setTo($recipient->getEmail())
                ->setBody(
                    $this->twig->render(
                        'BethelEmailBundle:Email:email.html.twig',
                        array(
                            'recipient' => $recipient,
                            'message' => $this->message
                        )
                    ),
                    'text/html'
                )
            ;
            $this->mailer->send($message);
        }

        return array(
            'recipient' => $this->recipient,
            'message' => $this->message
        );
    }
}