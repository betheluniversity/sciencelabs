<?php
namespace Bethel\SessionViewBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CloseSessionsCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('bethel:sessions:close')
            ->setDescription('Close all currently open sessions.')
            ->addArgument('message', InputArgument::OPTIONAL, 'The message which will be stored in the session comments.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /** @var  \Doctrine\ORM\EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        /** @var \Bethel\SessionViewBundle\Services\SessionClose $sessionCloser */
        $sessionCloser = $this->getContainer()->get('bethel.session_close');
        $date = new \DateTime('now');
        $message = $input->getArgument('message');
        if($message && is_string($message)) {
            $comment = 'Closed by the system on ' . $date->format('n/d/Y') . ' with message ' . $message;
        } else {
            $comment = 'Closed by the system on ' . $date->format('n/d/Y');
        }

        /** @var \Bethel\EntityBundle\Entity\SessionRepository $sessionRepository */
        $sessionRepository = $em->getRepository('BethelEntityBundle:Session');
        $studentSessionRepository = $em->getRepository('BethelEntityBundle:studentSession');
        $openSessions = $sessionRepository->findBy(
            array(
                'open' => true
            )
        );

        foreach($openSessions as $session) {
            foreach( array_merge($session->getStudentSessions(),$session->getTutorSessions()) as $studentOrTutorSession ){
                $studentOrTutorSession->setTimeOut(new \DateTime("now"));
                $em->persist($studentOrTutorSession);
                $em->flush();
            }
            $sessionCloser->close($session,$comment);
        }
    }
}