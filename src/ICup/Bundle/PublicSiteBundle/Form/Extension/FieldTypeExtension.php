<?php

namespace ICup\Bundle\PublicSiteBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FieldTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('phonestyle', $options['phonestyle']);
        $builder->setAttribute('help', $options['help']);
        $builder->setAttribute('icon', $options['icon']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['phonestyle'] = $form->getConfig()->getAttribute('phonestyle');
        $view->vars['help'] = $form->getConfig()->getAttribute('help');
        $view->vars['icon'] = $form->getConfig()->getAttribute('icon');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'phonestyle' => false,
            'help' => null,
            'icon' => null,
        ));
    }

    public function getExtendedType()
    {
        return 'form';
    }
}
