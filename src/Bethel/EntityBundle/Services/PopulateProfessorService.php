<?php

namespace Bethel\EntityBundle\Services;

use Bethel\EntityBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class PopulateProfessorService
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param string $apiProfessorUsername
     * @param string $apiProfessorName
     * @return User
     */
    public function populate($apiProfessorUsername, $apiProfessorName) {

        // look up the professor in the database
        /** @var $userRepository \Bethel\EntityBundle\Entity\UserRepository */
        $userRepository = $this->em->getRepository('BethelEntityBundle:User');
        $professor = $userRepository->findOneBy(
            array(
                'username' => $apiProfessorUsername
            )
        );

        // if the professor isn't in the database, we need to create a new user
        //  and persist it
        if (!$professor) {
            $professor = new User();

            // get the professor role
            $roleRepository = $this->em->getRepository('BethelEntityBundle:Role');
            $professorRole = $roleRepository->findOneBy(
                array(
                    'name' => 'Professor'
                )
            );

            // Names are in the following format: First M. Last
            $profNameArray = explode(" ", $apiProfessorName);

            $professor->setFirstName($profNameArray[0]);
            $profLastName = '';
            for ($i = 2; $i < count($profNameArray); $i++) {
                // Handle last names containing spaces.
                $profLastName += $profNameArray[$i];
            }
            $professor->setLastName($profNameArray[2]);
            $professor->setUsername($apiProfessorUsername);
            $professor->addRole($professorRole);
            $professor->setEmail($apiProfessorUsername . '@bethel.edu');

            $this->em->persist($professor);
            $this->em->flush();
        }

        return $professor;
    }
}