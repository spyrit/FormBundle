<?php

namespace Spyrit\FormBundle\Form;

use Propel\Bundle\PropelBundle\Form\DataTransformer\CollectionToArrayTransformer;
use Spyrit\FormBundle\Form\ChoiceList\ModelNewChoiceList;
use Spyrit\FormBundle\Form\DataTransformer\ArrayToSeparatedStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\ChoicesToValuesTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\ChoiceToValueTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Util\PropertyPath;

/**
 * Select2HiddenPropelType
 *
 */
class Select2HiddenPropelType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['multiple']) {
            $builder
                ->addViewTransformer(new CollectionToArrayTransformer(), true)
                ->addViewTransformer(new ChoicesToValuesTransformer($options['choice_list']))
                ->addViewTransformer(new ArrayToSeparatedStringTransformer($options['separator'], null, false))
            ;
        }else {
            $builder
                ->addViewTransformer(new ChoiceToValueTransformer($options['choice_list']));
        }
    }

    protected function buildPropelInitSource($values, $initValues, $property = null)
    {
        $values = explode(',', $values);
        $data = array();
        foreach ($initValues as $key => $value) {
            if (in_array($key, $values)) {
                $data[$key] = $value;
            }
        }
        return $this->buildPropelSelect2Data($data, $property);
    }

    protected function buildPropelSelect2Data($values, $property = null)
    {
        $propertyPath = null;
        if (!empty($property)) {
            $propertyPath = new PropertyPath($property);
        }

        $data = array();
        foreach ($values as $key => $value) {
            $data[] = array('id' => $key, 'text' => ($propertyPath ? $propertyPath->getValue($value) : (string) $value));
        }
        return $data;
    }

    protected function buildCommonJsConfig(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['js_config'] = array(
            'separator' => $options['separator'],
            'minimumInputLength' => $options['min_input_length'],
            'maximumSelectionSize' => $options['max_selection_size'],
            'multiple' => $options['multiple'],
            'closeOnSelect' => !$options['multiple'],
        );

        if (!empty($options['formatSelection'])) {
            $view->vars['formatSelection'] = $options['formatSelection'];
        }

        if (!empty($options['formatResult'])) {
            $view->vars['formatResult'] = $options['formatResult'];
        }

        if (in_array($options['width'], array('resolve', 'copy'))) {
            $view->vars['js_config']['width'] = $options['width'];
        } elseif (!empty($options['width'])) {
            $view->vars['js_config']['width'] = 'copy';
            $view->vars['attr']['style'] = 'width: '.$options['width'];
        } else {
            $view->vars['js_config']['width'] = 'resolve';
        }

        if (!empty($options['empty_value'])) {
            $view->vars['js_config']['placeholder'] = $options['empty_value'];
//            $view->vars['attr']['data-placeholder'] = $options['empty_value'];
            if (!$options['multiple']) {
                $view->vars['js_config']['allowClear'] = $options['allow_clear'];
            }
        }
    }

    protected function buildSourceData(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['useTags'] = $options['use_tags'];
        $view->vars['maxPerPage'] = $options['max_per_page'];
        $view->vars['initSource'] = null;

        if (!empty($options['class'])) {
            $choices = $options['choice_list'];
        }

        $options['choices'] = empty($options['choices']) ? array() : $options['choices'];
        $options['init_choices'] = empty($options['init_choices']) ? $options['choices'] : $options['init_choices'];

        $view->vars['sourceType'] = is_array($options['choices']) ? 'values' : 'route';
        $view->vars['initSourceType'] = is_array($options['init_choices']) ? 'values' : 'route';

        if ($options['use_tags']) {
            $view->vars['js_config']['tokenSeparators'] = array(',', ' ');
            $view->vars['js_config']['tags'] = is_array($options['choices']) ? $this->buildPropelSelect2Data($choices->getChoices(), $options['property']) : true;
        }

        if (is_array($options['choices'])) {
            if (!$options['use_tags']) {
                $view->vars['js_config']['data'] = $this->buildPropelSelect2Data($choices->getChoices(), $options['property']);
            }
        } else {
            $view->vars['source'] = $options['choices'];
        }
        if ($view->vars['value'] !== null && $view->vars['value'] !== '') {
            if (is_array($options['init_choices'])) {
                $view->vars['initSource'] = $this->buildPropelInitSource($view->vars['value'], $choices->getChoices(), $options['property']);
            } else {
                $view->vars['initSource'] = $options['init_choices'];
            }
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {

        $choiceList = function (Options $options) {
            return new ModelNewChoiceList(
                $options['class'],
                $options['property'],
                is_array($options['choices']) ? $options['choices'] : null,
                $options['query'],
                $options['group_by'],
                $options['use_tags'],
                $options['tag_column']
            );
        };

        $resolver
            ->setDefaults(array(
                'multiple' => false,
                'separator' => ',',
                'min_input_length' => null,
                'max_selection_size' => null,
                'allow_clear' => true,
                'empty_value' => null,
                'width' => 'resolve',
                'init_choices' => null,
                'max_per_page' => 10,
                'use_tags' => false,
                'formatSelection' => null,
                'formatResult' => null,
                'label_attr' => array('class' => 'select2_label'),
                // Propel
                'tag_column' => null,
                'class'             => null,
                'property'          => null,
                'query'             => null,
                'choices'           => null,
                'group_by'          => null,
                'choice_list'       => $choiceList,
                'index_property'    => null
            ))
            ->setAllowedTypes(array(
                'choices' => array('array', 'string', 'null'),
                'init_choices' => array('array', 'string', 'null'),
            ))
            ->setAllowedValues(array(
            ))
            ->setRequired(array(
            ))
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $this->buildCommonJsConfig($view, $form, $options);
        $this->buildSourceData($view, $form, $options);
    }

    public function getParent()
    {
        return TextType::class;
    }

    public function getName()
    {
        return 'select2_hidden_propel';
    }
}