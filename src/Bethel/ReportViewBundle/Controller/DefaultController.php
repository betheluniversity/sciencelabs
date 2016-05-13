<?php

namespace Bethel\ReportViewBundle\Controller;

use Bethel\EntityBundle\Entity\Course;
use Bethel\EntityBundle\Entity\Session;
use Bethel\EntityBundle\Entity\StudentSession;
use Bethel\EntityBundle\Entity\User;
use Bethel\EntityBundle\Form\SessionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Bethel\FrontBundle\Controller\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Validator\Constraints\DateTime;


/**
 * @Route("/report")
 */
class DefaultController extends BaseController
{   
    /**
     * @Route("/test-email/{sessionid}/{username}", name="test-email")
     * @ParamConverter("session", class="BethelEntityBundle:Session", options={"id" = "sessionid"})
     * @ParamConverter("user", class="BethelEntityBundle:User", options={"username" = "username"})
     * @Template("BethelReportViewBundle:Default:test-email.html.twig")
     * @param Session $session
     */
    public function testEmailAction(Session $session, User $user) {

        $em = $this->getEntityManager();
        $sessionRepository = $em->getRepository('BethelEntityBundle:Session');
        $courseRepository = $em->getRepository('BethelEntityBundle:Course');
        $userRepository = $em->getRepository('BethelEntityBundle:User');

        $reportCourses = array();
        $sessionCourses = $courseRepository->getAttendedSessionCourses($session);
        foreach( $sessionCourses as $course){
            if( $course->getProfessors()->contains($user) )
                array_push($reportCourses, $course);
        }

        // We only want to send professors an email if they teach a course
        // that at least one student attended for at this session
        $sessionEmailService = $this->get('bethel.session_email');
        $sessionEmailer = $sessionEmailService->create($session, $user, $reportCourses);
        $test = $sessionEmailer->sendEmail(true);

        return array('test' => $test);
    }

    /**
     * @Route("/email/{sessionid}/{userid}", name="report_email")
     * @ParamConverter("session", class="BethelEntityBundle:Session", options={"id" = "sessionid"})
     * @ParamConverter("emailRecipient", class="BethelEntityBundle:User", options={"id" = "userid"})
     * @param Session $session
     * @param User $emailRecipient
     */
    public function emailAction(Session $session, User $emailRecipient) {
        $em = $this->getEntityManager();
        $message = array();
        if($this->userHasRole($emailRecipient,'ROLE_ADMIN')) {
            $sessionEmailer = $this->get('bethel.session_email')->create($session, $emailRecipient);
            $message = $sessionEmailer->sendEmail(false, true);
        } elseif($this->userHasRole($emailRecipient,'ROLE_PROFESSOR')) {
            /** @var \Bethel\EntityBundle\Entity\CourseRepository $courseRepository */
            $courseRepository = $em->getRepository('BethelEntityBundle:Course');

            $reportCourses = array();
            $sessionCourses = $courseRepository->getAttendedSessionCourses($session);
            foreach( $sessionCourses as $course){
                if( $course->getProfessors()->contains($emailRecipient) )
                    array_push($reportCourses, $course);
            }


            // We only want to send professors an email if they teach a course
            // that at least one student attended for at this session
            if($reportCourses) {
                $sessionEmailer = $this->get('bethel.session_email')->create($session, $emailRecipient, $reportCourses);
                $message = $sessionEmailer->sendEmail(false, false);
            }
        }

        return new Response("<html><body>" . $message['message'] . "</body></html>", 200);
    }

    /**
     * @Route("/", name="report")
     * @Template("BethelReportViewBundle:Default:index.html.twig")
     */
    public function indexAction() {
        return array(
            'user' => $this->getUser()
        );
    }

    /**
     * @Route("/session", name="report_session")
     * @Template("BethelReportViewBundle:Default:sessions.html.twig")
     */
    public function sessionAction() {
        $em = $this->getEntityManager();
        $em->getFilters()->enable('softdeleteable');
        // TODO: only fetch sessions that are open or over.
        /** @var $sessionRepository \Bethel\EntityBundle\Entity\SessionRepository */
        $sessionRepository = $em->getRepository('BethelEntityBundle:Session');
        /** @var $sessionSemester \Bethel\EntityBundle\Entity\Semester */
        $sessionSemester = $this->getSessionSemester();

        $sessions = $sessionRepository->getClosedSessions($sessionSemester);

        $sessionResults = array();
        $monthTotals = array();

        foreach($sessions as $session) {
            $result = array();
            $result['session'] = $session;
            /** @var $sessionRepo \Bethel\EntityBundle\Entity\SessionRepository */
            $sessionRepo = $this->getEntityManager()->getRepository('BethelEntityBundle:Session');
            $result['total'] = $sessionRepo->getSessionAttendeeTotal($session);

            // F	A full textual representation of a month, such as January or March	January through December
            // if there are no sessions for the month of the current session yet, we need to create the key
            array_key_exists($session->getDate()->format('F'), $sessionResults) ? : $sessionResults[$session->getDate()->format('F')] = array();
            array_key_exists($session->getDate()->format('F'), $monthTotals) ? : $monthTotals[$session->getDate()->format('F')] = 0;
            $monthTotals[$session->getDate()->format('F')] += $result['total'];
            array_push($sessionResults[$session->getDate()->format('F')],$result);
        }

        uksort($sessionResults,array($this,"sessionMonthSort"));

        foreach($sessionResults as &$sessionResult) {
            usort($sessionResult, array($this,"sessionDateSort"));
        }
        $em->getFilters()->disable('softdeleteable');
        return array(
            'sessionResults' => $sessionResults,
            'monthTotals' => $monthTotals,
            'user' => $this->getUser()
        );
    }

    /**
     * @Route("/export/session", name="export_session")
     * @Template()
     */
    public function exportSessionAction()
    {
        // get the service container to pass to the closure
        $container = $this->container;
        $results = $this->sessionAction();
        $title = call_user_func(get_class($this). '::formatExportTitle', 'SessionReport');

        $response = new StreamedResponse(function() use($container, $results, $title) {

            $results = $results['sessionResults'];
            $handle = fopen('php://output', 'r+');   

            fputcsv($handle, array($title, 'Exported on: ', date('m/d/Y')));
            fputcsv($handle, array());
            fputcsv($handle, array(
                "Date",
                "Name",
                "DOW",
                "Start Time",
                "End Time",
                "Room",
                "Total Attendance",
                "Comments"
            ));

            $totals = 0;
            foreach($results as $month) {
                foreach($month as $session) {
                    /** @var \Bethel\EntityBundle\Entity\Session $sessionEntity */
                    $sessionEntity = $session['session'];
                    $sessionArray = array(
                        $sessionEntity->getDate()->format('m/d/Y'),
                        $sessionEntity->getName(),
                        $sessionEntity->getDate()->format('D'),
                        $sessionEntity->getStartTime()->format('g:ia'),
                        $sessionEntity->getEndTime()->format('g:ia'),
                        $sessionEntity->getRoom(),
                        $session['total'],
                        $sessionEntity->getComments()
                    );
                    fputcsv($handle, $sessionArray);
                    $totals += $session['total'];
                }
            }

            fputcsv($handle, array(
                "",
                "",
                "",
                "",
                "",
                "Total:",
                $totals
            ));

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition','attachment; filename="' . $title . '.csv"');

        return $response;
    }

    /**
     * @Route("/month/{year}/{month}", name="report_month", defaults={"year" = null, "month" = null})
     * @Template("BethelReportViewBundle:Default:month.html.twig")
     * @param null $year
     * @param null $month
     * @param Request $request
     * @return array
     * @internal param null $overrideSessionSemester
     */
    public function monthAction($year = null, $month = null, Request $request, $csv = false) {
        $em = $this->getEntityManager();
        // by default, we'll show the current month
        $date = new \DateTime("now");
        $sessionSemester = $this->getSessionSemester();

        if(!$year || !$month) {
            $year = $sessionSemester->getYear();
            $month = $sessionSemester->getStartDate();
            $month = date_format($month, "n");


            return $this->redirect($this->generateUrl('report_month', array(
                'year' => $year,
                'month' => $month
            )));
        }

        $semesterStartMonth = (int)$sessionSemester->getStartDate()->format('n');
        $semesterEndMonth = (int)$sessionSemester->getEndDate()->format('n');
        $date->setDate($year,$month,1);

        // Checking to see if the month we're navigating to is within
        // session semester range. If not we'll redirect to the first
        // month of the current session semester.
        if(
            $year != $sessionSemester->getYear() ||
            $semesterStartMonth > $month ||
            $semesterEndMonth < $month
        ) {
            $referer = $request->headers->get('referer');
            $referer = explode('/', $referer);
            $referer = array_slice($referer, -1);
            $referer = $referer[0];
            if($referer != 'annual') {
                return $this->redirect($this->generateUrl('report_month', array(
                    'year' => $sessionSemester->getYear(),
                    'month' => $semesterStartMonth
                )));
            } else {
                // We need to change the session semester to reflect the date
                $semesterRepository = $em->getRepository('BethelEntityBundle:Semester');
                $queryDate = \DateTime::createFromFormat('n/j/Y', $month . '/1/' . $year);
                try {
                    $semester = $semesterRepository->getSemesterByMonth($queryDate);
                } catch(NoResultException $e) {
                    $this->get('session')->getFlashBag()->add(
                        'warning',
                        'There is no session data in the system for the month of ' . $queryDate->format('F Y')
                    );
                    return $this->redirect($this->generateUrl('report_annual'));
                }

                $this->setSessionSemester($semester);
                $semesterStartMonth = (int) $semester->getStartDate()->format('n');
                $semesterEndMonth = (int) $semester->getEndDate()->format('n');
            }

        }

        $semesterMonths = array();

        do {
            $semesterMonths[] = $semesterStartMonth;
            $semesterStartMonth++;
        } while ($semesterStartMonth <= $semesterEndMonth);

        $firstDay = clone $date;
        $firstDay->modify("first day of this month");

        $firstDay->setTime(0,0,0);

        $lastDay = clone $firstDay;
        $lastDay
            ->modify("last day of this month");

        /** @var $sessionRepository \Bethel\EntityBundle\Entity\SessionRepository */
        $sessionRepository = $em->getRepository('BethelEntityBundle:Session');
        $monthSessions = $sessionRepository->getSessionsInDateRange($firstDay,$lastDay);

        $scheduleData = array();
        $otherMonthSessionsTotal = 0;
        $totalAttendance = 0;
        $realTotalArray = array();
        /** @var \Bethel\EntityBundle\Entity|Session $monthSession */
        foreach($monthSessions as $monthSession) {
            $schedule = $monthSession->getSchedule();
            if($schedule) {
                if(!array_key_exists($schedule->__toString(), $scheduleData)) {
                    $scheduleData[$schedule->__toString()] = array(
                        'attendance' => 0,
                        'dow' => $monthSession->getDate()->format('D'),
                        'startTime' => $schedule->getStartTime(),
                        'endTime' => $schedule->getEndTime(),
                        'name' => $schedule->__toString()
                    );
                }
                $scheduleData[$schedule->__toString()]['attendance'] += $sessionRepository->getSessionAttendeeTotal($monthSession);
                $totalAttendance += $sessionRepository->getSessionAttendeeTotal($monthSession);
            } else {
                $otherMonthSessionsTotal += $sessionRepository->getSessionAttendeeTotal($monthSession);
                $totalAttendance += $sessionRepository->getSessionAttendeeTotal($monthSession);
            }
            $realTotalArray[$monthSession->getId()] = $sessionRepository->getSessionAttendeeTotal($monthSession);
            
        }

        $arrayContents = array(
            'user' => $this->getUser(),
            'firstDay' => $firstDay,
            'lastDay' => $lastDay,
            'monthSessions' => $monthSessions,
            'totalAttendance' => $totalAttendance,
            'sessionSemester' => $sessionSemester,
            'semesterMonths' => $semesterMonths,
            'scheduleData' => $scheduleData,
            'otherMonthSessionsTotal' => $otherMonthSessionsTotal,
            'realTotalArray' => $realTotalArray,
            );

        // have a different return for csv
        if( $csv )
            return $arrayContents;

        $em->getFilters()->disable('softdeleteable');
        $returnValue = $this->render('BethelReportViewBundle:Default:month.html.twig', $arrayContents);
        $em->getFilters()->enable('softdeleteable');
        return $returnValue;
    }

    /**
     * @Route("/export/month/{year}/{month}/schedule", name="export_month_schedule")
     * @Template()
     * @param int $year
     * @param int $month
     * @param Request $request
     * @return StreamedResponse
     */
    public function exportMonthScheduleAction($year, $month, Request $request)
    {
        // This has been renamed to "Summary Export"
        // get the service container to pass to the closure
        $container = $this->container;
        $results = $this->monthAction((int)$year, (int)$month, $request, true);
        $monthName = \DateTime::createFromFormat('m', (int)$month)->format('F');
        $title = call_user_func(get_class($this) . '::formatExportTitle', ucwords(strtolower($monthName)) . '_SummaryReport');

        $response = new StreamedResponse(function() use($container, $results, $title) {

            //$em = $container->get('doctrine')->getManager();
            $handle = fopen('php://output', 'r+');
            
            fputcsv($handle, array($title, 'Exported on: ', date('m/d/Y')));
            fputcsv($handle, array());
            fputcsv($handle, array(
                "Schedule Name",
                "DOW",
                "Schedule Time",
                "Total Attendance",
                "% Total"
            ));

            $attendance = 0;
            foreach($results['scheduleData'] as $key => $schedule) {
                if( $key != "Unscheduled Sessions"){
                    fputcsv($handle, array(
                        $key,
                        $schedule['dow'],
                        $schedule['startTime']->format('g:ia') . ' - ' . $schedule['endTime']->format('g:ia'),
                        $schedule['attendance'],
                        round(($schedule['attendance']/($results['totalAttendance']+$results['otherMonthSessionsTotal']))*100,1) . '%'
                    ));
                    $attendance += $schedule['attendance'];
                }

            }

            fputcsv($handle, array(
                "Unscheduled Sessions",
                "",
                "",
                $results['otherMonthSessionsTotal'],
                round(($results['otherMonthSessionsTotal']/($results['totalAttendance']+$results['otherMonthSessionsTotal']))*100,1) . '%'
            ));
            fputcsv($handle, array(
                "",
                "",
                "Total:",
                $attendance + $results['otherMonthSessionsTotal']
            ));
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition','attachment; filename="' . $title . '.csv"');

        return $response;
    }

    /**
     * @Route("/export/month/{year}/{month}/session", name="export_month_session")
     * @Template()
     * @param int $year
     * @param int $month
     * @param Request $request
     * @return StreamedResponse
     */
    public function exportMonthSessionAction($year, $month, Request $request)
    {
        // This has been renamed to "Detail Export"
        // get the service container to pass to the closure
        $container = $this->container;
        $results = $this->monthAction((int)$year, (int)$month, $request, true);
        $monthName = \DateTime::createFromFormat('m', (int)$month)->format('F');
        $title = call_user_func(get_class($this). '::formatExportTitle', ucwords(strtolower($monthName)) . '_DetailReport');

        $response = new StreamedResponse(function() use($container, $results, $title) {

            //$em = $container->get('doctrine')->getManager();


            $handle = fopen('php://output', 'r+');
            
            fputcsv($handle, array($title, 'Exported on: ', date('m/d/Y')));
            fputcsv($handle, array());
            fputcsv($handle, array(
                "Name",
                "Date",
                "DOW",
                "Scheduled Time",
                "Total Attendance"
            ));

            $totalAttendance = 0;
            /** @var \Bethel\EntityBundle\Entity\Session $session */
            foreach($results['monthSessions'] as $session) {
                if(  $session->getEndTime() ){
                    fputcsv($handle, array(
                        $session->getName(),
                        $session->getDate()->format('m/d/Y'),
                        $session->getDate()->format('D'),
                        $session->getSchedStartTime()->format('g:ia') . '-' . $session->getSchedEndTime()->format('g:ia'),
                        $results['realTotalArray'][$session->getId()]
                        // count($session->getStudentSessions()) + $session->getAnonStudents()
                    ));
                    $totalAttendance += $results['realTotalArray'][$session->getId()];
                }
            }

            fputcsv($handle, array(
                "",
                "",
                "Total:",
                $totalAttendance
            ));
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition','attachment; filename="' . $title . '.csv"');

        return $response;
    }

    /**
     * @Route("/annual/{year}", name="report_annual", defaults={"year" = null})
     * @Template("BethelReportViewBundle:Default:annual.html.twig")
     * @param int|null $year
     * @return array
     */
    public function annualAction($year = null) {
        $em = $this->getEntityManager();
        // by default, we'll show the current month
        $date = new \DateTime("now");

        // if an explicit month and year have been passed, we'll show that instead
        // month and year are integers
        if(!$year) {
            $year = (int) $date->format("Y");
            $year -= 1;
        }

        $monthly = array();

        /** @var $studentSessionRepository \Bethel\EntityBundle\Entity\StudentSessionRepository */
        $studentSessionRepository = $em->getRepository('BethelEntityBundle:StudentSession');
        $sessionRepository = $em->getRepository('BethelEntityBundle:Session');
        $semesterRepository = $em->getRepository('BethelEntityBundle:Semester');

        $semesterQb = $semesterRepository->createQueryBuilder('s');

        $years = $semesterQb
            ->select('s.year')
            ->groupBy('s.year');

        $years = $years->getQuery()->getArrayResult();
        $years = array_map('current', $years);
        // Include the year before the first semester was created
        array_unshift($years, $years[0] - 1);

        $yearData = array();
        foreach($years as $year) {

            $monthly = array();
            for($i=0; $i < 12; $i++) {
                // convert to academic year (August - July)
                $j = $i + 8;
                if ($j > 12) {
                    $j -= 12;
                }
                if ($j == 1) {
                    $year += 1;
                }
                $dateString = $year . "-" . $j . "-1";
                $month = \DateTime::createFromFormat("Y-n-j", $dateString);
                $firstDay = clone $month;
                $firstDay->modify("first day of this month");

                $firstDay->setTime(0,0,0);

                $lastDay = clone $firstDay;
                $lastDay
                    ->modify("last day of this month");

                // For each month, we get the total attendance
                $monthSessions = $sessionRepository->getSessionsInDateRange($firstDay,$lastDay);
                $currentTotal = 0;
                foreach($monthSessions as $monthSession) {
                    $currentTotal += $sessionRepository->getSessionAttendeeTotal($monthSession);
                }
                $monthly[(int)$month->format('n')] = $currentTotal;
            }
            // Spring Total = Feb - Jul
            $springMonths = array_slice($monthly, 6, 10);
            $springTotal = array_sum($springMonths);

            // Fall Total = Aug - Dec
            $fallMonths = array_slice($monthly, 0, 5);
            $fallTotal = array_sum($fallMonths);

            // Summer Total
            $summerMonths = array_slice($monthly, 10, 12);
            $summerTotal = array_sum($summerMonths);
            $yearData[$year] = array(
                'academicYear' => $year - 1 . '-' . $year,
                'monthly' => $monthly,
                'springTotal' => $springTotal,
                'fallTotal' => $fallTotal,
                'summerTotal' => $summerTotal,
                'yearTotal' => array_sum($monthly)
            );
        }

        ///////////////////// Old Data hardcoded -- not worth the time to replace this with a dynamic solution //////////////////////
        if( $this->container->getParameter('app.title') == "Math Lab" ){
            $yearData['1993'] = array(
                'academicYear' => '1992-1993',
                'monthly' => array(0, 0, 90, 58, 80, 32, 0, 0, 0, 98, 99, 69, 14),
                'springTotal' => 260,
                'fallTotal' => 280,
                'summerTotal' => 0,
                'yearTotal' => 540
            );
            $yearData['1994'] = array(
                'academicYear' => '1993-1994',
                'monthly' => array(0, 0, 85, 100, 93, 29, 0, 0, 0, 136, 150, 131, 50),
                'springTotal' => 307,
                'fallTotal' => 467,
                'summerTotal' => 0,
                'yearTotal' => 774
            );
            $yearData['1995'] = array(
                'academicYear' => '1994-1995',
                'monthly' => array(0, 0, 103, 84, 93, 31, 0, 0, 0, 95, 96, 88, 20),
                'springTotal' => 311,
                'fallTotal' => 299,
                'summerTotal' => 0,
                'yearTotal' => 610
            );
            $yearData['1996'] = array(
                'academicYear' => '1995-1996',
                'monthly' => array(0, 0, 104, 83, 93, 27, 0, 0, 0, 138, 245, 208, 42),
                'springTotal' => 307,
                'fallTotal' => 633,
                'summerTotal' => 0,
                'yearTotal' => 940
            );
            $yearData['1997'] = array(
                'academicYear' => '1996-1997',
                'monthly' => array(0, 0, 81, 144, 191, 49, 0, 0, 0, 153, 175, 117, 12),
                'springTotal' => 465,
                'fallTotal' => 457,
                'summerTotal' => 0,
                'yearTotal' => 922
            );
            $yearData['1998'] = array(
                'academicYear' => '1997-1998',
                'monthly' => array(0, 0, 65, 60, 51, 16, 0, 0, 0, 88, 146, 99, 27),
                'springTotal' => 192,
                'fallTotal' => 360,
                'summerTotal' => 0,
                'yearTotal' => 552
            );
            $yearData['1999'] = array(
                'academicYear' => '1998-1999',
                'monthly' => array(0, 0, 61, 107, 106, 56, 0, 0, 0, 123, 156, 116, 42),
                'springTotal' => 330,
                'fallTotal' => 437,
                'summerTotal' => 0,
                'yearTotal' => 767
            );
            $yearData['2000'] = array(
                'academicYear' => '1999-2000',
                'monthly' => array(0, 0, 143, 183, 194, 133, 0, 0, 0, 203, 262, 259, 59),
                'springTotal' => 653,
                'fallTotal' => 783,
                'summerTotal' => 0,
                'yearTotal' => 1436
            );
            $yearData['2001'] = array(
                'academicYear' => '2000-2001',
                'monthly' => array(0, 0, 146, 157, 153, 49, 0, 0, 0, 222, 337, 226, 71),
                'springTotal' => 505,
                'fallTotal' => 856,
                'summerTotal' => 0,
                'yearTotal' => 1361
            );
            $yearData['2002'] = array(
                'academicYear' => '2001-2002',
                'monthly' => array(0, 36, 132, 135, 226, 121, 0, 0, 0, 225, 238, 192, 50),
                'springTotal' => 614,
                'fallTotal' => 705,
                'summerTotal' => 0,
                'yearTotal' => 1355
            );
            $yearData['2003'] = array(
                'academicYear' => '2002-2003',
                'monthly' => array(0, 19, 187, 234, 273, 155, 0, 0, 0, 167, 274, 215, 47),
                'springTotal' => 849,
                'fallTotal' => 703,
                'summerTotal' => 0,
                'yearTotal' => 1571
            );
            $yearData['2004']['monthly'][9] = 379;
            $yearData['2004']['monthly'][10] = 425;
            $yearData['2004']['monthly'][11] = 346;
            $yearData['2004']['monthly'][12] = 76;
            $yearData['2004']['monthly'][1] = 28;
            $yearData['2004']['fallTotal'] = 1226;
            $yearData['2004']['yearTotal'] = $yearData['2004']['yearTotal'] + 1226 + 28;
        }
        ///////////////////////////////////////////////////////////////

        asort($yearData);
        $yearData = array_reverse($yearData, true);

        return array(
            'user' => $this->getUser(),
            'yearData' => $yearData,
        );
    }

    /**
     * @Route("/export/year", name="export_annual")
     * @Template()
     * @return StreamedResponse
     */
    public function exportAnnualAction()
    {
        // get the service container to pass to the closure
        $container = $this->container;
        $results = $this->annualAction();

        $labs = explode(' ', $this->container->getParameter('app.title'));
        $shortLab = '';
        foreach( $labs as $lab){
            $shortLab .= $lab[0];
        }
        $title = $shortLab . '_CumulativeAttendance';

        $response = new StreamedResponse(function() use($container, $results, $title) {

            //$em = $container->get('doctrine')->getManager();

            $handle = fopen('php://output', 'r+');
            
            fputcsv($handle, array($title, 'Exported on: ', date('m/d/Y')));
            fputcsv($handle, array());
            fputcsv($handle, array(
                "Year",
                "Aug",
                "Sep",
                "Oct",
                "Nov",
                "Dec",
                "Fall",
                "Jan",
                "Feb",
                "Mar",
                "Apr",
                "May",
                "Spring",
                "Jun",
                "Jul",
                "Summer",
                "Total"
            ));

            $augTotal = 0;
            $septTotal = 0;
            $octTotal = 0;
            $novTotal = 0;
            $decTotal = 0;
            $fallTotal = 0;
            $janTotal = 0;
            $febTotal = 0;
            $marTotal = 0;
            $aprTotal = 0;
            $mayTotal = 0;
            $springTotal = 0;
            $junTotal = 0;
            $julyTotal = 0;
            $summerTotal = 0;
            $totalTotal = 0;
            foreach($results['yearData'] as $key => $year) {
                fputcsv($handle, array(
                    $year['academicYear'],
                    $year['monthly']['8'],
                    $year['monthly']['9'],
                    $year['monthly']['10'],
                    $year['monthly']['11'],
                    $year['monthly']['12'],
                    $year['fallTotal'],
                    $year['monthly']['1'],
                    $year['monthly']['2'],
                    $year['monthly']['3'],
                    $year['monthly']['4'],
                    $year['monthly']['5'],
                    $year['springTotal'],
                    $year['monthly']['6'],
                    $year['monthly']['7'],
                    $year['summerTotal'],
                    $year['yearTotal']
                ));
                $augTotal += $year['monthly']['8'];
                $septTotal += $year['monthly']['9'];
                $octTotal += $year['monthly']['10'];
                $novTotal += $year['monthly']['11'];
                $decTotal += $year['monthly']['12'];
                $fallTotal += $year['fallTotal'];
                $janTotal += $year['monthly']['1'];
                $febTotal += $year['monthly']['2'];
                $marTotal += $year['monthly']['3'];
                $aprTotal += $year['monthly']['4'];
                $mayTotal += $year['monthly']['5'];
                $springTotal += $year['springTotal'];
                $junTotal += $year['monthly']['6'];
                $julyTotal += $year['monthly']['7'];
                $summerTotal += $year['summerTotal'];
                $totalTotal += $year['yearTotal'];
            }

            fputcsv($handle, array(
                "Total:",
                $augTotal,
                $septTotal,
                $octTotal,
                $novTotal,
                $decTotal,
                $fallTotal,
                $janTotal,
                $febTotal,
                $marTotal,
                $aprTotal,
                $mayTotal,
                $springTotal,
                $junTotal,
                $julyTotal,
                $summerTotal,
                $totalTotal
            ));
            fclose($handle);
        });

        $labs = explode(' ', $this->container->getParameter('app.title'));
        $shortLab = '';
        foreach( $labs as $lab){
            $shortLab .= $lab[0];
        }
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition','attachment; filename="' . $shortLab . '_CumulativeAttendance.csv"');

        return $response;
    }

    /**
     * @Route("/session/{id}", name="report_single_session", defaults={"id" = null})
     * @ParamConverter("session", class="BethelEntityBundle:Session")
     * @Template("BethelReportViewBundle:Default:session.html.twig")
     * @param Session $session
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function singleSessionAction(Session $session = null, $csv=false) {
        if(!$session) {
            $this->get('session')->getFlashBag()->add(
                'warning',
                'That session does not exist.'
            );

            return $this->redirect($this->generateUrl('report_session'));
        }
        $em = $this->getEntityManager();
        $em->getFilters()->disable('softdeleteable');

        /** @var $sessionRepo \Bethel\EntityBundle\Entity\SessionRepository */
        $sessionRepo = $em->getRepository('BethelEntityBundle:Session');
        $courseRepository = $em->getRepository('BethelEntityBundle:Course');

        $attendees = $sessionRepo->getSessionAttendeeTotal($session);

        /** @var $userRepository \Bethel\EntityBundle\Entity\UserRepository */
        $userRepository = $em->getRepository('BethelEntityBundle:User');

        $scheduledTutors = $userRepository->getScheduledTutors($session);
        $attendeeTutors = $userRepository->getTutorAttendees($session);

        $tutorAttendance = array();

        // Scheduled attendees
        $tutorAttendance['scheduled'] = $this->getTutorCheckinTimes(array_intersect($attendeeTutors,$scheduledTutors), $session);

        // Unscheduled
        $tutorAttendance['unscheduled'] = $this->getTutorCheckinTimes(array_diff($attendeeTutors,$scheduledTutors), $session);

        // Absent tutors
        $tutorAttendance['absent'] = $this->getTutorCheckinTimes(array_diff($scheduledTutors,$attendeeTutors), $session);

        if($this->getUser()->hasRole('ROLE_PROFESSOR') && !$this->getUser()->hasRole('ROLE_ADMIN') && !$this->getUser()->hasRole('ROLE_VIEWER')) {
            $profView = true;
        } else {
            $profView = false;
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

        $arrayContents = array(
            'user'                  => $this->getUser(),
            'session'               => $session,
            'sessionsByCourse'    => $sessionsByCourse,
            'attendees'             => $attendees,
            'tutorAttendance'       => $tutorAttendance,
            'profView'              => $profView
            );

        $returnValue = $this->render('BethelReportViewBundle:Default:session.html.twig', $arrayContents);

        if( $csv)
            return $arrayContents;

        $em->getFilters()->enable('softdeleteable');
        return $returnValue;
    }

    /**
     * @param array $attendees
     * @param Session $session
     * @return array
     */
    private function getTutorCheckinTimes($attendees, $session) {
        /** @var $tutorSessionRepository  */
        $tutorSessionRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:TutorSession');
        $attendeeGroup = array();
        foreach($attendees as $attendee) {
            /** @var \Bethel\EntityBundle\Entity\TutorSession $tutorSession */
            $tutorSession = $tutorSessionRepository->findOneBy(array(
                'tutor' => $attendee,
                'session' => $session
            ));

            array_push($attendeeGroup, array(
                'tutor' => $attendee,
                'timeIn' => $tutorSession->getTimeIn(),
                'timeOut' => $tutorSession->getTimeOut(),
                'minutes' => $tutorSession->getMinutes()
            ));
        }

        return $attendeeGroup;
    }

    /**
     * @Route("/course", name="report_course")
     * @Template("BethelReportViewBundle:Default:courses.html.twig")
     */
    public function courseAction() {
        $user = $this->getUser();

        // TODO: this method doesn't currently count sessions where no course was specified

        /** @var $courseRepository \Bethel\EntityBundle\Entity\CourseRepository */
        $courseRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:Course');

        /** @var $sessionSemester \Bethel\EntityBundle\Entity\Semester */
        $sessionSemester = $this->getSessionSemester();

        $courses = $courseRepository->getSemesterCourses($sessionSemester);

        if ($this->userHasRole($user, 'ROLE_PROFESSOR') && !$this->userHasRole($user,'ROLE_ADMIN')) {
            // If the current user is a professor, we only want to show
            // his or her courses

            $allCourses = $courseRepository->getSemesterCoursesQB($sessionSemester)->getQuery()->getResult();
            $profCurrentCourses = array();
            foreach($allCourses as $course) {
                foreach($course->getProfessors() as $prof){
                    if( $user == $prof){
                        $profCurrentCourses[] = $course;
                    }
                }
            }
            $courses = $profCurrentCourses;
        }

        /** @var $studentSessionRepository \Bethel\EntityBundle\Entity\StudentSessionRepository */
        $studentSessionRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:StudentSession');

        // the number of StudentSessions that have occurred (total for the semester)
        // $totalAttendance = $studentSessionRepository->getSemesterAttendees($sessionSemester);

        // This is the number of unique students who have attended sessions
        // $uniqueAttendance = $studentSessionRepository->getSemesterUniqueCount($sessionSemester);

        $totalAttendance = 0;
        $uniqueAttendance = 0;
        foreach($courses as $course) {
            $totalAttendance += $studentSessionRepository->getCourseAttendeeTotal($course,$sessionSemester);
            $uniqueAttendance += $studentSessionRepository->getCourseUniques($course,$sessionSemester);
        }

        $courseResults = array();
        foreach($courses as $course) {
            $courseResult = array();
            $courseResult['course'] = $course;

            // Total attendance
            // This is the total number of StudentSessions that have included this course
            $attendance = $studentSessionRepository->getCourseAttendeeTotal($course,$sessionSemester);

            $courseResult['attendance'] = $attendance;

            // Unique attendance
            // This is the number of unique students who have attended for a specific course
            $unique = $studentSessionRepository->getCourseUniques($course,$sessionSemester);
            $courseResult['unique'] = $unique;

            // Percentage attendance
            if($totalAttendance)
                $percentage = round($attendance/$totalAttendance * 100, 2);
            $courseResult['percentage'] = isset($percentage) ? $percentage : 'N/A';

            array_push($courseResults, $courseResult);
        }

        return array(
            'user' => $user,
            'courseResults' => $courseResults,
            'totalAttendance' => $totalAttendance,
            'uniqueAttendance' => $uniqueAttendance,
            'sessionSemester' => $sessionSemester,
        );
    }

    /**
     * @Route("/course/{id}", name="report_single_course")
     * @ParamConverter("course", class="BethelEntityBundle:Course")
     * @Template("BethelReportViewBundle:Default:course.html.twig")
     * @param \Bethel\EntityBundle\Entity\Course $course
     * @return array
     */
    public function singleCourseAction($course) {
        $em = $this->getEntityManager();
        $em->getFilters()->disable('softdeleteable');

        // Current course reports are good. We also want to add that you can list the course, and a list of all students who came
        // (including the number of times that they came) - this could be a link from the "unique" in parens (that is at the bottom of the Report/Course)
        // - this would bring up a list of the course with the student names & number of times attended

        /** @var $sessionRepository \Bethel\EntityBundle\Entity\SessionRepository */
        $sessionRepository = $em->getRepository('BethelEntityBundle:Session');

        /** @var $userRepository \Bethel\EntityBundle\Entity\UserRepository */
        $userRepository = $em->getRepository('BethelEntityBundle:User');

        /** @var $studentSessionRepository \Bethel\EntityBundle\Entity\StudentSessionRepository */
        $studentSessionRepository = $em->getRepository('BethelEntityBundle:StudentSession');

        /** @var $sessionSemester \Bethel\EntityBundle\Entity\Semester */
        $sessionSemester = $this->getSessionSemester();

        // first we get lab sessions held this semester where this course was offered
        $labs = $sessionRepository->getCourseSessionsHeld($course,$sessionSemester);

        $labAttendance = array();
        // total attendance (including repeat attendance)
        $total = 0;
        foreach($labs as $lab) {
            // then we get the total number of students who attended
            // for this course for each session
            $labAttend = array();
            $labAttend['lab'] = $lab;
            $labAttend['total'] = $sessionRepository->getCourseSessionAttendeeTotal($lab,$course,$sessionSemester);
            $total += $labAttend['total'];
            array_push($labAttendance,$labAttend);
        }

        /** @var \Bethel\EntityBundle\Entity\User $uniques */
        $uniques = $userRepository->getCourseAttendees($course,$sessionSemester);
        usort($uniques,array($this,"userLastNameSort"));
        $totalUnique = count($uniques);
        $uniqueAttendance = array();
        foreach($uniques as $unique) {
            $uniqueAttend = array();
            $uniqueAttend['student'] = $unique;
            // number of time the student attended a session for this course
            $uniqueAttend['attendance'] = $studentSessionRepository->getCourseAttendanceCount($unique,$course,$sessionSemester);
            $courseAttendance = $studentSessionRepository->getCourseAttendance($unique,$course,$sessionSemester);
            $totalMinutes = 0;
            /** @var \Bethel\EntityBundle\Entity\StudentSession $attendance */
            foreach($courseAttendance as $attendance) {
                $totalMinutes += $attendance->getMinutes();
            }
            $uniqueAttend['avgMinutes'] = round($totalMinutes/$uniqueAttend['attendance']);
            array_push($uniqueAttendance,$uniqueAttend);
        }

        $em->getFilters()->enable('softdeleteable');

        return array(
            'user' => $this->getUser(),
            'course' => $course,
            'labAttendance' => $labAttendance,
            'total' => $total,
            'totalUnique' => $totalUnique,
            'uniques' => $uniqueAttendance,
            'sessionSemester' => $sessionSemester
        );
    }

    /**
     * @Route("/export/course/session/{id}/session", name="export_course_session")
     * @ParamConverter("course", class="BethelEntityBundle:Course", options={"id" = "id"})
     * @Template()
     * @param Course $course
     * @return StreamedResponse
     */
    public function exportCourseSessionAction(Course $course)
    {
        // get the service container to pass to the closure
        $container = $this->container;
        $results = $this->singleCourseAction($course);
        $title = call_user_func(get_class($this) . '::formatExportTitle', 'SessionAttendance_' . $course->getCourseCode());

        $response = new StreamedResponse(function() use($container, $results, $title) {

            $handle = fopen('php://output', 'r+');
            
            fputcsv($handle, array($title, 'Exported on: ', date('m/d/Y')));
            fputcsv($handle, array());
            fputcsv($handle, array(
                "Date",
                "DOW",
                "Time",
                "Attendees"
            ));

            $totals = 0;
            foreach($results['labAttendance'] as $key => $labResult) {
                /** @var \Bethel\EntityBundle\Entity\Session $lab */
                $lab = $labResult['lab'];
                $totals += $labResult['total'];
                fputcsv($handle, array(
                    $lab->getDate()->format('n/d/Y'),
                    $lab->getDate()->format('D'),
                    $lab->getSchedStartTime()->format('g:ia') . '-' . $lab->getSchedEndTime()->format('g:ia'),
                    $labResult['total']
                ));
            }

            fputcsv($handle, array(
                "",
                "",
                "Total",
                $totals
            ));
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition','attachment; filename="' . $title . '.csv"');

        return $response;
    }

    /**
     * @Route("/export/course/{id}/student", name="export_course_student")
     * @ParamConverter("course", class="BethelEntityBundle:Course", options={"id" = "id"})
     * @Template()
     * @param Course $course
     * @return StreamedResponse
     */
    public function exportCourseStudentAction(Course $course)
    {
        // get the service container to pass to the closure
        $container = $this->container;
        $results = $this->singleCourseAction($course);
        $title = call_user_func(get_class($this) . '::formatExportTitle', 'SessionAttendance');

        $response = new StreamedResponse(function() use($container, $results, $title) {

            $handle = fopen('php://output', 'r+');
            
            fputcsv($handle, array($title, 'Exported on: ', date('m/d/Y')));
            fputcsv($handle, array());
            fputcsv($handle, array(
                "First Name",
                "Last Name",
                "Sessions",
                "Avg Time"
            ));

            $totals = 0;
            $averageMin = 0;
            $minuteTotal = 0;
            foreach($results['uniques'] as $unique) {
                fputcsv($handle, array(
                    $unique['student']->getFirstName(),
                    $unique['student']->getLastName(),
                    $unique['attendance'],
                    $unique['avgMinutes'] . ' min'
                ));
                $totals += $unique['attendance'];
                $averageMin += $unique['avgMinutes'];
            }

            

            if( sizeof($results['uniques']) != 0)
                $minuteTotal = $averageMin/sizeof($results['uniques']);

            fputcsv($handle, array(
                "",
                "Total:",
                $totals,
                $minuteTotal
                
            ));
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition','attachment; filename="' . $title . '.csv"');

        return $response;
    }


    /**
     * @Route("/student", name="report_student")
     * @Template("BethelReportViewBundle:Default:students.html.twig")
     */
    public function studentAction() {
        $em = $this->getEntityManager();
        $sessionSemester = $this->getSessionSemester();
        $em->getFilters()->disable('softdeleteable');
        /** @var $userRepository \Bethel\EntityBundle\Entity\UserRepository */
        $userRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:User');
        $studentSessionRepository = $em->getRepository('BethelEntityBundle:StudentSession');
        /** @var $courseRepository \Bethel\EntityBundle\Entity\CourseRepository */
        $courseRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:Course');
        $semesterStudents = $userRepository->getSemesterAttendees($sessionSemester);
        $students = array();
        foreach($semesterStudents as $student) {

            if($this->getUser()->hasRole('ROLE_PROFESSOR') && !$this->getUser()->hasRole('ROLE_ADMIN') && !$this->getUser()->hasRole('ROLE_VIEWER')) {
                $courses = $courseRepository->getStudentCourses($student, $sessionSemester);
                if( sizeof($courses) == 0 )
                    $display = false;
                else
                    $display = true;
            } else {
                $display = true;
            }

            if( $display ){
                $students[] = array(
                    'attendance' => $studentSessionRepository->getSemesterAttendanceTotal($student, $sessionSemester),
                    'user' => $student
                );
            }
        }
        $em->getFilters()->enable('softdeleteable');

        return array(
            'user' => $this->getUser(),
            'students' => $students
        );
    }

    /**
     * @Route("/export/student", name="export_students")
     * @Template()
     * @return StreamedResponse
     */
    public function exportStudentsAction()
    {
        // get the service container to pass to the closure
        $container = $this->container;
        $results = $this->studentAction();
        $students = array();
        foreach($results['students'] as $student) {
            array_push($students, array(
                'lastName' => $student['user']->getLastName(),
                'firstName' => $student['user']->getFirstName(),
                'email' => $student['user']->getEmail(),
                'attendance' => $student['attendance']
            ));
        }
        usort($students,array($this,"userLastNameArraySort"));

        $title = call_user_func(get_class($this) . '::formatExportTitle', 'StudentReport');

        $response = new StreamedResponse(function() use($container, $students, $title) {

            $handle = fopen('php://output', 'r+');

            
            fputcsv($handle, array($title, 'Exported on: ', date('m/d/Y')));
            fputcsv($handle, array());
            fputcsv($handle, array(
                "Last",
                "First",
                "Email",
                "Attendance"
            ));

            $totals = 0;
            foreach($students as $student) {
                fputcsv($handle, $student);
                $totals += $student['attendance'];
            }

            fputcsv($handle, array(
                "",
                "",
                "Total:",
                $totals
            ));
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition','attachment; filename="' . $title . '.csv"');

        return $response;
    }

    /**
     * @Route("/student/{id}", name="report_single_student")
     * @Template("BethelReportViewBundle:Default:student.html.twig")
     */
    public function singleStudentAction($id) {
        $em = $this->getEntityManager();
        $em->getFilters()->disable('softdeleteable');
        /** @var $userRepository \Bethel\EntityBundle\Entity\UserRepository */
        $userRepository = $em->getRepository('BethelEntityBundle:User');
        $student = $userRepository->findOneById($id);
        $professor = $this->getUser();
        $em->getFilters()->enable('softdeleteable');

        /** @var $courseRepository \Bethel\EntityBundle\Entity\CourseRepository */
        $courseRepository = $em->getRepository('BethelEntityBundle:Course');

        /** @var $studentSessionRepository \Bethel\EntityBundle\Entity\StudentSessionRepository */
        $studentSessionRepository = $em->getRepository('BethelEntityBundle:StudentSession');

        /** @var $sessionRepository \Bethel\EntityBundle\Entity\SessionRepository */
        $sessionRepository = $em->getRepository('BethelEntityBundle:Session');

        /** @var $sessionSemester \Bethel\EntityBundle\Entity\Semester */
        $sessionSemester = $this->getSessionSemester();

        $courses = $courseRepository->getStudentCourses($student, $sessionSemester);

        if($this->getUser()->hasRole('ROLE_PROFESSOR') && !$this->getUser()->hasRole('ROLE_ADMIN') && !$this->getUser()->hasRole('ROLE_VIEWER')) {
            $profView = true;
            foreach( $courses as $course){
                if( $this->getUser() == $course->getProfessors() )
                    $display = false;
                else
                    $display = true;
            }
        } else {
            $profView = false;
            $display = true;
        }

        $semesterAttendanceTotal = $studentSessionRepository->getSemesterAttendanceTotal($student,$sessionSemester);

        // Redirect to the student directory if this student hasn't attended any sessions
        if($semesterAttendanceTotal == 0) {
            $this->get('session')->getFlashBag()->add(
                'info',
                $student . ' has not attended any labs for ' . $sessionSemester
            );

            return array(
                'display'   =>  $display,       // if the user has no access to the specific student
                'user' => $this->getUser(),
                'student' => $student,
                'coursesAndAttendance' => array(),
                'semesterAttendance' => array(),
                'semesterAttendanceTotal' => $semesterAttendanceTotal,
                'sessionsWithCourses' => 0,
                'percentageAttended' => 0,
                'totalTimeSpent' => 0,
                'averageTimeSpent' => 0,
                'studentOtherSessions' => array(),
                'sessionSemester' => $sessionSemester,
                'profView'  =>  $profView,      // if the user is a prof, don't give ALL links
                'courses'    => $courses
            );
        }

        // if its a prof, only show their course records.
        if($this->getUser()->hasRole('ROLE_PROFESSOR') && !$this->getUser()->hasRole('ROLE_ADMIN') && !$this->getUser()->hasRole('ROLE_VIEWER')) {
            $professor = $this->getUser();
        } else {
            $professor = null;
        }

        $coursesAndAttendance = array();
        foreach($courses as $course) {
            $courseAttendance = $studentSessionRepository->getCourseAttendanceCount($student, $course, $sessionSemester);
            $coursesAndAttendance[] = array('course' => $course, 'attendance' => $courseAttendance);
        }

        // sessions held for courses student is enrolled in
        $sessionsWithCourses = $sessionRepository->getSessionsWithCourses($courses, $sessionSemester);

        if($sessionsWithCourses > 0) {
            $percentageAttended = round($semesterAttendanceTotal/$sessionsWithCourses * 100,0);
        } else {
            $percentageAttended = '???';
        }

        $semesterAttendance = $studentSessionRepository->getSemesterAttendance($student,$sessionSemester,'DESC',$professor);

        $timeSpent = 0;
        foreach($semesterAttendance as $attendance) {
            /** @var \Bethel\EntityBundle\Entity\StudentSession $attendance */
            $minutes = $attendance->getMinutes();
            if($minutes) {
                $timeSpent += $minutes;
            }
        }

        if(count($semesterAttendance)>0) {
            $averageTimeSpent = round($timeSpent/count($semesterAttendance),0);
        } else {
            $averageTimeSpent = '???';
        }


        // Convert total time to hours
        $totalTimeSpent = round($timeSpent/60,1);

        $studentOtherSessions = $studentSessionRepository->getStudentOtherSessions($student, $sessionSemester);

        return array(
            'display'   =>  $display,
            'user' => $this->getUser(),
            'student' => $student,
            'coursesAndAttendance' => $coursesAndAttendance,
            'semesterAttendance' => $semesterAttendance,
            'semesterAttendanceTotal' => $semesterAttendanceTotal,
            'sessionsWithCourses' => $sessionsWithCourses,
            'percentageAttended' => $percentageAttended,
            'totalTimeSpent' => $totalTimeSpent,
            'averageTimeSpent' => $averageTimeSpent,
            'studentOtherSessions' => $studentOtherSessions,
            'sessionSemester' => $sessionSemester,
            'profView'  =>  $profView,
            'courses'    => $courses
        );

    }

    /**
     * @Route("/semester", name="report_semester")
     * @Template("BethelReportViewBundle:Default:semester.html.twig")
     */
    public function semesterAction() {
        $sessionSemester = $this->getSessionSemester();

        /** @var $sessionRepository \Bethel\EntityBundle\Entity\SessionRepository */
        $sessionRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:Session');
        $sessions = $sessionRepository->getInProgressOrClosedSessions($sessionSemester);

        // We need to coerce our results array into an ArrayCollection object
        // so that we can use Doctrine criteria on it.
        $sessionsCollection = new ArrayCollection($sessions);

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('semester',$sessionSemester));

        $sessions = $sessionsCollection->matching($criteria);

        /** @var $studentSessionRepository \Bethel\EntityBundle\Entity\StudentSessionRepository */
        $studentSessionRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:StudentSession');
        $totalAttendance = $studentSessionRepository->getSemesterAttendees($sessionSemester);
        $uniqueAttendance = $studentSessionRepository->getSemesterUniqueCount($sessionSemester);

        if(count($sessions)) {
            $averageAttendance = round($totalAttendance / count($sessions), 2);
        } else {
            $averageAttendance = 0;
        }

        $totalTime = 0;
        foreach($sessions as $session) {
            /** @var \Bethel\EntityBundle\Entity\StudentSession $studentSession */
            $studentSessions = $studentSessionRepository->findBy(array('session'=>$session));
            foreach($studentSessions as $studentSession) {
                $totalTime += $studentSession->getMinutes();
            }
        }
        // Convert total time to hours
        $totalTime = $totalTime/60;

        if($uniqueAttendance) {
            $averageTime = round($totalTime / $uniqueAttendance, 2);
        } else {
            $averageTime = 0;
        }

        /** @var $scheduleRepository \Bethel\EntityBundle\Entity\ScheduleRepository */
        $scheduleRepository = $this->getEntityManager()->getRepository('BethelEntityBundle:Schedule');
        // We must disable the SoftDeleteable filter so we can grab Sessions
        // for deleted schedules
        $this->getEntityManager()->getFilters()->disable('softdeleteable');
        $schedules = $scheduleRepository->findBy(array('term'=>$sessionSemester->getTerm()));
        usort($schedules,array($this,"scheduleDowSort"));
        // Re-enable the SoftDeleteable filter so we don't get deleted Sessions
        $this->getEntityManager()->getFilters()->enable('softdeleteable');
        $weekDays = array('Sun','Mon','Tue','Wed','Thu','Fri','Sat');

        $scheduleResults = array();
        $sessionTotals = 0;
        foreach($schedules as $schedule) {
            $scheduleSessions = $sessionRepository->findBy(array('schedule'=>$schedule,'semester'=>$sessionSemester));

            // If a schedule has not had any sessions held in the given semester
            // we can just skip over it
            if(!empty($scheduleSessions)) {
                // We need to coerce our results array into an ArrayCollection object
                // so that we can use Doctrine criteria on it.
                $scheduleSessionsCollection = new ArrayCollection($scheduleSessions);

                // startTime IS NOT NULL
                $criteria = Criteria::create()
                    ->where(Criteria::expr()->neq('startTime',null));

                $scheduleSessions = $scheduleSessionsCollection->matching($criteria);

                $scheduleResult = array();
                $scheduleResult['schedule'] = $schedule;

                $scheduleResult['sessions'] = $scheduleSessions;
                $scheduleResult['attendance'] = 0;
                foreach($scheduleSessions as $scheduleSession) {
                    $scheduleResult['attendance'] += $sessionRepository->getSessionAttendeeTotal($scheduleSession);
                }
                $scheduleResult['percentage'] = round($scheduleResult['attendance']/$totalAttendance, 2)*100;
                array_push($scheduleResults,$scheduleResult);
                $sessionTotals += sizeof($scheduleSessions);
            }
        }

        $unscheduledSessions = $sessionRepository->findBy(array('schedule'=>null,'semester'=>$sessionSemester));

        $unscheduledTotal = 0;
        foreach( $unscheduledSessions as $unscheduledSession){
            $unscheduledTotal += sizeof($unscheduledSession->getStudentSessions()) + $unscheduledSession->getAnonStudents();
        }

        return array(
            'user' => $this->getUser(),
            'semester' => $sessionSemester,
            'sessions' => $sessions,
            'totalAttendance' => $totalAttendance,
            'sessionTotals' => $sessionTotals,
            'unscheduledTotal' => $unscheduledTotal,
            'uniqueAttendance' => $uniqueAttendance,
            'averageAttendance' => $averageAttendance,
            'avgVisitsPerStudent' => round($totalAttendance/$uniqueAttendance,2),
            'averageTime' => $averageTime,
            'weekDays' => $weekDays,
            'scheduleResults' => $scheduleResults,
            'unscheduledSessions' => $unscheduledSessions
        );
    }

    /**
     * @Route("/export/semester", name="export_semester")
     * @Template()
     */
    public function exportSemesterAction()
    {
        // get the service container to pass to the closure
        $container = $this->container;
        $results = $this->semesterAction();
        /** @var \Bethel\EntityBundle\Entity\Semester $semester */
        $semester = $results['semester'];
        $session = $this->getSessionSemester();
        $title = call_user_func(get_class($this) . '::formatExportTitle', 'TermReport');

        $response = new StreamedResponse(function() use($container, $results, $title) {

            $handle = fopen('php://output', 'r+');
            
            fputcsv($handle, array($title, 'Exported on: ', date('m/d/Y')));
            fputcsv($handle, array());
            fputcsv($handle, array('Schedule Statistics for Closed Sessions'));
            fputcsv($handle, array(
                "Schedule Name",
                "DOW",
                "Start Time",
                "Stop Time",
                "Number of Sessions",
                "Attendance",
                "Percentage"
            ));

            $totalNumSessions = 0;
            $totalAttendance = 0;
            foreach($results['scheduleResults'] as $key => $scheduleResult) {
                /** @var \Bethel\EntityBundle\Entity\Schedule $schedule */
                $schedule = $scheduleResult['schedule'];
                fputcsv($handle, array(
                    $schedule->getName(),
                    $results['weekDays'][$schedule->getDayOfWeek()],
                    $schedule->getStartTime()->format('g:ia'),
                    $schedule->getEndTime()->format('g:ia'),
                    count($scheduleResult['sessions']),
                    $scheduleResult['attendance'],
                    $scheduleResult['percentage'] . '%'
                ));
                $totalNumSessions += count($scheduleResult['sessions']);
                $totalAttendance += $scheduleResult['attendance'];
            }

            fputcsv($handle, array(
                "",
                "",
                "",
                "Total:",
                $totalNumSessions,
                $totalAttendance,
                "100%"
            ));

            // unscheduled sessions
            fputcsv($handle, array());  
            fputcsv($handle, array('Unscheduled Sessions'));
            foreach($results['unscheduledSessions'] as $key => $scheduleResult) {
                fputcsv($handle, array(
                    $scheduleResult->getDate()->format('m/d/y'),
                    $scheduleResult->getStartTime()->format('g:ia'),
                    $scheduleResult->getEndTime()->format('g:ia'),
                    sizeof($scheduleResult->getStudentSessions())
                ));
            }
            fputcsv($handle, array('', '', 'Total', $results['unscheduledTotal']));

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition','attachment; filename="' . $title . '.csv"');

        return $response;
    }

    /**
     * The view semester form will be submitted to this endpoint
     *
     * @Route("/viewsemester/", name="report_view_semester")
     */
    public function adminSessionSemesterAction() {
        $em = $this->getEntityManager();
        $session = $this->get('session');

        /** @var $semesterHandler \Bethel\FrontBundle\Services\SessionSemester */
        $semesterHandler = $this->get('session_semester')->create($session);

        $form = $this->get('request')->request->get('form');
        if(!is_null($form) && $form['semester']) {
            /** @var $semester \Bethel\EntityBundle\Entity\Semester */
            $semester = $em->getRepository('BethelEntityBundle:Semester')->findOneById($form['semester']);
            $semesterHandler->setSessionSemester($semester);

            $this->get('session')->getFlashBag()->add(
                'success',
                'Semester has been set to ' . $semester
            );

            return $this->redirect($this->generateUrl($form['referringUrl'], $routeParameters = json_decode($form['routeParameters'], true)));
        }

        $this->get('session')->getFlashBag()->add(
            'warning',
            'Semester was not changed.'
        );

        if($form['referringUrl']) {
            return $this->redirect($this->generateUrl($form['referringUrl'], $routeParameters = json_decode($form['routeParameters'], true)));
        } else {
            return $this->redirect($this->generateUrl('report'));
        }
    }


    public function viewSemesterSwitchAction($currentRoute, $routeParameters) {
        $em = $this->getEntityManager();
        /** @var $semesterHandler \Bethel\FrontBundle\Services\SessionSemester */

        $session = $this->get('session');
        /** @var $semesterHandler \Bethel\FrontBundle\Services\SessionSemester */
        $semesterHandler = $this->get('session_semester')->create($session);

        $sessionSemester = $this->getSessionSemester();

        $semesters = $em->getRepository('BethelEntityBundle:Semester')->findAll();
        $semesters = array_reverse($semesters);

        $sessionForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('report_view_semester'))
            ->add('semester', 'entity', array(
                'label' => false,
                'class' => 'BethelEntityBundle:Semester',
                'choices' => $semesters,
                'multiple'  => false,
                'expanded' => false,
                'attr' => array(
                    'style' => 'height:33px;font-size:0.8rem'
                )
            ))
            ->add('referringUrl', 'hidden', array(
                'data' => $currentRoute
            ))
            ->add('routeParameters', 'hidden', array(
                'data' => $routeParameters
            ))
            ->add('save','submit', array(
                'attr' => array('class'=>'button tiny success radius right'),
                'label' => 'Set'
            ))
            ->getForm()
            ->createView();

//        if (isset($_POST['form']['semester'])) {
//            /** @var $semester \Bethel\EntityBundle\Entity\Semester */
//            $semester = $em->getRepository('BethelEntityBundle:Semester')->findOneById($_POST['form']['semester']);
//            $semesterHandler->setSessionSemester($semester);
//
//            return $this->redirect($this->generateUrl($currentRoute));
//        }

        return $this->render(
            'BethelReportViewBundle:Default:semester_switch.html.twig',
            array(
                'sessionSemester' => $sessionSemester,
                'sessionForm' => $sessionForm
            )
        );
    }

    public function formatExportTitle($title){
        $selectedSemester = $this->getSessionSemester();
        $labs = explode(' ', $this->container->getParameter('app.title'));
        $shortLab = '';
        foreach( $labs as $lab){
            $shortLab .= $lab[0];
        }
        $term = $selectedSemester->getTerm();
        if( $term == 'Spring' || $term == 'Summer' || $term == 'Interim')
            $term = strtoupper(substr($term, 0, 2));
        else
            $term = $term[0];

        $fullTitle = $term . $selectedSemester->getYear() . '_' . $shortLab . '_' . $title;

        return $fullTitle;
    }

}
