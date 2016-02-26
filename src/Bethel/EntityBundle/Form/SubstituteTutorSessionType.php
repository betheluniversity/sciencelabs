<?php

namespace Bethel\EntityBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SubstituteTutorSessionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \Bethel\EntityBundle\Entity\User $user */
        $user = $options['user'];
        $builder
            ->add('schedTimeIn', 'time', array(
                'label' => 'Scheduled Time In',
                'widget' => 'single_text',
                'read_only' => true
            ))
            ->add('schedTimeOut', 'time', array(
                'label' => 'Scheduled Time Out',
                'widget' => 'single_text',
                'read_only' => true
            ))
            ->add('tutor', 'entity', array(
                    'label' => 'Tutor',
                    'class' => 'BethelEntityBundle:User',
                    'query_builder' => function(EntityRepository $repository) use ($user) {
                        return $repository->createQueryBuilder('u')
                            ->where('u = :user')
                            ->setParameter('user', $user);
                    },
                )
            )
            ->add('save','submit', array(
                'attr' => array('class'=>'button success radius right')
            ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Bethel\EntityBundle\Entity\TutorSession'
        ))
        ->setRequired(array(
            'user',
        ))
        ->setAllowedTypes(array(
            'user' => 'Bethel\EntityBundle\Entity\User'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bethel_entitybundle_substitutetutorsession';
    }
}
