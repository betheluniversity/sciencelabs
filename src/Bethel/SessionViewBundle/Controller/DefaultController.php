<?php

namespace Bethel\SessionViewBundle\Controller;

use Bethel\EntityBundle\Entity\Course;
use Bethel\EntityBundle\Entity\Semester;
use Bethel\EntityBundle\Entity\Session;
use Bethel\EntityBundle\Entity\StudentSession;
use Bethel\EntityBundle\Entity\TutorSession;
use Bethel\EntityBundle\Entity\User;
use Bethel\EntityBundle\Form\AnonAttendanceType;
use Bethel\EntityBundle\Form\SessionCommentType;
use Bethel\EntityBundle\Form\SessionType;
use Bethel\EntityBundle\Form\StudentAddAttendanceType;
use Bethel\EntityBundle\Form\StudentAttendanceType;
use Bethel\EntityBundle\Form\StudentSessionType;
use Bethel\EntityBundle\Form\StudentSigninType;
use Bethel\EntityBundle\Form\TutorSessionAdminType;
use Bethel\FrontBundle\Controller\BaseController;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Route("/session")
 */
class DefaultController extends BaseController
{
    /**
     * @Route("/", name="session")
     * @Template("BethelSessionViewBundle:Default:index.html.twig")
     */
    public function indexAction() {
        // TODO: only display sessions scheduled for the future
        $em = $this->getEntityManager();

        /** @var $sessionRepository \Bethel\EntityBundle\Entity\SessionRepository */
        $sessionRepository = $em->getRepository('BethelEntityBundle:Session');
        $openSessions = $sessionRepository->findBy(
            array(
                'open' => true
            )
        );

        $activeSemester = $this->getActiveSemester();
        $scheduledSessions = $sessionRepository->getScheduledSessionsSortedByDate($activeSemester);
        
        $em->getFilters()->disable('softdeleteable');

        $returnValue = $this->render('BethelSessionViewBundle:Default:index.html.twig', array(
            'user' => $this->getUser(),
            'openSessions' => $openSessions,
            'scheduledSessions' => $scheduledSessions,
            'activeSemester' => $activeSemester
        ));
        $em->getFilters()->enable('softdeleteable');
        
        return $returnValue;
    }

    /**
     * @Route("/closed", name="session_closed")
     * @Template("BethelSessionViewBundle:Default:closed.html.twig")
     */
    public function closedAction() {
        $em = $this->getEntityManager();

        /** @var $sessionRepository \Bethel\EntityBundle\Entity\SessionRepository */
        $sessionRepository = $em->getRepository('BethelEntityBundle:Session');
        $activeSemester = $this->getActiveSemester();
        $semester = $this->getSessionSemester();
        $closedSessions = $sessionRepository->getClosedSessions($semester, true);
        $sessionContainer = array();
        $em->getFilters()->disable('softdeleteable');

        /** @var \Bethel\EntityBundle\Entity\Session $closedSession */
        foreach($closedSessions as $closedSession) {
            $tutorSessions = $closedSession->getTutorSessions();
            $tutors = array();
            $leadTutors = array();
            /** @var \Bethel\EntityBundle\Entity\TutorSession $tutorSession */
            foreach($tutorSessions as $tutorSession) {
                if($tutorSession->getLead()) {
                    $leadTutors[] = $tutorSession->getTutor()->__toString();
                } else {
                    $tutors[] = $tutorSession->getTutor()->__toString();
                }
            }
            $sessionContainer[] = array(
                'tutors' => $tutors,
                'leadTutors' => $leadTutors,
                'session' => $closedSession
            );
        }
        $em->getFilters()->enable('softdeleteable');
        return array(
            'user' => $this->getUser(),
            'sessionContainer' => $sessionContainer,
            'closedSessions' => $closedSessions,
            'activeSemester' => $activeSemester,
            'selectedSemester'  => $semester

        );
    }

    /**
     * @Route("/edit/{id}", name="session_edit", defaults={"id" = null})
     * @Route("/create", name="session_create", defaults={"id" = null})
     * @Template("BethelSessionViewBundle:Default:edit.html.twig")
     * @param Request $request
     * @param null $session
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAction($id, Request $request) {
        $em = $this->getEntityManager();
        $em->getFilters()->disable('softdeleteable');
        $sessionRepository = $em->getRepository('BethelEntityBundle:Session');

        if( $id) {
            /** @var $session \Bethel\EntityBundle\Entity\Session */
            $session = $sessionRepository->find(array('id' => $id));
        } else {
            $session = null;
        }
        if(!$session) {
            $actionString = 'created';
            $session = new Session();
            $message = 'Create a new session';
            $newSession = true;
        } else {
            $actionString = 'edited';
            $message = 'Edit ' . $session . ' Information';
            $newSession = false;
        }

        $studentSessions = new \ArrayObject($session->getStudentSessions());
        $iterator = $studentSessions->getIterator();
        // Sort alphabetically by last name
        $iterator->uasort(function (StudentSession $a, StudentSession $b) {
            return ($a->getStudent()->getLastName() < $b->getStudent()->getLastName()) ? -1 : 1;
        });

        $tutorSessions = new \ArrayObject($session->getTutorSessions());
        $tutorIterator = $tutorSessions->getIterator();

        $tutorIterator->uasort(function (TutorSession $a, TutorSession $b) {
            return ($a->getTutor()->getLastName() < $b->getTutor()->getLastName()) ? -1 : 1;
        });

        // TODO: Allow creation and modification of the sessions start and stop time
        $form = $this->createForm(new SessionType(), $session, array(
            'action' => $this->generateUrl('session_edit', array(
                'id' => $session->getId()
            ))
        ));

        $courseCodeRepository = $em->getRepository('BethelEntityBundle:CourseCode');
        $tutorSessionRepository = $em->getRepository('BethelEntityBundle:TutorSession');
        /** @var $userRepository \Bethel\EntityBundle\Entity\UserRepository */
        $userRepository = $em->getRepository('BethelEntityBundle:User');

        // Set the course codes for the Session based on whether or not it already has
        // course codes assigned. If not, we'll assume this session will be held for all
        // courses in the system.
        if(!count($session->getCourseCodes())) {
            $form->get('coursecodes')->setData($courseCodeRepository->findBy(array('active'=>true)));
        }

        // Manually setting the data for tutors, since we can't bind directly to the
        // session entity to get this data.
        // Binding entities to query parameters only allowed for entities that have an identifier.
        if($session->getId()) {
            $schedLeads = $userRepository->getLeadSessionTutors($session);
            if($schedLeads) {
                $form->get('leadTutors')->setData($schedLeads);
            }

            $schedTutors = $userRepository->getNonLeadSessionTutors($session);
            if($schedTutors) {
                $form->get('tutors')->setData($schedTutors);
            }
        }

        // Create the Student Attendance by Course section
        $sessionsByCourse = array();
        foreach( $session->getStudentSessions() as $studentSession){
            $courses = $studentSession->getCourses();
            foreach( $courses as $course){
                if( is_null($sessionsByCourse[strval($course->getCourseCode())]) )
                    $sessionsByCourse[strval($course->getCourseCode())] = array();
                array_push($sessionsByCourse[strval($course->getCourseCode())], $studentSession);
            }
        }

        /** @var $sessionFormHandler \Bethel\EntityBundle\Form\Handler\SessionFormHandler */
        $sessionFormHandler = $this->get('session_form_handler');

        $em->getFilters()->enable('softdeleteable');

        if($request->getMethod() == 'POST') {
            $result = $sessionFormHandler->process($form, $actionString);

            if($result['success'] == false) {
                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $result['message']
                );

                return array(
                    'user' => $this->getUser(),
                    'form' => $result['form'],
                    'studentSessions' => $studentSessions,
                    'sessionsByCourse'    => $sessionsByCourse,
                    'tutorSessions' => $tutorSessions,
                    'session' => $session,
                    'message' => $message,
                    'newSession' => $newSession,
                    'sessionSemester' => $session->getSemester(),
                );
            } else if($result['success'] == true) {
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $result['message']
                );

                return $this->redirect($this->generateUrl('session_closed'));
            }
        }

        return array(
            'user' => $this->getUser(),
            'form' => $form,
            'studentSessions' => $studentSessions,
            'sessionsByCourse'    => $sessionsByCourse,
            'tutorSessions' => $tutorSessions,
            'session' => $session,
            'message' => $message,
            'newSession' => $newSession,
            'sessionSemester' => $session->getSemester(),
        );
    }

    /**
     * @Route("/attendance/student/{id}", name="session_add_student", defaults={"id" = null})
     * @ParamConverter("session", class="BethelEntityBundle:Session")
     * @Template("BethelSessionViewBundle:Default:add_attendance_form.html.twig")
     * @param Session $session
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addStudentAction(Session $session, Request $request) {
        $em = $this->getEntityManager();
        $studentSession = new StudentSession();
        $studentSession->setSession($session);

        $form = $this->createForm(new StudentAddAttendanceType(), $studentSession, array(
            'em' => $this->getEntityManager()
        ));

        if($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {

                $em->persist($studentSession);
                $em->flush();

                return $this->redirect($this->generateUrl('session_add_attendance', array(
                    'id' => $studentSession->getId()
                )));
            } else if($form->isSubmitted() && !$form->isValid()) {
                $formErrors = $form->getErrors();

                if ($formErrors->count() > 0) {
                    foreach ($formErrors as $formError) {
                        $this->get('session')->getFlashBag()->add(
                            'warning',
                            $formError->getMessage()
                        );
                    }
                } else {
                    $this->get('session')->getFlashBag()->add(
                        'warning',
                        'You submission was invalid.'
                    );
                }

                return $this->redirect($this->generateUrl('session_edit', array(
                    'id' => $session->getId()
                )));
            }
        }

        return array(
            'user' => $this->getUser(),
            'form' => $form
        );
    }

    /**
     * @Route("/attendance/add/{id}", name="session_add_attendance", defaults={"id" = null})
     * @ParamConverter("studentSession", class="BethelEntityBundle:StudentSession")
     * @Template("BethelSessionViewBundle:Default:attendance_form.html.twig")
     * @param StudentSession $studentSession
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addAttendanceAction(StudentSession $studentSession, Request $request) {
        $em = $this->getEntityManager();

        $username = $studentSession->getStudent()->getUsername();
        $wsapi = $this->get('bethel.wsapi_controller');
        // Connected to the WSAPIController
        $apiCourses = $wsapi->getCourses($username);

        /** @var \Bethel\CourseViewBundle\Services\PopulateCoursesService $populateCoursesService */
        $populateCoursesService = $this->get('bethel.populate_courses');
        $courses = $populateCoursesService->populate($apiCourses, $studentSession->getSession());

        $form = $this->createForm(new StudentAttendanceType(), $studentSession, array(
            'em' => $this->getEntityManager(),
            'studentCourses' => $courses
        ));

        $form->handleRequest($request);

        if ($form->isValid()) {

            $em->persist($studentSession);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                'Attendance for ' . $studentSession->getStudent()->__toString(). ' created'
            );

            return $this->redirect($this->generateUrl('session_edit', array(
                'id' => $studentSession->getSession()->getId()
            )));
        } else if($form->isSubmitted() && !$form->isValid()) {
            $formErrors = $form->getErrors();

            if ($formErrors->count() > 0) {
                foreach ($formErrors as $formError) {
                    $this->get('session')->getFlashBag()->add(
                        'warning',
                        $formError->getMessage()
                    );
                }
            } else {
                $this->get('session')->getFlashBag()->add(
                    'warning',
                    'You submission was invalid.'
                );
            }

            return array(
                'user' => $this->getUser(),
                'studentSession' => $studentSession,
                'form' => $form
            );
        }

        return array(
            'user' => $this->getUser(),
            'studentSession' => $studentSession,
            'form' => $form
        );
    }

    /**
     * @Route("/attendance/edit/{id}", name="session_edit_attendance")
     * @ParamConverter("studentSession", class="BethelEntityBundle:StudentSession")
     * @Template("BethelSessionViewBundle:Default:attendance_form.html.twig")
     * @param StudentSession $studentSession
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editAttendanceAction(StudentSession $studentSession, Request $request) {
        $em = $this->getEntityManager();

        $courseRepository = $em->getRepository("BethelEntityBundle:Course");
        $courses = $courseRepository->getStudentCourses($studentSession->getStudent(), $studentSession->getSession()->getSemester());
        $courses = new ArrayCollection($courses);

        $form = $this->createForm(new StudentAttendanceType(), $studentSession, array(
            'em' => $this->getEntityManager(),
            'studentCourses' => $courses
        ));

        $form->handleRequest($request);

        if ($form->isValid()) {
            $username = $studentSession->getStudent()->getUsername();

            $courseRepository = $em->getRepository("BethelEntityBundle:Course");
            $courses = $courseRepository->getStudentCourses($studentSession->getStudent(), $this->getActiveSemester());
            $courses = new ArrayCollection($courses);
            $em->persist($studentSession);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                $studentSession->getStudent()->__toString() . ' attendance edited'
            );

            return $this->redirect($this->generateUrl('session_edit', array(
                'id' => $studentSession->getSession()->getId()
            )));
        } else if($form->isSubmitted() && !$form->isValid()) {
            $formErrors = $form->getErrors();

            if ($formErrors->count() > 0) {
                foreach ($formErrors as $formError) {
                    $this->get('session')->getFlashBag()->add(
                        'warning',
                        $formError->getMessage()
                    );
                }
            } else {
                $this->get('session')->getFlashBag()->add(
                    'warning',
                    'You submission was invalid.'
                );
            }

            return $this->redirect($this->generateUrl('session_edit', array(
                'id' => $studentSession->getSession()->getId()
            )));
        }

        return array(
            'user' => $this->getUser(),
            'form' => $form
        );
    }

    /**
     * @Route("/attendance/tutor/edit/{id}", name="session_edit_tutor_attendance")
     * @ParamConverter("tutorSession", class="BethelEntityBundle:TutorSession")
     * @Template("BethelSessionViewBundle:Default:tutor_attendance_form.html.twig")
     * @param TutorSession $tutorSession
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editTutorAttendanceAction(TutorSession $tutorSession, Request $request) {
        $em = $this->getEntityManager();

        $form = $this->createForm(new TutorSessionAdminType(), $tutorSession);
        $formTutor = $form->offsetGet('tutor');
        $form->remove('tutor');

        $form->handleRequest($request);

        if ($form->isValid()) {

            $em->persist($tutorSession);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                'Attendance for ' . $tutorSession->getTutor()->__toString() . ' edited'
            );

            return $this->redirect($this->generateUrl('session_edit', array(
                'id' => $tutorSession->getSession()->getId()
            )));
        } else if($form->isSubmitted() && !$form->isValid()) {
            $formErrors = $form->getErrors();

            if ($formErrors->count() > 0) {
                foreach ($formErrors as $formError) {
                    $this->get('session')->getFlashBag()->add(
                        'warning',
                        $formError->getMessage()
                    );
                }
            } else {
                $this->get('session')->getFlashBag()->add(
                    'warning',
                    'You submission was invalid.'
                );
            }

            return $this->redirect($this->generateUrl('session_edit', array(
                'id' => $tutorSession->getSession()->getId()
            )));
        }

        return array(
            'user' => $this->getUser(),
            'form' => $form,
            'tutor' => $tutorSession->getTutor()
        );
    }

    /**
     * @Route("/addattendance/tutor/{id}", name="session_create_tutor_attendance")
     * @ParamConverter("session", class="BethelEntityBundle:Session")
     * @Template("BethelSessionViewBundle:Default:tutor_attendance_form.html.twig")
     * @param Session $session
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addTutorAttendanceAction(Session $session, Request $request) {
        $em = $this->getEntityManager();
        $tutorSession = new TutorSession();
        $tutorSession->setSession($session);

        $form = $this->createForm(new TutorSessionAdminType(), $tutorSession);

        $form->handleRequest($request);

        if ($form->isValid()) {

            $em->persist($tutorSession);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                'Tutor attendance created.'
            );

            return $this->redirect($this->generateUrl('session_edit', array(
                'id' => $tutorSession->getSession()->getId()
            )));
        } else if($form->isSubmitted() && !$form->isValid()) {
            $formErrors = $form->getErrors();

            if ($formErrors->count() > 0) {
                foreach ($formErrors as $formError) {
                    $this->get('session')->getFlashBag()->add(
                        'warning',
                        $formError->getMessage()
                    );
                }
            } else {
                $this->get('session')->getFlashBag()->add(
                    'warning',
                    'You submission was invalid.'
                );
            }

            return $this->redirect($this->generateUrl('session_edit', array(
                'id' => $tutorSession->getSession()->getId()
            )));
        }

        return array(
            'user' => $this->getUser(),
            'form' => $form
        );
    }

    /**
     * @Route("/attendance/tutor/delete/{id}", name="delete_tutor_attendance")
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteTutorAttendanceAction($id) {
        // TODO: Authorization for destructive action
        $em = $this->getEntityManager();
        $tutorSessionRepository = $em->getRepository("BethelEntityBundle:TutorSession");
        $tutorSession = $tutorSessionRepository->find($id);

        if($tutorSession) {
            $sessionId = $tutorSession->getSession()->getId();
            $tutorName = $tutorSession->getTutor()->__toString();
            $em->remove($tutorSession);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',
                'Attendance for ' . $tutorName . ' was deleted'
            );
            return $this->redirect($this->generateUrl('session_edit', array('id' => $sessionId)));
        } else {
            $this->get('session')->getFlashBag()->add(
                'warning',
                'Tutor attendance does not exist'
            );
            return $this->redirect($this->generateUrl('session'));
        }
    }

    /**
     * @Route("/addanon/{id}", name="session_add_anon", defaults={"id" = null})
     * @ParamConverter("session", class="BethelEntityBundle:Session")
     * @Template("BethelSessionViewBundle:Default:addanon.html.twig")
     * @param Session $session
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addAnonAction(Session $session, Request $request) {
        $em = $this->getEntityManager();

        $form = $this->createForm(new AnonAttendanceType(), $session);

        $form->handleRequest($request);

        if ($form->isValid()) {

            $em->persist($session);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                'Anonymous students added.'
            );

            return $this->redirect($this->generateUrl('session_edit', array(
                'id' => $session->getId()
            )));
        } else if($form->isSubmitted() && !$form->isValid()) {
            $this->get('session')->getFlashBag()->add(
                'warning',
                'Something went wrong! Please contact an administrator.'
            );

            return $this->redirect($this->generateUrl('session_edit', array(
                'id' => $session->getId()
            )));
        }

        return array(
            'user' => $this->getUser(),
            'form' => $form
        );
    }

    /**
     * @Route("/attendance/delete/{id}", name="attendance_delete")
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function attendanceDeleteAction($id) {
        // TODO: Authorization for destructive action
        $em = $this->getEntityManager();
        $studentSessionRepository = $em->getRepository("BethelEntityBundle:StudentSession");
        $studentSession = $studentSessionRepository->find($id);

        if($studentSession) {
            $sessionId = $studentSession->getSession()->getId();
            $studentName = $studentSession->getStudent()->__toString();
            $em->remove($studentSession);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'success',
                'Attendance for ' . $studentName . ' was deleted'
            );
            return $this->redirect($this->generateUrl('session_edit', array('id' => $sessionId)));
        } else {
            $this->get('session')->getFlashBag()->add(
                'warning',
                'Student attendance does not exist.'
            );
            return $this->redirect($this->generateUrl('session'));
        }
    }

    /**
     * @Route("/attendance/{id}", name="session_attendance")
     * @ParamConverter("session", class="BethelEntityBundle:Session")
     * @Template("BethelSessionViewBundle:Default:attendance.html.twig")
     * @param Session $session
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function attendanceAction(Session $session, Request $request) {
        $studentSessions = new \ArrayObject($session->getStudentSessions());
        $iterator = $studentSessions->getIterator();
        // Sort alphabetically by last name
        $iterator->uasort(function (StudentSession $a, StudentSession $b) {
            return ($a->getStudent()->getLastName() < $b->getStudent()->getLastName()) ? -1 : 1;
        });

        $tutorSessions = new \ArrayObject($session->getTutorSessions());
        $tutorIterator = $tutorSessions->getIterator();

        $tutorIterator->uasort(function (TutorSession $a, TutorSession $b) {
            return ($a->getTutor()->getLastName() < $b->getTutor()->getLastName()) ? -1 : 1;
        });

        return array(
            'user' => $this->getUser(),
            'studentSessions' => $studentSessions,
            'tutorSessions' => $tutorSessions,
            'session' => $session
        );
    }

    /**
     * @Route("/view/{id}", name="session_view")
     * @Template("BethelSessionViewBundle:Default:view.html.twig")
     * @ParamConverter("session", class="BethelEntityBundle:Session")
     * @param Session $session
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function viewAction(Session $session, Request $request) {
        $em = $this->getEntityManager();

        $studentSessions = new \ArrayObject($session->getStudentSessions());
        $iterator = $studentSessions->getIterator();
        // Sort alphabetically by last name
        $iterator->uasort(function (StudentSession $a, StudentSession $b) {
            return ($a->getStudent()->getLastName() < $b->getStudent()->getLastName()) ? -1 : 1;
        });

        $tutorSessions = new \ArrayObject($session->getTutorSessions());
        $tutorIterator = $tutorSessions->getIterator();

        $tutorIterator->uasort(function (TutorSession $a, TutorSession $b) {
            return ($a->getTutor()->getLastName() < $b->getTutor()->getLastName()) ? -1 : 1;
        });

        return array(
            'user' => $this->getUser(),
            'studentSessions' => $studentSessions,
            'tutorSessions' => $tutorSessions,
            'session' => $session
        );
    }

    /**
     * @Route("/start/{id}", name="session_start", defaults={"id" = null})
     * @ParamConverter("session", class="BethelEntityBundle:Session")
     */
    public function startAction(Session $session) {
        $user = $this->getUser();
        $em = $this->getEntityManager();

        $currTime = new \DateTime("now");
        $schedTime = $session->getDate();
        $schedTime->setTime($session->getSchedStartTime()->format('G'), $session->getSchedStartTime()->format('i'));
        $schedCurrDiff = $currTime->diff($schedTime);

        if(!$this->userHasRole($user, 'ROLE_TUTOR') && !$this->userHasRole($user, 'ROLE_ADMIN') && !$this->userHasRole($user, 'ROLE_LEAD_TUTOR')) {
            $this->get('session')->getFlashBag()->add(
                'warning',
                'Session cannot be started. You do not have the necessary permissions.'
            );
            return $this->redirect($this->generateUrl('session'));
        } else if($schedCurrDiff->days == 0 && $schedCurrDiff->h <= 1) {
            $session->setStartTime($currTime);
            $session->setOpen(true);
            $session->setOpener($user);
            $em->persist($session);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                $session->__toString() . ' was started.'
            );
            if($this->userHasRole($user, 'ROLE_TUTOR') || $this->userHasRole($user, 'ROLE_LEAD_TUTOR')) {
                if($this->checkInTutor($user, $session)) {
                    $redirectUrl = $this->container->getParameter('cas.logout') . '?gateway=true&service=' .
                        urlencode($this->generateUrl('session_open', array('hash' => $session->getHash()), true));

                    return $this->redirect($redirectUrl);
                }
            } else {
                $redirectUrl = $this->container->getParameter('cas.logout') . '?gateway=true&service=' .
                    urlencode($this->generateUrl('session_open', array('hash' => $session->getHash()), true));

                return $this->redirect($redirectUrl);
            }
        }

        $this->get('session')->getFlashBag()->add(
            'warning',
            'Session cannot be started. Please ensure that the session is scheduled to start within 2 hours.'
        );
        return $this->redirect($this->generateUrl('session'));
    }

    /**
     * @Route("/stop/{id}", name="session_stop", defaults={"id" = null})
     * @ParamConverter("session", class="BethelEntityBundle:Session")
     * @Template("BethelSessionViewBundle:Default:stop.html.twig")
     * @param Session $session
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function stopAction(Session $session, Request $request) {
        $em = $this->getEntityManager();

        /** @var \Bethel\EntityBundle\Entity\CourseRepository $courseRepository */
        $courseRepository = $em->getRepository('BethelEntityBundle:Course');

        $courseProfs = array();
        $sessionCourseCodes = $session->getCourseCodes();
        $sessionCourseCodes = $sessionCourseCodes->toArray();
        /** @var \Bethel\EntityBundle\Entity\CourseCode $courseCode */
        foreach($sessionCourseCodes as $courseCode) {
            $sessionCourses = $courseRepository->getCoursesByCourseCodeAndSemester(
                $courseCode->getDept(),
                $courseCode->getCourseNum(),
                $this->getActiveSemester()
            );
            /** @var \Bethel\EntityBundle\Entity\Course $course */
            foreach($sessionCourses as $course) {
                $courseProfs[$course->__toString()] = $course->getProfessors();
            }
        }

        // TODO: Authorization for this action
        $form = $this->createForm(new SessionCommentType(), $session);

        /** @var $sessionCommentFormHandler \Bethel\EntityBundle\Form\Handler\SessionCommentFormHandler */
        $sessionCommentFormHandler = $this->get('session_comment_form_handler');


        if($request->isMethod('POST')) {
            $form->submit($request);
            if($form->isValid()) {
                /** @var \Bethel\EntityBundle\Entity\Session $session */
                $session = $form->getData();

                /** @var \Bethel\SessionViewBundle\Services\SessionClose $sessionCloser */
                $sessionCloser = $this->get('bethel.session_close');

                $session = $sessionCloser->close($session);

                if(!$session->getOpen()) {
                    $this->get('session')->getFlashBag()->add(
                        'success',
                        $session->__toString() . ' was stopped.'
                    );
                } else {
                    $this->get('session')->getFlashBag()->add(
                        'warning',
                        'There was a problem closing the session. Please contact an administrator.'
                    );
                }

                return $this->redirect($this->generateUrl('session'));
            } else {
                $this->get('session')->getFlashBag()->add(
                    'warning',
                    'That comment is invalid!'
                );

                return $this->redirect($this->generateUrl('session_stop', array(
                    'id' => $session->getId()
                )));
            }
        }

        return array(
            'user' => $this->getUser(),
            'session' => $session,
            'courseProfs' => $courseProfs,
            'form' => $form
        );
    }

    /**
     * @Route("/open/{hash}", name="session_open")
     * @ParamConverter("session", class="BethelEntityBundle:Session", options={"hash" = "hash"})
     * @Template("BethelSessionViewBundle:Default:open.html.twig")
     * @param Session $session
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function openAction(Session $session) {
        $em = $this->getEntityManager();
        // Template for in progress session
        if($session->getOpen()) {
            /** @var \Bethel\EntityBundle\Entity\StudentSessionRepository $studentSessionRepository */
            $studentSessionRepository = $em->getRepository('BethelEntityBundle:StudentSession');
            $studentSessions = $studentSessionRepository->getSessionAttendanceByLastName($session);
            return array(
                'session' => $session,
                'studentSessions' => $studentSessions
            );
        }

        $this->get('session')->getFlashBag()->add(
            'info',
            'Session is not currently open.'
        );

        return $this->redirect($this->generateUrl('session'));
    }

    /**
     * @Route("/open/tutor/{hash}", name="session_tutor_open")
     * @ParamConverter("session", class="BethelEntityBundle:Session", options={"hash" = "hash"})
     * @Template("BethelSessionViewBundle:Default:open_tutor.html.twig")
     * @param Session $session
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function openTutorAction(Session $session, Request $request) {

        // Template for in progress session
        if($session->getOpen()) {
            // we need to check if there is a user logged in
            // so that we can trigger a CAS logout via js
            // $currSession = $request->getSession()->all();
            // $loggedIn = $currSession ? true : false;
            $userSession = $this->get('session');
            $securityContext = $this->get('security.context');
            $securityContext->setToken(null);
            $userSession->invalidate();

            $courseRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:Course');

            $courseProfs = array();
            $sessionCourseCodes = $session->getCourseCodes();
            $sessionCourseCodes = $sessionCourseCodes->toArray();
            /** @var \Bethel\EntityBundle\Entity\CourseCode $courseCode */
            foreach($sessionCourseCodes as $courseCode) {
                $sessionCourses = $courseRepository->getCoursesByCourseCodeAndSemester(
                    $courseCode->getDept(),
                    $courseCode->getCourseNum(),
                    $this->getActiveSemester()
                );
                /** @var \Bethel\EntityBundle\Entity\Course $course */
                foreach($sessionCourses as $course) {
                    $courseProfs[$course->__toString()] = $course->getProfessors();
                }
            }

            return array(
                'session' => $session,
                'courseProfs' => $courseProfs
            );
        }

        $this->get('session')->getFlashBag()->add(
            'info',
            'Session is not currently open.'
        );

        return $this->redirect($this->generateUrl('session'));
    }

    /**
     * @Route("/checkout/{id}/{sessionid}", name="session_checkout")
     * @ParamConverter("student", class="BethelEntityBundle:User", options={"id" = "id"})
     * @ParamConverter("session", class="BethelEntityBundle:Session", options={"id" = "sessionid"})
     * @param User $user
     * @param Session $session
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function checkoutAction(User $user, Session $session) {

        if($session->getOpen()) {
            /** @var $studentSession StudentSession */
            $studentSession = $this
                ->getEntityManager()
                ->getRepository('BethelEntityBundle:StudentSession')
                ->findOneBy(
                    array(
                        'student' => $user->getId(),
                        'session' => $session->getId(),
                        'timeOut' => null
                    )
                );

            if($studentSession) {
                if(!$studentSession->getTimeOut()) {
                    $studentSession->setTimeOut(new \DateTime("now"));
                    $this->getEntityManager()->persist($studentSession);
                    $this->getEntityManager()->flush();

                    $this->get('session')->getFlashBag()->add(
                        'success',
                        $studentSession->getStudent() . ' was signed out.'
                    );
                } else {
                    $this->get('session')->getFlashBag()->add(
                        'warning',
                        $studentSession->getStudent() . ' has already been signed out.'
                    );
                }

                $redirectUrl = $this->container->getParameter('cas.logout') . '?gateway=true&service=' .
                    urlencode($this->generateUrl('session_open', array('hash' => $session->getHash()), true));
            } else {
                /** @var $tutorSession TutorSession */
                $tutorSession = $this
                    ->getEntityManager()
                    ->getRepository('BethelEntityBundle:TutorSession')
                    ->findOneBy(
                        array(
                            'tutor' => $user->getId(),
                            'session' => $session->getId(),
                            'timeOut' => null
                        )
                    );

                if($tutorSession) {
                    if(!$tutorSession->getTimeOut()) {
                        $tutorSession->setTimeOut(new \DateTime("now"));
                        $this->getEntityManager()->persist($tutorSession);
                        $this->getEntityManager()->flush();

                        $this->get('session')->getFlashBag()->add(
                            'success',
                            $tutorSession->getTutor() . ' was signed out.'
                        );
                    } else {
                        $this->get('session')->getFlashBag()->add(
                            'warning',
                            $tutorSession->getTutor() . ' has already been signed out.'
                        );
                    }

                    $redirectUrl = $this->container->getParameter('cas.logout') . '?gateway=true&service=' .
                        urlencode($this->generateUrl('session_tutor_open', array('hash' => $session->getHash()), true));
                } else {
                    $this->get('session')->getFlashBag()->add(
                        'warning',
                        'You are not currently signed in.'
                    );
                }
            }
        } else {
            $this->get('session')->getFlashBag()->add(
                'info',
                'Session is not currently open.'
            );
        }

        if(!isset($redirectUrl)) {
            $redirectUrl = $this->container->getParameter('cas.logout') . '?gateway=true&service=' .
                urlencode($this->generateUrl('session_open', array('hash' => $session->getHash()), true));
        }

        return $this->redirect($redirectUrl);
    }

    /**
     * @Route("/checkin/{id}", name="session_checkin", defaults={"id" = null})
     * @ParamConverter("session", class="BethelEntityBundle:Session")
     * @Template("BethelSessionViewBundle:Default:checkin.html.twig")
     * @param Session $session
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function checkinAction(Session $session, Request $request) {
        // Template for in progress session

        /** @var $user \Bethel\EntityBundle\Entity\User */
        $user = $this->getUser();
        if($session->getOpen()) {

            $sessionHash = $session->getHash();
            /** @var EntityManager $em */
            $em = $this->getEntityManager();


            if(!$this->userHasRole($user, 'ROLE_STUDENT')) {
                /** @var \Doctrine\ORM\EntityRepository $roleRepository */
                $roleRepository = $em->getRepository('BethelEntityBundle:Role');
                /** @var \Bethel\EntityBundle\Entity\Role $studentRole */
                $studentRole = $roleRepository->findOneBy(array(
                    'role' => 'ROLE_STUDENT'
                ));
                $user->addRole($studentRole);
                $em->persist($user);
                $em->flush();
            }

            $activeStudentSession = $em->getRepository('BethelEntityBundle:StudentSession')->findOneBy(
                array(
                    'student' => $user,
                    'session' => $session,
                    'timeOut' => null
                )
            );

            if($activeStudentSession) {
                $this->get('session')->getFlashBag()->add(
                    'info',
                    'You must first sign out of any active session before signing in'
                );

                // We redirect to the CAS logout page with a service parameter of
                // the URL of the open session.
                $redirectUrl = $this->container->getParameter('cas.logout') . '?gateway=true&service=' .
                    urlencode($this->generateUrl('session_open', array('hash' => $sessionHash), true));
                
                return $this->redirect($redirectUrl);
            } else {
                // If the student has already signed in we will let them know about it
                $studentSessions = $em->getRepository('BethelEntityBundle:StudentSession')->findBy(array('student' => $user, 'session' => $session));
                /** @var \Bethel\EntityBundle\Entity\StudentSession $studentSession */
                foreach ($studentSessions as $studentSession) {

                    if($studentSession->getMinutes() > 0) {
                        $msg = 'You have already been here for ';
                        /** @var \Bethel\EntityBundle\Entity\Course $course */
                        foreach($studentSession->getCourses() as $course) {
                            $msg .= $course->getTitle() . ', ';
                        }
                        $msg .= ' and spent ' . $studentSession->getMinutes() . ' minutes this session';

                        $this->get('session')->getFlashBag()->add(
                            'info',$msg
                        );
                    }
                }

                $studentSession = new StudentSession();

                $username = $user->getUsername();
                // Connected to the WSAPIController
                $wsapi = $this->get('bethel.wsapi_controller');
                $apiCourses = $wsapi->getCourses($username);

                // TODO: don't call the API twice for each checkin

                // here we're going to handle all of the database stuff related to
                // fetching and storing these courses, checking whether the entities
                // exist in the database and creating them if they don't
                $populateCoursesService = $this->get('bethel.populate_courses');
                $courses = $populateCoursesService->populate($apiCourses, $session);

                if(count($courses) > 0) {
                    /** @var \Bethel\EntityBundle\Entity\Course $course */
                    foreach($courses as $course) {
                        $user->addCourse($course);
                    }
                    $em->persist($user);
                    $em->flush();
                }

                $form = $this->createForm(new StudentSigninType($courses), $studentSession);

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $studentSession->setStudent($user);
                    $studentSession->setSession($session);
                    $studentSession->setTimeIn(new \DateTime("now"));
                    $em->persist($studentSession);
                    $em->flush();

                    $this->get('session')->getFlashBag()->add(
                        'success',
                        'You have been checked in.'
                    );

                    // We redirect to the CAS logout page with a service parameter of
                    // the URL of the open session.
                    $redirectUrl = $this->container->getParameter('cas.logout') . '?gateway=true&service=' .
                        urlencode($this->generateUrl('session_open', array('hash' => $sessionHash), true));

                    return $this->redirect($redirectUrl);
                } else if ($form->isSubmitted() && !$form->isValid()) {
                    $formErrors = $form->getErrors();

                    if ($formErrors->count() > 0) {
                        foreach ($formErrors as $formError) {
                            $this->get('session')->getFlashBag()->add(
                                'warning',
                                $formError->getMessage()
                            );
                        }
                    } else {
                        $this->get('session')->getFlashBag()->add(
                            'warning',
                            'Your submission was invalid.'
                        );
                    }

                    return $this->redirect($this->generateUrl('session_checkin', array(
                        'id' => $session->getId()
                    )));
                }
            }

            return array(
                'user' => $this->getUser(),
                'form' => $form,
                'sessionHash' => $sessionHash
            );
        }

        $this->get('session')->getFlashBag()->add(
            'info',
            'Session is not currently open.'
        );
        return $this->redirect($this->generateUrl('session'));
    }

    /**
     * @Route("/checkin/confirm/{id}", name="checkin_confirmation")
     * @ParamConverter("session", class="BethelEntityBundle:Session")
     * @Template("BethelSessionViewBundle:Default:checkin_confirmation.html.twig")
     * @param Session $session
     * @return array
     */
    public function checkInConfirmation(Session $session) {
        return array(
            'user' => $this->getUser(),
            'session' => $session
        );
    }

    /**
     * @Route("/checkin/tutor/{id}", name="session_tutor_checkin", defaults={"id" = null})
     * @ParamConverter("session", class="BethelEntityBundle:Session")
     * @Template("BethelSessionViewBundle:Default:checkin.html.twig")
     * @param Session $session
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function checkInTutorView(Session $session) {
        /** @var $user \Bethel\EntityBundle\Entity\User */
        $user = $this->getUser();
        if($session->getOpen() && $this->userHasRole($user, 'ROLE_TUTOR') || $this->userHasRole($user, 'ROLE_LEAD_TUTOR')) {

            $sessionHash = $session->getHash();
            if ($this->checkInTutor($user, $session)) {
                // We redirect to the CAS logout page with a service parameter of
                // the URL of the open session.
                $redirectUrl = $this->container->getParameter('cas.logout') . '?gateway=true&service=' .
                    urlencode($this->generateUrl('session_tutor_open', array('hash' => $sessionHash), true));

                return $this->redirect($redirectUrl);
            }
        }

        $this->get('session')->getFlashBag()->add(
            'warning',
            'You do not have sufficient permissions to sign in as a Tutor'
        );

        return $this->redirect($this->generateUrl('checkin_confirmation', array(
            'id' => $session->getId()
        )));
    }

    /**
     * @param \Bethel\EntityBundle\Entity\User $user
     * @param \Bethel\EntityBundle\Entity\Session $session
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function checkInTutor($user, $session) {
        $em = $this->getEntityManager();
        $sessionHash = $session->getHash();
        /** @var $tutorSession \Bethel\EntityBundle\Entity\TutorSession */
        $tutorSession = $em->getRepository('BethelEntityBundle:TutorSession')->findOneBy(array('tutor'=>$user,'session'=>$session));
        if ($tutorSession && $tutorSession->getTimeIn()) {
            $tutorSession->setTimeIn(null);
            $tutorSession->setTimeOut(null);
        }

        if($tutorSession) {
            $tutorSession->setTimeIn(new \DateTime("now"));
            $em->persist($tutorSession);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                'You have been checked in.'
            );

            return true;
        } else {
            $tutorSession = new TutorSession();
            $tutorSession->setTutor($user);
            $tutorSession->setSession($session);
            $tutorSession->setTimeIn(new \DateTime("now"));
            $tutorSession->setLead(false);
            $em->persist($tutorSession);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                'You have been checked in.'
            );

            return true;
        }
    }

    /**
     * @Route("/deleted", name="session_deleted")
     * @Template("BethelSessionViewBundle:Default:deleted.html.twig")
     */
    public function deletedAction() {
        $em = $this->getEntityManager();
        $semester = $this->getSessionSemester();

        /** @var $user \Bethel\EntityBundle\Entity\User */
        $user = $this->getUser();
        if( $this->userHasRole($user, 'ROLE_ADMIN') ) {
            /** @var $sessions \Bethel\EntityBundle\Entity\Session */
            $sessionRepository = $em->getRepository('BethelEntityBundle:Session');
            $sessions = $sessionRepository->getDeletedSessions($semester);

            $sessionContainer = array();
            /** @var \Bethel\EntityBundle\Entity\Session $closedSession */
            foreach($sessions as $closedSession) {
                $tutorSessions = $closedSession->getTutorSessions();
                $tutors = array();
                $leadTutors = array();
                /** @var \Bethel\EntityBundle\Entity\TutorSession $tutorSession */
                foreach($tutorSessions as $tutorSession) {
                    if($tutorSession->getLead()) {
                        if( $tutorSession->getTutor() )
                            $leadTutors[] = $tutorSession->getTutor()->__toString();
                    } else {
                        if( $tutorSession->getTutor() )
                            $tutors[] = $tutorSession->getTutor()->__toString();
                    }
                }
                $sessionContainer[] = array(
                    'tutors' => $tutors,
                    'leadTutors' => $leadTutors,
                    'session' => $closedSession
                );
            }

            $returnValue = $this->render('BethelSessionViewBundle:Default:deleted.html.twig', array(
                'user' => $this->getUser(),
                'sessionContainer' => $sessionContainer,
                'selectedSemester' => $semester
            ));

            return $returnValue;
        }

        return $this->redirect($this->generateUrl('session'));
    }

    /**
     * @Route("/restore/{id}", name="session_restore", defaults={"id" = null})
     */
    public function restoreAction($id) {
        $em = $this->getEntityManager();

        $em->getFilters()->disable('softdeleteable');
        $sessionRepository = $em->getRepository('BethelEntityBundle:Session');

        /** @var $session \Bethel\EntityBundle\Entity\Session */
        $session = $sessionRepository->find(array('id' => $id));

        $session->setDeletedAt(null);
        $em->persist($session);
        $em->flush();

        $em->getFilters()->enable('softdeleteable');

        return $this->redirect($this->generateUrl('session'));
    }
}
