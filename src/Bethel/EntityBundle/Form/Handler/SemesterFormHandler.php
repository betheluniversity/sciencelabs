<?php

namespace Bethel\EntityBundle\Form\Handler;

use Bethel\CourseViewBundle\Services\PopulateCourseCodesService;
use Bethel\EntityBundle\Entity\Semester;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\ValidatorInterface;

class SemesterFormHandler {
    protected $em;
    protected $requestStack;
    protected $session;
    protected $validator;
    protected $populateCourseCodesService;

    public function __construct(
        EntityManager $em,
        RequestStack $requestStack,
        Session $session,
        ValidatorInterface $validator,
        PopulateCourseCodesService $populateCourseCodesService
    ) {
        $this->em = $em;
        $this->request = $requestStack->getCurrentRequest();
        $this->session = $session;
        $this->validator = $validator;
        $this->populateCourseCodesService = $populateCourseCodesService;
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
     * @return \Bethel\EntityBundle\Entity\Semester|null
     */
    public function processValidForm(Form $form) {
        $semesterRepository = $this->em->getRepository('BethelEntityBundle:Semester');
        $userRepository = $this->em->getRepository('BethelEntityBundle:User');
        $roleRepository = $this->em->getRepository('BethelEntityBundle:Role');

        // Need to handle the form submission. If there is already a Semester entity with
        // the year and term selected, we need to switch that to active rather than

        if(!is_null($form)) {
            /** @var $formSemester \Bethel\EntityBundle\Entity\Semester */
            $formSemester = $form->getData();
            /** @var $semester \Bethel\EntityBundle\Entity\Semester */
            // Try to find a semester with the same term and year as the one submitted
            $semester = $semesterRepository->findOneBy(
                array(
                    'term' => $formSemester->getTerm(),
                    'year' => $formSemester->getYear()
                )
            );

            if($formSemester->getStartDate() == null || $formSemester->getEndDate() == null) {
                $this->session->getFlashBag()->add(
                    'warning',
                    'This semester does not have a date range. Please select a start and end date.'
                );

                return false;
            }

            // Deactivate all semesters in preparation for activating a new semester
            $activeSemesters = $semesterRepository->findBy(array('active' => true));
            foreach($activeSemesters as $activeSemester) {
                $activeSemester->setActive(false);
                $this->em->persist($activeSemester);
            }

            if($semester) {
                // If we found an existing Semester that matches what was submitted, we
                // set that to active, update the dates and persist it ...
                $semester->setActive(true);
                $semester->setStartDate($formSemester->getStartDate());
                $semester->setEndDate($formSemester->getEndDate());
                $this->em->persist($semester);
                $this->em->flush();

                $this->session->getFlashBag()->add(
                    'success',
                    $semester . ' has been set to active.'
                );

                $semesterChanged = true;
            } else {
                // ... otherwise we create a new Semester with the information submitted.
                $semester = $formSemester;
                $semester->setActive(true);

                $errors = $this->validator->validate($semester);

                if(count($errors) == 0) {
                    $this->em->persist($semester);
                    $this->em->flush();

                    $this->session->getFlashBag()->add(
                        'info',
                        $semester . ' has been created.'
                    );

                    $semesterChanged = true;
                } else {
                    foreach($errors as $error) {
                        $this->session->getFlashBag()->add(
                            'warning',
                            $error->getMessage()
                        );
                    }

                    $semesterChanged = false;
                }
            }

            if($semesterChanged) {
                // We deactivate all users in the system who only have the student role
                $studentRole = $roleRepository->findOneBy(array('name' => 'Student'));
                $students = $userRepository->getUsersWithSingleRole($studentRole);
                /** @var \Bethel\EntityBundle\Entity\User $student */
                foreach($students as $student) {
                    $this->em->remove($student);
                }
                $this->em->flush();

                // We also want to ask Banner what the courses are for the semester, and
                // store them in the database
                $courseCodeRepository = $this->em->getRepository('BethelEntityBundle:CourseCode');
                $activeCourseCodes = $courseCodeRepository->getActiveCourseCodes();

                $createdCourses = $this->populateCourseCodesService->populate(new ArrayCollection($activeCourseCodes));
            }
        }

        return isset($semester) ? $semester : null;
    }

} 