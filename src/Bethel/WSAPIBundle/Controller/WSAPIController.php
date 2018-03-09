<?php

namespace Bethel\WSAPIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Buzz\Browser;


class WSAPIController extends Controller
{
    private $base;
    private $secret;
    private $accountId;
    private $timestamp;
    private $get_username_url;
    private $get_course_code_and_name_url;
    private $get_courses_by_course_code_url;
    private $get_courses_url;
    private $get_names_url;

    public function __construct($base, $secret, $accountId, $get_username_url, $get_course_code_and_name_url, $get_courses_by_course_code_url, $get_courses_url, $get_names_url) {
        $this->base = $base;
        $this->secret = $secret;
        $this->accountId = $accountId;
        $this->timestamp = time();

        $this->get_username_url = $get_username_url;
        $this->get_course_code_and_name_url = $get_course_code_and_name_url;
        $this->get_courses_by_course_code_url = $get_courses_by_course_code_url;
        $this->get_courses_url = $get_courses_url;
        $this->get_names_url = $get_names_url;
    }

    public function getUsername($first, $last=null) {
        $route = $this->get_username_url;
        $response = $this->insertRouteParams($route, $first, $last);
        return $response;
    }

    public function getCourseCodeAndName($courseSubject, $courseNumber) {
        $route = $this->get_course_code_and_name_url;
        $response = $this->insertRouteParams($route, $courseSubject, $courseNumber);
        return $response;
    }

    public function getCoursesByCourseCode($courseSubject, $courseNumber, $interval=null) {
        $route = $this->get_courses_by_course_code_url;
        if( $interval )
            $response = $this->insertRouteParams($route, $courseSubject, $courseNumber, $interval);
        else
            $response = $this->insertRouteParams($route, $courseSubject, $courseNumber);
        return $response;
    }

    public function getCourses($username) {
        $route = $this->get_courses_url;
        $response = $this->insertRouteParams($route, $username);
        return $response;
    }

    public function getNames($username) {
        $route = $this->get_names_url;
        $response = $this->insertRouteParams($route, $username);
        return $response;
    }

    public function insertRouteParams($route, $param1=null, $param2=null, $param3=null) {
        if( $param1 ) {
            $pos = strpos($route, '<PARAM>');
            $route = substr_replace($route, $param1, $pos, strlen('<PARAM>'));
        }
        if( $param2 ) {
            $pos = strpos($route, '<PARAM>');
            $route = substr_replace($route, $param2, $pos, strlen('<PARAM>'));
        }
        if( $param3 ){
            $pos = strpos($route, '<PARAM>');
            $route = substr_replace($route, $param3, $pos, strlen('<PARAM>'));
        }

        $response = $this->send($route);
        return $response;
    }

    /**
     * @param $request
     * @param int $try
     * @return mixed
     * @throws \Exception
     */
    private function send($request, $try = 1) {
        try {
            $path_and_query = $request . "?TIMESTAMP=".$this->timestamp."&"."ACCOUNT_ID=".$this->accountId;

            $signature = hash_hmac('sha1', $path_and_query, $this->secret);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->base . $path_and_query);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Auth-Signature: $signature"));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $response = curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            // We'll try to send the request 3 times. If it still fails, give up
            if ($try > 2) {
                throw $e;
            }

            return $this->send($request, $try + 1);
        }
        $response = json_decode($response, true);

        return $response;
    }
}
