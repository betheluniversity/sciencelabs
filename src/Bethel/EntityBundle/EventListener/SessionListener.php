<?php

namespace Bethel\EntityBundle\EventListener;

use Bethel\EntityBundle\Entity\Session;
use Doctrine\ORM\Event\OnFlushEventArgs;

class SessionListener {
    public function onFlush(OnFlushEventArgs $args) {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $entities = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates()
        );

        foreach($entities as $entity) {
            if(!($entity instanceof Session)) {
                continue;
            }

            // Set the Semester
            $semesterRepository = $em->getRepository('BethelEntityBundle:Semester');
            // This was causing issues with editing/creating new sessions.
            // $entity->setSemester($semesterRepository->findOneBy(array('active' => true)));
            $em->persist($entity);

            $md = $em->getClassMetadata('Bethel\EntityBundle\Entity\Session');

            $uow->recomputeSingleEntityChangeSet($md, $entity);
        }
    }
}