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

}
