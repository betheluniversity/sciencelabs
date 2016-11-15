<?php

namespace Bethel\FrontBundle\Controller;

use Bethel\EntityBundle\Entity\CourseCode;
use Bethel\EntityBundle\Entity\Semester;
use Bethel\EntityBundle\Entity\User;
use Bethel\EntityBundle\Form\CourseCodeType;
use Bethel\EntityBundle\Form\ScheduleType;
use Bethel\EntityBundle\Form\SemesterType;
use Bethel\EntityBundle\Form\UserAdminType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\ORMInvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\SecurityContext;




use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class DefaultController extends BaseController
{
    /**
     * @Route("/", name="homepage")
     * @Template("BethelFrontBundle:Default:index.html.twig")
     */
    public function indexAction() {
        if( $this->container->getParameter('env') && $this->container->getParameter('env') == 'test' ){
            $em = $this->getEntityManager();
            $userRepository = $em->getRepository('BethelEntityBundle:User');
            $username = $this->container->getParameter('test.username');
            $user = $userRepository->findOneBy(array('username' => $username));

            $token = new UsernamePasswordToken($user, null, "cas_firewall", array($this->container->getParameter('test.role')) );
            $this->get("security.context")->setToken($token); //now the user is logged in

            //now dispatch the login event
            $request = $this->get("request");
            $event = new InteractiveLoginEvent($request, $token);
            $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
        } else {
            $user = $this->getUser();
        }

        return array(
            'user' => $user,
        );
    }


    /**
     * @Route("/admin/user/{id}", name="admin_user_edit")
     * @ParamConverter("editUser", class="BethelEntityBundle:User")
     * @Template("BethelFrontBundle:Default:user_admin.html.twig")
     * @param Request $request
     * @param User $editUser
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Request $request, User $editUser) {
        $user = $this->getUser();

        if (false === $this->get('security.context')->isGranted('ROLE_ADMIN')) {
            // TODO: allow the user to edit roles based on his or her own role
            throw new AccessDeniedHttpException('You are not an administrator and may not edit other users.');
        }

        if( $user != $editUser)
            $showRoles = true;
        else
            $showRoles = false;

        $form = $this->createForm(new UserAdminType(), $editUser, array(
            'action' => $this->generateUrl('admin_user_edit', array(
                    'id' => $editUser->getId()
                )),
            'show_roles' => $showRoles
        ));

        // get data
        $em = $this->getEntityManager();
        $userRepository = $em->getRepository('BethelEntityBundle:User');
        $courseData = $userRepository->getCourseViewerCourses($editUser);

        // get courses they teach
        $profCourses = $editUser->getProfessorCourses();

        $form->get('courses')->setData($courseData);

        if($request->getMethod() == 'POST') {
            /** @var $formHandler \Bethel\EntityBundle\Form\Handler\UserAdminFormHandler */
            $formHandler = $this->get('user_admin_form_handler');
            $submissionResult = $formHandler->process($form);

            if ($submissionResult['success']) {
                $em = $this->getEntityManager();
                $em->persist($editUser);
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $editUser->__toString() . ' was successfully edited'
                );
            } else {
                $this->get('session')->getFlashBag()->add(
                    'warning',
                    'There was a problem with your changes'
                );
            }

            return $this->redirect($this->generateUrl('user'));
        }

        return array(
            'user' => $this->getUser(),
            'editUser' => $editUser,
            'form' => $form,
            'profCourses' => $profCourses
        );
    }

    /**
     * @Route("/admin/user/deactivate/{id}", name="admin_user_deactivate")
     * @ParamConverter("user", class="BethelEntityBundle:User")
     * @param User $deactivateUser
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Template("BethelFrontBundle:Default:user_deactivate.html.twig")
     */
    public function deactivateAction(User $deactivateUser, Request $request) {

        $deactivateForm = $this->createFormBuilder()
            ->add('deactivateUser', 'hidden', array(
                'data' => $deactivateUser
            ))
            ->add('save','submit', array(
                'attr' => array('class'=>'button alert large radius'),
                'label' => 'Deactivate'
            ))
            ->getForm()
            ->createView();


        if($request->getMethod() == 'POST') {
            $this->getEntityManager()->remove($deactivateUser);
            $userDisplayName = $deactivateUser->__toString();
            $this->getEntityManager()->flush();
            $this->get('session')->getFlashBag()->add(
                'success',
                $userDisplayName . ' has been deactivated'
            );

            return $this->redirect($this->generateUrl('user'));
        }

        return array(
            'user' => $this->getUser(),
            'deactivateForm' => $deactivateForm,
            'deactivateUser' => $deactivateUser
        );
    }

    /**
     * This will step the user through the process of transitioning from one
     * semester to another. This includes populating schedules, or modifying
     * those from the previous year, selecting tutors for each of those
     * schedules, and selecting a start and end date for each schedule.
     *
     * We will also do some transparent cleanup work. We'll wipe out tutors
     * and lead tutors from the schedules before we use them, and then wipe
     * them out again when we're done. We'll also de-escalate the privileges
     * of all lead tutors to the tutor level. Lead tutor role will be assigned
     * to all users selected as lead tutors during session creation.
     *
     * @Route("/admin/transition/1", name="admin_transition")
     * @Template("BethelFrontBundle:Default:transition.html.twig")
     */
    public function transitionAction() {

        // Step 1: Update the current semester
        $currentSemester = $this->getActiveSemester();

        $semesterForm = $this->createForm(new SemesterType($this->get('term_validator')), null, array(
            'action' => $this->generateUrl('admin_transition')
        ));

        /** @var $semesterFormHandler \Bethel\EntityBundle\Form\Handler\SemesterFormHandler */
        $semesterFormHandler = $this->get('semester_form_handler');

        $semesterFormHandler->process($semesterForm);

        return array(
            'user' => $this->getUser(),
            'semesterForm' => $semesterForm->createView(),
            'currentSemester' => $currentSemester
        );
    }

    /**
     * @Route("/admin/transition/2", name="admin_transition_user_docs")
     * @Template("BethelFrontBundle:Default:transition_docs.html.twig")
     * @return array
     */
    public function transitionUserDocs() {
        // Docs are located in app/config/twig.yml
        $docTitle = "Step 2: Edit Users";
        return array(
            'docTitle' => $docTitle,
            'user' => $this->getUser()
        );
    }

    /**
     * @Route("/admin/transition/3", name="admin_transition_course_docs")
     * @Template("BethelFrontBundle:Default:transition_docs.html.twig")
     * @return array
     */
    public function transitionCourseDocs() {
        // Docs are located in app/config/twig.yml
        $docTitle = "Step 3: Review Courses";
        return array(
            'docTitle' => $docTitle,
            'user' => $this->getUser()
        );
    }
    /**
     * @Route("/admin/transition/4", name="admin_transition_schedule_docs")
     * @Template("BethelFrontBundle:Default:transition_docs.html.twig")
     * @return array|RedirectResponse
     */
    public function transitionScheduleDocs() {
        // Docs are located in app/config/twig.yml
        $docTitle = "Step 4: Review Schedules";
        return array(
            'docTitle' => $docTitle,
            'user' => $this->getUser()
        );
    }

    /**
    * @Route("/debug")
    * @Template("BethelFrontBundle:Default:debug.html.twig")
    */
    public function debugAction() {

        $request = $this->getRequest();
        $session = $request->getSession();

        if($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        var_dump($error);
//        return array(
//            'error' => $error
//        );
    }

    /**
     * @Route("/login_failure")
     * @Template("BethelFrontBundle:Default:login_failure.html.twig")
     */
    public function loginFailureAction() {
        // If our login failed it's probably because there was no session cookie
        // Redirect to the front page after it has been created
        $logoutUrl  = $this->container->getParameter('cas.login');
        $redirectUrl = $logoutUrl . '?service=' .
            urlencode($this->generateUrl('homepage', array(), true));
        $response = new RedirectResponse($redirectUrl);
        return $response;
    }

    /**
     * @Route("/delete/course/{id}", name="course_delete")
     * @Route("/delete/session/{id}", name="session_delete")
     * @Template("BethelFrontBundle:Default:entity_delete.html.twig")
     * @param $id
     * @param Request $request
     * @param $_route
     * @return array|RedirectResponse
     */
    public function deleteEntityAction($id, Request $request, $_route) {
        $em = $this->getEntityManager();

        // This action handles deletion for multiple different entities
        // We make the decision about which entity we're deleting based on the route
        switch($_route) {
            case 'course_delete':
                $repository = $em->getRepository('BethelEntityBundle:Course');
                $redirectUrl = $this->generateUrl('course');
                break;
            case 'session_delete':
                $repository = $em->getRepository('BethelEntityBundle:Session');
                $redirectUrl = $this->generateUrl('session_closed');
                break;
            default:
                throw new HttpException(404, "Route not valid.");
        }

        $entity = $repository->findOneBy(array('id' => $id));
        $deleteForm = $this->createFormBuilder()
            ->add('deleteEntity', 'hidden', array(
                'data' => $entity
            ))
            ->add('save','submit', array(
                'attr' => array('class'=>'button alert large radius'),
                'label' => 'Delete'
            ))
            ->getForm();
        $deleteFormView = $deleteForm->createView();


        if($request->getMethod() == 'POST') {
            $deleteForm->submit($request);
            $em->getFilters()->enable('softdeleteable');
            $em->remove($entity);
            $entityName = $entity->__toString();
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',
                $entityName . ' was successfully deleted'
            );

            return $this->redirect($redirectUrl);
        }

        return array(
            'user' => $this->getUser(),
            'deleteForm' => $deleteFormView,
            'entity' => $entity
        );
    }
}
