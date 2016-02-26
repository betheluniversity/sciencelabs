<?php
/**
 * Created by PhpStorm.
 * User: pms63443
 * Date: 2/5/15
 * Time: 11:16 AM
 */

namespace Bethel\EntityBundle\Services;


use Bethel\EntityBundle\Entity\Semester;
use Bethel\EntityBundle\Exception\SemesterNotFoundException;
use Doctrine\DBAL\Exception\DatabaseObjectNotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;

class PopulateSemesterService {

    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em) {
        $this->em = $em;
    }

    /**
     * @param string $apiSemester
     * @return Semester
     */
    public function populate($apiSemester) {
        // look up the semester in the database
        // brittle string manipulation
        $semesterArray = explode(" ", $apiSemester);
        $semesterTerm = $semesterArray[0];
        $semesterYear = intval($semesterArray[1]);
        /** @var $semesterRepository EntityRepository */
        $semesterRepository = $this->em->getRepository('BethelEntityBundle:Semester');
        /** @var $semester Semester */
        $semester = $semesterRepository->findOneBy(
            array(
                'term' => $semesterTerm,
                'year' => $semesterYear
            )
        );

        // if the semester doesn't exist, we need to inform the user
        if(!$semester) {
            throw new SemesterNotFoundException('The term ' . $semesterTerm . ' ' . $semesterYear . ' was not found in the system, please create it and try again');
        }

        return $semester;
    }
}