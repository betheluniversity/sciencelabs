<?php

namespace Bethel\EntityBundle\Form\Handler;

use Bethel\CourseViewBundle\Services\PopulateCourseCodesService;
use Bethel\EntityBundle\Entity\CourseCode;
use Bethel\EntityBundle\Exception\SemesterNotFoundException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class CourseCodeFormHandler {
    protected $em;
    protected $requestStack;
    protected $session;
    protected $populateCourseCodesService;

    public function __construct(EntityManager $em, RequestStack $requestStack, Session $session, PopulateCourseCodesService $populateCourseCodesService) {
        $this->em = $em;
        $this->request = $requestStack->getCurrentRequest();
        $this->session = $session;
        $this->populateCourseCodesService = $populateCourseCodesService;
    }

    public function process(Form $form) {
        if('POST' !== $this->request->getMethod()) {
            return false;
        }

        if($form->isValid()) {
            return $this->processValidForm($form);
        }

        $this->session->getFlashBag()->add(
            'warning',
            'Your form was not valid.'
        );
        return false;
    }

    /**
     * Processes the valid form
     *
     * @param Form $form
     * @return ArrayCollection
     */
    public function processValidForm(Form $form) {
        // Bethel University Course Codes:
        // Subject Level Number Suffix
        //     HIS     3 07     G
        // Level, number and suffix are concatenated
        // and appear as "course_num" in the Banner API

        $activeCourseCodes = $this->em->getRepository('BethelEntityBundle:CourseCode')->findBy(array('active'=>true));
        $courseCodes = explode(',',$form->get('coursecode')->getData());
        $deactivatedCourseCodes = array_diff($activeCourseCodes, $courseCodes);
        $newCourseCodeCollection = new ArrayCollection();
        $courseCodeCollection = new ArrayCollection();

        foreach($deactivatedCourseCodes as $deactivatedCourseCode) {
            /** @var $deactivatedCourseCode \Bethel\EntityBundle\Entity\CourseCode */
            $deactivatedCourseCode->setActive(false);
            $this->em->persist($deactivatedCourseCode);
            $this->em->flush();
        }

        foreach($courseCodes as $courseCode) {
            $ccArray = str_split($courseCode);

            for($i = 0; $i < count($ccArray); $i++) {
                // we step through the course code until we encounter the first number
                // then we break out, assigning everything up to that point as the
                // department code, and everything after as the course number
                // we also need to throw away anything after a space
                if(is_numeric($ccArray[$i])) {
                    $dept = trim(substr($courseCode,0,$i));
                    $courseNum = trim(substr($courseCode, $i));
                    $courseNum = explode(' ', $courseNum);
                    $courseNum = $courseNum[0];
                    break;
                }
            }

            if(isset($courseNum) && isset($dept)) {
                /** @var $cc \Bethel\EntityBundle\Entity\CourseCode */
                $cc = $this->em->getRepository('BethelEntityBundle:CourseCode')
                    ->findOneBy(array('courseNum' => $courseNum, 'dept' => $dept));

                if(!$cc || !$cc->getActive()) {
                    if(!$cc) {
                        // Create a new course code if we can't find it in the database
                        $cc = new CourseCode();
                        $cc->setCourseNum($courseNum);
                        $cc->setDept($dept);
                        $cc->setUnderived($courseCode);

                        $this->em->persist($cc);
                        $this->em->flush();
                    } elseif(!$cc->getActive()) {
                        // If the course code is already there and just inactive, all we
                        // need to do is activate it.
                        $cc->setActive(true);

                        $this->em->persist($cc);
                        $this->em->flush();
                    }
                    $newCourseCodeCollection->add($cc);
                }
                $courseCodeCollection->add($cc);

                $courseNum = null;
                $dept = null;
            } else {
                // We just did some rudimentary checking to see if we hit a number somewhere in the string
                // If there isn't a number in there at all, we reject the string as an invalid course code
                $this->session->getFlashBag()->add(
                    'warning',
                    $courseCode . ' is not a valid course code.'
                );
            }
        }

        try {
            $createdCourses = $this->populateCourseCodesService->populate($courseCodeCollection);
        } catch (SemesterNotFoundException $e) {
            $this->session->getFlashBag()->add(
                'warning',
                $e->getMessage()
            );
            return $newCourseCodeCollection;
        }

        if($createdCourses) {
            $this->session->getFlashBag()->add(
                'success',
                'Course Codes validated, see below for added courses'
            );
        } else {
            $this->session->getFlashBag()->add(
                'success',
                'Course Codes validated, no courses were added to the system'
            );
        }

        return $newCourseCodeCollection;
    }
}