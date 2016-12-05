<?php

namespace Bethel\EntityBundle\Form;

use Bethel\EntityBundle\Entity\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityManager;

class StudentAddAttendanceType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $options['em'];
        $roleRepository = $em->getRepository('BethelEntityBundle:Role');
        $builder
            ->add('student', 'entity', array(
                    'label' => 'Registered Students',
                    'class' => 'BethelEntityBundle:User',
                    'multiple' => false,
                    'expanded' => false,
                    'required' => true,
                    'empty_value' => '',
                    'query_builder' => function(UserRepository $repository) use ($roleRepository) {
                        return $repository->queryAlphaByRoleQB(
                            $roleRepository->findBy(array(
                                'role' => 'ROLE_STUDENT'
                            ))
                        );
                    },
                    'mapped' => true,
                    'attr' => array('class'=>'chosen-select','data-placeholder'=>'Choose student ...', 'style' => 'height:400px')
                )
            )
            ->add('save','submit', array(
                'label' => 'Submit',
                'attr' => array('class'=>'button success radius right', 'style' => 'margin-top:20px')
            ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
            ->setDefaults(array(
                'data_class' => 'Bethel\EntityBundle\Entity\StudentSession'
            ))
            ->setRequired(array(
                'em'
            ))
            ->setAllowedTypes(array(
                'em' => 'Doctrine\ORM\EntityManager'
            ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bethel_entitybundle_studentaddattendance';
    }
}
