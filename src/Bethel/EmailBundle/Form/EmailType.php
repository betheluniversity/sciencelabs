<?php

namespace Bethel\EmailBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmailType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \Bethel\EntityBundle\Entity\Session $session */
        $session = $builder->getData();
        $builder
            ->add('role', 'entity', array(
                    'label' => 'Recipients',
                    'class' => 'BethelEntityBundle:Role',
                    'multiple' => true,
                    'expanded' => false,
                    'required' => false,
                    'query_builder' => function($repository) {
                        /** @var \Doctrine\ORM\EntityRepository $repository */
                        return $repository->createQueryBuilder('r')
                            ->where('r.role != :apirole')
                            ->setParameter('apirole', 'ROLE_API_USER');
                    },
                    'property' => 'name',
                    'translation_domain' => 'BethelEntityBundle',
                    'attr' => array('class'=>'chosen-select','data-placeholder'=>'Choose recipients ...')
                )
            )
            ->add('cc', 'entity', array(
                    'label' => 'CC',
                    'class' => 'BethelEntityBundle:User',
                    'multiple' => true,
                    'expanded' => false,
                    'required' => false,
                    'attr' => array('class'=>'chosen-select','data-placeholder'=>'Choose CC recipients ...')
                )
            )
            ->add('bcc', 'entity', array(
                    'label' => 'BCC',
                    'class' => 'BethelEntityBundle:User',
                    'multiple' => true,
                    'expanded' => false,
                    'required' => false,
                    'attr' => array('class'=>'chosen-select','data-placeholder'=>'Choose BCC recipients ...')
                )
            )
            ->add('subject', 'text', array(
                'label' => 'Subject',
                'required' => false,
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('message', 'textarea', array(
                'label' => 'Message',
                'attr' => array('rows'=>'15'),
                'required' => false,
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('save','submit', array(
                'label' => 'Send',
                'attr' => array('class'=>'button success radius right')
            ));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array());
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bethel_emailbundle_email';
    }
}
