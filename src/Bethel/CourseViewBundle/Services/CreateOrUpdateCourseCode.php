<?php
/**
 * Created by PhpStorm.
 * User: pms63443
 * Date: 4/21/15
 * Time: 1:40 PM
 */

namespace Bethel\CourseViewBundle\Services;


use Bethel\EntityBundle\Entity\CourseCode;
use Doctrine\ORM\EntityManager;

class CreateOrUpdateCourseCode {

    private $em;

    public function __construct(
        EntityManager $em
    ) {
        $this->em = $em;
    }

    /**
     * @param array $apiCourseCode
     * @return array
     */
    public function createOrUpdate($apiCourseCode) {
        // cNumber, subject, title
        $courseCodeRepository = $this->em->getRepository('BethelEntityBundle:CourseCode');

        $courseCode = $courseCodeRepository->findOneBy(array(
            'courseNum' => $apiCourseCode['cNumber'],
            'dept' => $apiCourseCode['subject']
        ));

        $created = false;
        if(!$courseCode) {
            $courseCode = new CourseCode();
            $created = true;
        }

        $courseCode
            ->setCourseName($apiCourseCode['title'])
            ->setCourseNum($apiCourseCode['cNumber'])
            ->setDept($apiCourseCode['subject'])
            ->setUnderived($apiCourseCode['subject'] . $apiCourseCode['cNumber']);

        $this->em->persist($courseCode);
        $this->em->flush();

        return array(
            'courseCode' => $courseCode,
            'created' => $created
        );
    }
}