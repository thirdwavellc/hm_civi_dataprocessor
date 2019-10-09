<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\DataProcessor\FieldOutputHandler;

use Civi\DataProcessor\Exception\DataSourceNotFoundException;
use Civi\DataProcessor\Exception\FieldNotFoundException;
use CRM_Dataprocessor_ExtensionUtil as E;
use Civi\DataProcessor\Source\SourceInterface;
use Civi\DataProcessor\DataSpecification\FieldSpecification;

class RawFieldOutputHandler extends AbstractSimpleFieldOutputHandler implements OutputHandlerAggregate {

  /**
   * @var bool
   */
  protected $isAggregateField = false;

  /**
   * Initialize the processor
   *
   * @param String $alias
   * @param String $title
   * @param array $configuration
   * @param \Civi\DataProcessor\ProcessorType\AbstractProcessorType $processorType
   */
  public function initialize($alias, $title, $configuration) {
    parent::initialize($alias, $title, $configuration);
    $this->isAggregateField = isset($configuration['is_aggregate']) ? $configuration['is_aggregate'] : false;
  }

  /**
   * When this handler has additional configuration you can add
   * the fields on the form with this function.
   *
   * @param \CRM_Core_Form $form
   * @param array $field
   */
  public function buildConfigurationForm(\CRM_Core_Form $form, $field=array()) {
    parent::buildConfigurationForm($form, $field);
    $form->add('checkbox', 'is_aggregate', E::ts('Aggregate on this field'));
    if (isset($field['configuration'])) {
      $configuration = $field['configuration'];
      $defaults = array();
      if (isset($configuration['is_aggregate'])) {
        $defaults['is_aggregate'] = $configuration['is_aggregate'];
      }
      $form->setDefaults($defaults);
    }
  }

  /**
   * When this handler has configuration specify the template file name
   * for the configuration form.
   *
   * @return false|string
   */
  public function getConfigurationTemplateFileName() {
    return "CRM/Dataprocessor/Form/Field/Configuration/RawFieldOutputHandler.tpl";
  }


  /**
   * Process the submitted values and create a configuration array
   *
   * @param $submittedValues
   * @return array
   */
  public function processConfiguration($submittedValues) {
    $configuration = parent::processConfiguration($submittedValues);
    $configuration['is_aggregate'] = isset($submittedValues['is_aggregate']) ? $submittedValues['is_aggregate'] : false;
    return $configuration;
  }

  /**
   * @return \Civi\DataProcessor\DataSpecification\FieldSpecification
   */
  public function getAggregateFieldSpec() {
    return $this->inputFieldSpec;
  }

  /**
   * @return bool
   */
  public function isAggregateField() {
    return $this->isAggregateField;
  }


}
