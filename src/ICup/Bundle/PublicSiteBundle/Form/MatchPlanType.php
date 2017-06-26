<?php

namespace ICup\Bundle\PublicSiteBundle\Form;

use ICup\Bundle\PublicSiteBundle\Entity\MatchPlanUpdateForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatchPlanType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('venue')
            ->add('matchtime')
            ->add('date')
            ->add('timeslot')
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => MatchPlanUpdateForm::class,
            'csrf_protection' => false,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return '';
    }
}
