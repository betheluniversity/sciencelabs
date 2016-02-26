<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(

            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Sensio\Bundle\BuzzBundle\SensioBuzzBundle(),
            new Bethel\EntityBundle\BethelEntityBundle(),
            new Bethel\UserBundle\BethelUserBundle(),
            new Bethel\SessionBundle\BethelSessionBundle(),
            new Bethel\ScheduleBundle\BethelScheduleBundle(),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new BeSimple\SsoAuthBundle\BeSimpleSsoAuthBundle(),
            new Bethel\FrontBundle\BethelFrontBundle(),
            new Bethel\SessionViewBundle\BethelSessionViewBundle(),
            new Bethel\ScheduleViewBundle\BethelScheduleViewBundle(),
            new Bethel\UserViewBundle\BethelUserViewBundle(),
            new Bethel\ReportViewBundle\BethelReportViewBundle(),
            new Bethel\CourseViewBundle\BethelCourseViewBundle(),
            new Bethel\SemesterApiBundle\BethelSemesterApiBundle(),
            new Bethel\TutorBundle\BethelTutorBundle(),
            new Bethel\WsapiBundle\BethelWsapiBundle(),
            new Bethel\EmailBundle\BethelEmailBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
