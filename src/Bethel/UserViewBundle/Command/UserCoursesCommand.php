<?php
namespace Bethel\UserViewBundle\Command;

use Bethel\EntityBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UserCoursesCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('bethel:user:courses')
            ->setDescription('Pull in all courses for the given users')
            ->addArgument('username', InputArgument::OPTIONAL, 'The username of the user whose courses we want to fetch.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /** @var  \Doctrine\ORM\EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $userRepository = $em->getRepository('BethelEntityBundle:User');

        $username = $input->getArgument('username');
        if($username && is_string($username)) {
            $user = $userRepository->findOneBy(array('username' => $username));
            if( $user ) {
                $message = $this->addCoursesForUser($user);
                $output->writeln($message);
            }
        } else {
            $output->writeln('<error>You must select a user.</error>');
        }
    }

    /**
     * @param User $user
     * @return string
     */
    private function addCoursesForUser(User $user) {
        /** @var  \Doctrine\ORM\EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $wsapi = $this->getContainer()->get('wsapi');
        $populateCoursesService = $this->getContainer()->get('bethel.populate_courses');

        $apiCourses = $wsapi->getCourses($user->getUsername());
        $courses = $populateCoursesService->populate($apiCourses);

        $message = $user->getFirstName() . ' ' . $user->getLastName() . ' Courses: ';
        /** @var \Bethel\EntityBundle\Entity\Course $course */
        foreach($courses as $course) {
            $user->addCourse($course);
            $message .= $course->__toString() . ', ';
        }
        $em->persist($user);
        $em->flush();

        return $message;
    }
}