<?php
namespace Bethel\CourseViewBundle\Command;

use Bethel\EntityBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateProfessorsCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('bethel:migrate:professors')
            ->setDescription('Migrate professors from a one to many relationship with courses to a many to many relationship.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /** @var  \Doctrine\ORM\EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        $courseRepository = $em->getRepository('BethelEntityBundle:Course');
        $courses = $courseRepository->findAll();

        foreach($courses as $course) {
            $professor = $course->getProfessor();
            $course->addProfessor($professor);
            $em->persist($course);
            $output->writeln('<info>' . $professor->__toString() . ' added to ' . $course->__toString() . '</info>');
        }
        $em->flush();

        return null;
    }
}