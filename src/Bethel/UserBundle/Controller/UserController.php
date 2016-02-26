<?php

namespace Bethel\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Bethel\EntityBundle\Entity\User;
use Bethel\EntityBundle\Form\UserType;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    // Manual testing examples:
    // curl -i -H "Accept: application/json" -X DELETE http://scilabs.dev/app_dev.php/users/1
    // curl -v -H "Accept: application/json" -X GET http://scilabs.dev/app_dev.php/users/1
    // curl -v -H "Accept: application/json" -H "Content-type: application/json" -X POST -d '{"firstName":"John", "lastName": "Smith", "username": "jsmith", "email": "foo@example.org", "accessLevel": "Student", "active": "1" }' http://scilabs.dev/app_dev.php/users
    // curl -v -H "Accept: application/json" -H "Content-type: application/json" -X PUT -d '{"firstName":"Joe"}' http://scilabs.dev/app_dev.php/users/1

    /**
     * @Rest\View
     */
    public function allAction()
    {
        $userRepo = $this->getDoctrine()->getRepository('BethelEntityBundle:User');
        $users = $userRepo->findAll();

        return $users;
    }

    /**
     * @Rest\View(serializerGroups={"userInfo"})
     */
    public function getAction($id)
    {
        $userRepo = $this->getDoctrine()->getRepository('BethelEntityBundle:User');
        $user = $userRepo->findOneById($id);

        if (!$user instanceof User) {
            throw new NotFoundHttpException('User not found');
        }

        return $user;
    }
//
//    public function newAction(Request $request) {
//        return $this->processUserForm($request);
//    }
//
//    public function editAction(Request $request, User $user) {
//        return $this->processUserForm($request, $user);
//    }
//
    /**
     * @Rest\View(statusCode=204)
     * @param User $user
     */
    public function removeAction(User $user)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($user);
        $em->flush();
    }
//
//    private function processUserForm($request, User $user = null) {
//
//        // We need to determine our response based on whether we're creating the entity or editing it
//        if(!$user) {
//            $user = new User();
//            $statusCode = 201;
//        } else {
//            $statusCode = 204;
//        }
//
//        $em = $this->getDoctrine()->getManager();
//
//        $form = $this->createForm(new UserType(), $user, array('method' => $request->getMethod()));
//        // http://symfony.com/blog/new-in-symfony-2-4-the-request-stack
//        // $logger = $this->get('logger');
//        // $logger->error();
//        // \Doctrine\Common\Util\Debug::dump($request->getMethod());
//
//        // Pass a FALSE to clearMissing (i.e. set field to NULL when they're
//        // missing in the submitted data) only if the request type is a PUT
//        $form->submit($request->request->all(), $request->getMethod() != "PUT" ? true : false);
//
//        if ($form->isValid()) {
//            $user = $form->getData();
//            $em->persist($user);
//            $em->flush();
//            $response = new Response();
//            $response->setStatusCode($statusCode);
//
//            // set the `Location` header only when creating new resources
//            if (201 === $statusCode) {
//                $response->headers->set('Location',
//                    $this->generateUrl(
//                        'bethel_user_get', array('id' => $user->getId()),
//                        true // absolute
//                    )
//                );
//            }
//
//            return $response;
//        }
//        return View::create($form, 400);
//    }
}
