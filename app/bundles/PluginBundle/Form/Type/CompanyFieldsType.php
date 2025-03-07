<?php

namespace Mautic\PluginBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<mixed>>
 */
class CompanyFieldsType extends AbstractType
{
    use FieldsTypeTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->buildFormFields($builder, $options, $options['integration_fields'], $options['mautic_fields'], 'company', $options['limit'], $options['start']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $this->configureFieldOptions($resolver, 'company');
    }

    public function getBlockPrefix(): string
    {
        return 'integration_company_fields';
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $this->buildFieldView($view, $options);
    }
}
