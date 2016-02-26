<?php
namespace Bethel\CourseViewBundle\Command;

use Bethel\EntityBundle\Entity\User;
use Bethel\EntityBundle\Exception\SemesterNotFoundException;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCourseCodeCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('bethel:coursecode:create')
            ->setDescription('Create a coursecode. Optionally populate course(s) given that course code')
            ->addArgument('subject', InputArgument::REQUIRED, 'The subject code for the course e.g. COS.')
            ->addArgument('number', InputArgument::REQUIRED, 'The course number for the course e.g. 101.')
            ->addOption('populate-courses', null, InputOption::VALUE_NONE, 'Populate courses corresponding to this course code for the current semester.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /** @var  \Doctrine\ORM\EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $ccValidator = $this->getContainer()->get('bethel.validate_course_code');

        $subject = $input->getArgument('subject');
        $cNumber = $input->getArgument('number');

        $apiCourseCode = $ccValidator->validate($subject, $cNumber);
        if($apiCourseCode) {
            $output->writeln('<info>Valid Course Code</info>');
        } else {
            $output->writeln('<error>Invalid Course Code</error>');
            return null;
        }

        $ccUpdater = $this->getContainer()->get('bethel.create_or_update_course_code');

        $createCourseCode = $ccUpdater->createOrUpdate($apiCourseCode);

        /** @var \Bethel\EntityBundle\Entity\CourseCode $courseCode */
        $courseCode = $createCourseCode['courseCode'];
        $createdOrEdited = $createCourseCode['created'] ? 'created' : 'edited';

        $output->writeln(array(
            'Course Code ' . $createdOrEdited,
            $courseCode->__toString()
        ));

        // Populate courses
        if($input->getOption('populate-courses')) {
            $coursePopulator = $this->getContainer()->get('bethel.populate_course_codes');
            try {
                $coursePopulator->populate(
                    new ArrayCollection(array($courseCode))
                );
            } catch (SemesterNotFoundException $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                return null;
            }

        }

        return null;
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