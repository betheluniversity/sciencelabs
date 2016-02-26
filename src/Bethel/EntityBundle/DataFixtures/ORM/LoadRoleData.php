<?php
namespace Bethel\EntityBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Bethel\EntityBundle\Entity\Role;

class LoadRoleData extends AbstractFixture implements OrderedFixtureInterface {
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager) {

        $studentRole = new Role();
        $studentRole->setName('Student');
        $studentRole->setRole('ROLE_STUDENT');
        $studentRole->setSort(1);

        $manager->persist($studentRole);

        $professorRole = new Role();
        $professorRole->setName('Professor');
        $professorRole->setRole('ROLE_PROFESSOR');
        $professorRole->setSort(2);

        $manager->persist($professorRole);

        $tutorRole = new Role();
        $tutorRole->setName('Tutor');
        $tutorRole->setRole('ROLE_TUTOR');
        $tutorRole->setSort(3);

        $manager->persist($tutorRole);

        $leadRole = new Role();
        $leadRole->setName('Lead Tutor');
        $leadRole->setRole('ROLE_LEAD_TUTOR');
        $leadRole->setSort(4);

        $manager->persist($leadRole);

        $viewerRole = new Role();
        $viewerRole->setName('Viewer');
        $viewerRole->setRole('ROLE_VIEWER');
        $viewerRole->setSort(5);

        $manager->persist($viewerRole);

        $adminRole = new Role();
        $adminRole->setName('Administrator');
        $adminRole->setRole('ROLE_ADMIN');
        $adminRole->setSort(6);

        $manager->persist($adminRole);

        $apiRole = new Role();
        $apiRole->setName('API User');
        $apiRole->setRole('ROLE_API_USER');

        $manager->persist($apiRole);

        $manager->flush();
    }

    public function getOrder() {
        return 1;
    }
}

?>