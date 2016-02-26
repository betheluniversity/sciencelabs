<?php

namespace Bethel\TutorBundle\Controller;

use Bethel\EntityBundle\Form\OwnedTutorSessionType;
use Bethel\EntityBundle\Form\SubstituteTutorSessionType;
use Bethel\EntityBundle\Form\TutorSessionType;
use Bethel\FrontBundle\Controller\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;


/**
 * @Route("/tutor")
 */
class DefaultController extends BaseController {

    /**
     * @Route("s/schedule", name="tutor_schedule")
     * @Template("BethelTutorBundle:Default:schedule.html.twig")
     */
    public function scheduleAction() {

        return array(
            'user' => $this->getUser()
        );
    }

    /**
     * @Route("/session/{id}", name="tutor_session")
     * @ParamConverter("TutorSession", class="BethelEntityBundle:TutorSession")
     * @Template("BethelTutorBundle:Default:session.html.twig")
     */
    public function sessionAction($id) {

        $user = $this->getUser();

        $tutorSessionRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:TutorSession');

        /** @var $tutorSession \Bethel\EntityBundle\Entity\TutorSession */
        $tutorSession = $tutorSessionRepository->findOneBy(array('id' => $id));

        /** @var $tutorSessionFormHandler \Bethel\EntityBundle\Form\Handler\TutorSessionFormHandler */
        $tutorSessionFormHandler = $this->get('tutor_session_form_handler');

        if ($user->hasRole('ROLE_ADMIN')) {
            $form = $this->createForm(new TutorSessionType(), $tutorSession, array(
                'action' => $this->generateUrl('tutor_session', array(
                        'id' => $tutorSession->getId()
                    ))
            ));
        } else if($tutorSession->getTutor() == $this->getUser()) {
            // Tutors should not be able to remove their own sessions
            // instead, we give them the option to allow substitutes
            $form = $this->createForm(new OwnedTutorSessionType(), $tutorSession, array(
                'action' => $this->generateUrl('tutor_session', array(
                    'id' => $tutorSession->getId()
                ))
            ));
        } else if($tutorSession->getSubstitutable() && ($user->hasRole('ROLE_TUTOR') || $user->hasRole('ROLE_LEAD_TUTOR'))) {
            $form = $this->createForm(new SubstituteTutorSessionType(), $tutorSession, array(
                'user' => $user,
                'action' => $this->generateUrl('tutor_session', array(
                    'id' => $tutorSession->getId()
                ))
            ));
        } else {
            if(!$tutorSession->getSubstitutable()) {
                $this->get('session')->getFlashBag()->add(
                    'warning',
                    'You may not edit this session. The owner of the session has not allowed substitutes.'
                );
            } else {
                $this->get('session')->getFlashBag()->add(
                    'warning',
                    'You may not edit this session. If you believe this is an error, contact an administrator.'
                );
            }

            return $this->redirect($this->generateUrl('tutor_schedule'));
        }

        $return = $tutorSessionFormHandler->process($form, $this->getUser());


        if($return === true) {
            $this->get('session')->getFlashBag()->add(
                'warning',
                'There was a problem with your changes!'
            );

            return $this->redirect($this->generateUrl('session_edit'));
        } else if($return) {
            $this->get('session')->getFlashBag()->add(
                'success',
                'The session was saved successfully.'
            );
        }

        return array(
            'tutorSession' => $tutorSession,
            'user' => $user,
            'form' => $form
        );
    }

    /**
     * @Route("/schedule/delete/{id}", name="tutor_session_delete")
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function tutorSessionDeleteAction($id) {
        $em = $this->getEntityManager();

        $tutorSessionRepository = $em->getRepository("BethelEntityBundle:TutorSession");
        $tutorSession = $tutorSessionRepository->find($id);

        if ($tutorSession) {
            $em->remove($tutorSession);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',
                'Tutor session was deleted!'
            );
        } else {
            $this->get('session')->getFlashBag()->add(
                'warning',
                'Tutor session does not exist.'
            );
        }

        return $this->redirect($this->generateUrl('tutor_schedule'));
    }
}