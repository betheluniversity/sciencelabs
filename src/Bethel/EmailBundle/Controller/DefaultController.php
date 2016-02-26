<?php

namespace Bethel\EmailBundle\Controller;

use Bethel\EmailBundle\Form\EmailType;
use Bethel\FrontBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Bethel\EmailBundle\Form\Handler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @Route("/email")
 */
class DefaultController extends BaseController
{
    /**
     * @Route("/create", name="email_create")
     * @Template("BethelEmailBundle:Default:create_email.html.twig")
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createEmailAction() {

        $emailForm = $this->createForm(new EmailType(), null, array(
            'action' => $this->generateUrl('email_confirm')
        ));

        $emailFormView = $emailForm->createView();

        return array(
            'user' => $this->getUser(),
            'emailForm' => $emailFormView
        );
    }

    /**
     * @Route("/confirm", name="email_confirm")
     * @Template("BethelEmailBundle:Default:confirm_email.html.twig")
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function confirmEmailAction(Request $request) {
        if($request->getMethod() == 'POST') {
            $emailForm = $this->createForm(new EmailType(), null);
            $referer = array_slice(explode('/',$request->headers->get('referer')), -1);
            $referer = $referer[0];
            if($referer == 'create') {
                $emailForm->submit($request);

                $emailFormView = $emailForm->createView();
                return array(
                    'user' => $this->getUser(),
                    'emailForm' => $emailFormView
                );
            } else if ($referer == 'confirm') {
                $emailCreateFormHandler = $this->get('bethel.create_email');
                $emailForm = $emailCreateFormHandler->process($emailForm);
                if($emailForm->isValid()) {
                    $roles = $emailForm->get('role')->getData();
                    $cc = $emailForm->get('cc')->getData();
                    $bcc = $emailForm->get('bcc')->getData();

                    $userString = '';
                    $users = array_merge($cc->toArray(), $bcc->toArray());
                    for($i = 0; $i < count($users); $i++) {
                        /** @var \Bethel\EntityBundle\Entity\User $user */
                        $user = $users[$i];
                        if($i+1 == count($users)) {
                            $userString .= $user->__toString();
                        } else {
                            $userString .= $user->__toString() . ', ';
                        }
                    }

                    $roleString = '';
                    for($i = 0; $i < count($roles); $i++) {
                        /** @var \Bethel\EntityBundle\Entity\Role $role */
                        $role = $roles[$i];
                        if($i+1 == count($roles)) {
                            $roleString .= $role->getName();
                        } else {
                            $roleString .= $role->getName() . ', ';
                        }
                    }

                    $message = 'Your email was sent to';
                    if(strlen($userString) > 0) {
                        $message .= ' the following users: ' . $userString;
                        if(strlen($roleString) > 0) {
                            $message .= ' and';
                        }
                    }

                    if(strlen($roleString) > 0) {
                        $message .= ' the following roles: ' . $roleString;
                    }
                    $this->get('session')->getFlashBag()->add(
                        'success',
                        $message
                    );

                    return $this->redirect($this->generateUrl('email_create'));
                } else {
                    $emailFormView = $emailForm->createView();

                    return array(
                        'user' => $this->getUser(),
                        'emailForm' => $emailFormView
                    );
                }
            } else {
                throw new AccessDeniedHttpException;
            }

        } else {
            throw new AccessDeniedHttpException;
        }
    }
}
