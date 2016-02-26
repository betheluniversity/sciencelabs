<?php

namespace Bethel\CourseViewBundle\Controller;

use Bethel\EntityBundle\Entity\Course;
use Bethel\EntityBundle\Form\CourseCodeType;
use Bethel\FrontBundle\Controller\BaseController;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/course")
 */
class DefaultController extends BaseController
{

    /**
     * @Route("/", name="course")
     * @Template()
     */
    public function indexAction() {
        return $this->redirect($this->generateUrl('course_admin'));
    }


    /**
     * @Route("/admin", name="course_admin")
     * @Template("BethelCourseViewBundle:Default:admin.html.twig")
     */
    public function adminAction() {
        $em = $this->getEntityManager();

        // A comma separated list of all active course codes in the database
        // we'll use this to populate the "tag list" of courses in the course
        // code form
        $courses = implode(',',$em->getRepository('BethelEntityBundle:CourseCode')->findBy(array('active'=>true)));

        $courseForm = $this->createForm(new CourseCodeType($this->container->getParameter('app.title')), null, array(
            'action' => $this->generateUrl('coursecode_submit')
        ));

        $courseForm->get('coursecode')->setData($courses);

        $courseForm = $courseForm->createView();

        $activeSemester = $this->getActiveSemester();
        $courseRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:Course');
        $activeCourses = $courseRepository->getSemesterCourses($activeSemester);

        return array(
            'user' => $this->getUser(),
            'courseForm' => $courseForm,
            'activeCourses' => $activeCourses,
            'activeSemester' => $activeSemester
        );
    }

    /**
     * @Route("/admin/submit", name="coursecode_submit")
     * @Template("BethelFrontBundle:Default:debug.html.twig")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function adminCoursesAction(Request $request) {

        // We need to create a new instance of the CourseCode form to bind
        // the request form object to.
        $form = $this->createForm(new CourseCodeType($this->container->getParameter('app.title')));

        $form = $form->submit($request->get('bethel_entitybundle_coursecode'));

        /** @var $courseCodeFormHandler \Bethel\EntityBundle\Form\Handler\CourseCodeFormHandler */
        $courseCodeFormHandler = $this->get('course_code_form_handler');

        $courseCodeFormHandler->process($form);

        return $this->redirect($this->generateUrl('course_admin'));
    }

    /**
     * @Route("/{id}", name="course_single")
     * @ParamConverter("course", class="BethelEntityBundle:Course")
     * @Template("BethelCourseViewBundle:Default:course.html.twig")
     * @param Course $course
     * @return array
     */
    public function singleAction(Course $course) {
        return array(
            'user' => $this->getUser(),
            'course' => $course,
            'courseClass' => get_class($course)
        );
    }

    /**
     * @Route("/admin/addcoursecodes", name="coursecode_add")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */

    // XXX: TEMP solution to associate courses with coursecodes
    public function adminCoursesAddCourseCodes() {
        $em = $this->getEntityManager();
        $courseRepository = $em->getRepository('BethelEntityBundle:Course');
        $courseCodeRepository = $em->getRepository('BethelEntityBundle:CourseCode');
        $courses = $courseRepository->findBy(array('courseCode' => null));

        $modifiedCourses = array();
        foreach($courses as $course) {
            $courseCode = $courseCodeRepository->findOneBy(array(
                'dept' => $course->getDept(),
                'courseNum' => $course->getCourseNum()
            ));
            if($courseCode) {
                $course->setCourseCode($courseCode);
                $em->persist($course);
                $em->flush();
                $modifiedCourses[] = $course;
            }
        }

        $resp = '';
        foreach($modifiedCourses as $modifiedCourse) {
            $resp .= $modifiedCourse->__toString();
            $resp .= ', ';
        }

        return new Response('Course codes added to: ' . $resp, 200);
    }
}
