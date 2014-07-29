<?php

namespace ICup\Bundle\PublicSiteBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ButtonTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('buttontype', $options['buttontype']);
        $builder->setAttribute('icon', $options['icon']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['buttontype'] = $form->getConfig()->getAttribute('buttontype');
        $view->vars['icon'] = $form->getConfig()->getAttribute('icon');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'buttontype' => 'btn btn-primary',
            'icon' => null,
        ));
    }

    public function getExtendedType()
    {
        return 'submit';
    }
}
