<?php

namespace Bethel\EntityBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OwnedTutorSessionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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
            ->add('substitutable', 'checkbox', array(
                'label' => 'Allow substitutes',
                'required' => false
            ))
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
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'bethel_entitybundle_ownedtutorsession';
    }
}
