<?php

namespace Bethel\UserViewBundle\Command;

use Bethel\EntityBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AllUsersCoursesCommand extends ContainerAwareCommand {

    protected function configure() {
        $this
            ->setName('bethel:users:courses')
            ->setDescription('Pull in all courses for all active students')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /** @var  \Doctrine\ORM\EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $userRepository = $em->getRepository('BethelEntityBundle:User');

        $roleRepository = $em->getRepository('BethelEntityBundle:Role');
        $studentRole = $roleRepository->findOneBy(array('role'=>'ROLE_STUDENT'));
        $students = $userRepository->getUsersByRole($studentRole->getName());

        /** @var \Bethel\UserViewBundle\Command\UserCoursesCommand $userCoursesCommand */
        $userCoursesCommand = $this->getApplication()->find('bethel:user:courses');

        /** @var \Bethel\EntityBundle\Entity\User $student */
        foreach($students as $student) {
            $arguments = array(
                'username' => $student->getUsername()
            );
            $input = new ArrayInput($arguments);
            $returnCode = $userCoursesCommand->run($input,$output);
            if($returnCode == 0) {
                $output->writeln('User courses fetched.');
            }
        }
    }


}