<?php

namespace ICup\Bundle\PublicSiteBundle\Form\Doctrine;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\TournamentOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TournamentOptionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('drr')
            ->add('svd')
            ->add('er')
            ->add('strategy')
            ->add('wpoints')
            ->add('tpoints')
            ->add('lpoints')
            ->add('dscore')
            ->add('order')
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
            'data_class' => TournamentOption::class,
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
