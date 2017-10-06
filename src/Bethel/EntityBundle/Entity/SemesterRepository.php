<?php

namespace Bethel\EntityBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

/**
 * SemesterRepository
 */
class SemesterRepository extends EntityRepository {


    /**
     * Queries for sessions in a given semester and returns the associated schedules
     *
     * @param \DateTime $date
     * @return Semester
     * @throws NoResultException
     */
    public function getSemesterByMonth(\DateTime $date) {
        // the -1 is a fix to make work with semesters that start on Jan. 1
        $yearStart = \DateTime::createFromFormat('n/j/Y', '12/31/' . $date->format('Y')-1);
        $yearEnd = \DateTime::createFromFormat('n/j/Y', '12/31/' . $date->format('Y'));
        $qb = $this->createQueryBuilder('s')
            ->where('s.startDate >= :yearStart')
            ->andWhere('s.endDate <= :yearEnd')
            ->setParameter('yearStart', $yearStart)
            ->setParameter('yearEnd',$yearEnd);

        $yearSemesters = $qb->getQuery()->getResult();

        /** @var Semester $yearSemester */
        foreach($yearSemesters as $yearSemester) {
            $startMonth = $yearSemester->getStartDate()->format('n');
            $endMonth = $yearSemester->getEndDate()->format('n');
            $monthRange = range($startMonth,$endMonth);
            foreach($monthRange as $month) {
                if($date->format('n') == $month) {
                    return $yearSemester;
                }
            }
        }

        throw new NoResultException();
    }

    /**
     * Queries for a semester based on date
     *
     * @param $year
     * @param $month
     * @return Array(Semester)
     * @throws NoResultException
     */
    public function getSemesterByMonthAndYear($year, $month) {
        $date = new \DateTime("now");
        $date->setDate($year,$month,1);
        $date->setTime(0,0,0);

        $firstDay = clone $date;
        $firstDay->modify("first day of this month");

        $middleDay = clone $date;
        $middleDay->setDate($year,$month,15);

        $lastDay = clone $firstDay;
        $lastDay->modify("last day of this month");

        $qb = $this->createQueryBuilder('s')
            ->where(':firstDay BETWEEN s.startDate AND s.endDate')
            ->orWhere(':middleDay BETWEEN s.startDate AND s.endDate')
            ->orWhere(':lastDay BETWEEN s.startDate AND s.endDate')
            ->setParameter('firstDay', $firstDay->format('Y-m-d'))
            ->setParameter('middleDay', $middleDay->format('Y-m-d'))
            ->setParameter('lastDay', $lastDay->format('Y-m-d'));

        // returns an array of semesters
        return $qb->getQuery()->getResult();
    }
}
