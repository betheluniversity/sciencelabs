<?php

namespace Bethel\UserViewBundle\Controller;

use Bethel\EntityBundle\BethelEntityBundle;
use Bethel\EntityBundle\Entity\User;
use Bethel\EntityBundle\Form\UserCreateType;
use Bethel\EntityBundle\Form\UserSearchType;
use Bethel\EntityBundle\Form\UserType;
use Bethel\FrontBundle\Controller\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Bethel\EntityBundle\Entity\UserRepository;

/**
 * @Route("/user")
 */
class DefaultController extends BaseController
{
    /**
     * @Route("/", name="user")
     * @Template("BethelUserViewBundle:Default:index.html.twig")
     */
    public function indexAction() {
        // TODO: only display active users
        $em = $this->getEntityManager();
        $roleRepository = $em->getRepository('BethelEntityBundle:Role');
        /** @var \Bethel\EntityBundle\Entity\UserRepository $userRepository */
        $userRepository = $em->getRepository('BethelEntityBundle:User');
        $roles = $roleRepository->findAll();

        $sortedRoleUsers = array();
        foreach($roles as $role) {
            $roleSort = $role->getSort();
            $roleName = $role->getName();
            if($roleSort) {
                $sortedRoleUsers[$role->getSort()] = array(
                    'role' => $roleName,
                    'users' => $userRepository->getUsersByRole($roleName)
                );
            }
        }

        return array(
            'user' => $this->getUser(),
            'sortedRoleUsers' => $sortedRoleUsers
        );
    }

    /**
     * @Route("/edit/{id}", name="user_edit", defaults={"id" = null})
     * @ParamConverter("editUser", class="BethelEntityBundle:User")
     * @Template("BethelUserViewBundle:Default:edit.html.twig")
     * @param Request $request
     * @param User $editUser
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction(Request $request, User $editUser = null) {
        $user = $this->getUser();
        if($editUser && $user->getId() !== $editUser->getId()) {
            throw new AccessDeniedException('You may not edit someone else\'s profile.');
        }


        if(!$editUser) {
            $editUser = $user;
        }

        $form = $this->createForm(new UserType(), $editUser, array(
            'action' => $this->generateUrl('user_edit', array(
                'id' => $editUser->getId()
            ))
        ));

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getEntityManager();
            $em->persist($editUser);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                'Your form was saved successfully.'
            );
        } else if($form->isSubmitted() && !$form->isValid()) {
            $this->get('session')->getFlashBag()->add(
                'warning',
                'There was a problem with your changes!'
            );

            return $this->redirect($this->generateUrl('user_edit', array(
                'id' => $editUser->getId()
            )));
        }

        return array(
            'user' => $this->getUser(),
            'form' => $form
        );
    }

    /**
     * @Route("/search/{fname}/{lname}", name="user_search", defaults={"fname" = null, "lname" = null})
     * @Template("BethelUserViewBundle:Default:search.html.twig")
     * @param Request $request
     * @param null $fname
     * @param null $lname
     * @return array
     */
    public function searchAction(Request $request, $fname = null, $lname = null) {

        $form = $this->createForm(new UserSearchType());

        /** @var $userSearchFormHandler \Bethel\EntityBundle\Form\Handler\UserSearchFormHandler */
        $userSearchFormHandler = $this->get('user_search_form_handler');

        $searchResults = null;

        if($request->getMethod() == 'POST') {
            $searchResults = $userSearchFormHandler->process($form);
        }

        return array(
            'user' => $this->getUser(),
            'form' => $form,
            'searchResults' => $searchResults
        );
    }

    /**
     * @Route("/create/{username}/{fname}/{lname}", name="user_create", defaults={"fname" = null, "lname" = null})
     * @Template("BethelUserViewBundle:Default:create.html.twig")
     * @param Request $request
     * @param string $username
     * @param string|null $fname
     * @param string|null $lname
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createAction(Request $request, $username, $fname = null, $lname = null) {
        $em = $this->getEntityManager();
        /** @var $userRepository \Bethel\EntityBundle\Entity\UserRepository */
        $userRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:User');

        $em->getFilters()->disable('softdeleteable');
        $existingUser = $userRepository->findOneBy(array('username' => $username));
        $em->getFilters()->enable('softdeleteable');

        if($existingUser) {
            $existingUser->setDeletedAt(null);
            $em->persist($existingUser);
            $em->flush();
            return array(
                'user' => $this->getUser(),
                'existingUser' => $existingUser
            );
        } else {
            $form = $this->createForm(new UserCreateType());

            $form->get('username')->setData($username);

            /** @var $userCreateFormHandler \Bethel\EntityBundle\Form\Handler\UserCreateFormHandler */
            $userCreateFormHandler = $this->get('user_create_form_handler');

            if($request->getMethod() == 'POST') {
                $createdUser = $userCreateFormHandler->process($form);

                if($createdUser instanceof User) {
                    $successMsg = $createdUser->__toString() . ' was created successfully with the following role(s): ';
                    $userRoles = $createdUser->getRoles();
                    $numRoles = count($userRoles);
                    $i = 0;
                    /** @var \Bethel\EntityBundle\Entity\Role $role */
                    foreach($userRoles as $role) {
                        $successMsg .= $role->getName();
                        if(++$i !== $numRoles) {
                            $successMsg .= ', ';
                        }
                    }
                    $this->get('session')->getFlashBag()->add(
                        'success',
                        $successMsg
                    );
                    return $this->redirect($this->generateUrl('user_search'));
                } else {
                    $this->get('session')->getFlashBag()->add(
                        'warning',
                        'There was a problem creating this user'
                    );
                }
            }

            return(array(
                'user' => $this->getUser(),
                'fname' => $fname,
                'lname' => $lname,
                'form' => $form
            ));
        }
    }
}
