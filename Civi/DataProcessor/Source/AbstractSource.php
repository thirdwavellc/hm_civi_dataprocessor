<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\DataProcessor\Source;

use Civi\DataProcessor\DataFlow\MultipleDataFlows\JoinInterface;
use Civi\DataProcessor\DataSpecification\FieldSpecification;
use Civi\DataProcessor\ProcessorType\AbstractProcessorType;

use CRM_Dataprocessor_ExtensionUtil as E;

abstract class AbstractSource implements SourceInterface {

  /**
   * @var String
   */
  protected $sourceName;

  /**
   * @var String
   */
  protected $sourceTitle;

  /**
   * @var AbstractProcessorType
   */
  protected $dataProcessor;

  /**
   * @var array
   */
  protected $configuration;

  /**
   * @var \Civi\DataProcessor\DataFlow\AbstractDataFlow
   */
  protected $dataFlow;

  public function __construct() {

  }

  /**
   * @return String
   */
  public function getSourceName() {
    return $this->sourceName;
  }

  /**
   * @param String $name
   * @return \Civi\DataProcessor\Source\SourceInterface
   */
  public function setSourceName($name) {
    $this->sourceName = $name;
    return $this;
  }

  /**
   * @return String
   */
  public function getSourceTitle() {
    return $this->sourceTitle;
  }

  /**
   * @param String $title
   * @return \Civi\DataProcessor\Source\SourceInterface
   */
  public function setSourceTitle($title) {
    $this->sourceTitle = $title;
    return $this;
  }

  /**
   * @param AbstractProcessorType $dataProcessor
   *
   * @return \Civi\DataProcessor\Source\SourceInterface
   */
  public function setDataProcessor(AbstractProcessorType $dataProcessor) {
    $this->dataProcessor = $dataProcessor;
    return $this;
  }

  /**
   * @param array $configuration
   *
   * @return \Civi\DataProcessor\Source\SourceInterface
   */
  public function setConfiguration($configuration) {
    $this->configuration = $configuration;
    return $this;
  }

  /**
   * Returns the default configuration for this data source
   *
   * @return array
   */
  public function getDefaultConfiguration() {
    return array();
  }

  /**
   * @return \Civi\DataProcessor\DataFlow\AbstractDataFlow|\Civi\DataProcessor\DataFlow\AbstractDataFlow
   * @throws \Exception
   */
  public function getDataFlow() {
    $this->initialize();
    return $this->dataFlow;
  }

  /**
   * Sets the join specification to connect this source to other data sources.
   *
   * @param \Civi\DataProcessor\DataFlow\MultipleDataFlows\JoinInterface $join
   *
   * @return \Civi\DataProcessor\Source\SourceInterface
   */
  public function setJoin(JoinInterface $join) {
    return $this;
  }

  /**
   * Ensure that filter field is accesible in the query
   *
   * @param String $fieldName
   * @return \Civi\DataProcessor\DataFlow\AbstractDataFlow|null
   * @throws \Exception
   */
  public function ensureField($fieldName) {
    $field = $this->getAvailableFields()->getFieldSpecificationByName($fieldName);
    if ($field) {
      $this->dataFlow->getDataSpecification()
        ->addFieldSpecification($fieldName, $field);
    }
    return $this->dataFlow;
  }

  /**
   * Ensures a field is in the data source
   *
   * @param \Civi\DataProcessor\DataSpecification\FieldSpecification $fieldSpecification
   *
   * @return \Civi\DataProcessor\Source\SourceInterface
   * @throws \Exception
   */
  public function ensureFieldInSource(FieldSpecification $fieldSpecification) {
    if (!$this->dataFlow->getDataSpecification()->doesFieldExist($fieldSpecification->name)) {
      $this->dataFlow->getDataSpecification()->addFieldSpecification($fieldSpecification->name, $fieldSpecification);
    }
    return $this;
  }

  /**
   * @return \Civi\DataProcessor\DataSpecification\DataSpecification
   */
  public function getAvailableFilterFields() {
    return $this->getAvailableFields();
  }

  /**
   * Returns true when this source has additional configuration
   *
   * @return bool
   */
  public function hasConfiguration() {
    return count($this->getAvailableFilterFields()->getFields()) > 0 ? true : false;
  }

  /**
   * Returns an array with the names of required configuration filters.
   * Those filters are displayed as required to the user
   *
   * @return array
   */
  protected function requiredConfigurationFilters() {
    return array();
  }

  /**
   * When this source has additional configuration you can add
   * the fields on the form with this function.
   *
   * @param \CRM_Core_Form $form
   * @param array $source
   */
  public function buildConfigurationForm(\CRM_Core_Form $form, $source=array()) {
    $fields = array();
    $required_fields = array();
    $requiredFilters = $this->requiredConfigurationFilters();
    foreach($this->getAvailableFilterFields()->getFields() as $fieldSpec) {
      $alias = $fieldSpec->name;
      $isRequired = false;
      if (in_array($alias, $requiredFilters)) {
        $isRequired = true;
      }
      switch ($fieldSpec->type) {
        case 'Boolean':
          if ($isRequired) {
            $required_fields[$alias] = $fieldSpec->title;
          } else {
            $fields[$alias] = $fieldSpec->title;
          }
          $form->addElement('select', "{$alias}_op", ts('Operator:'), [
            '=' => E::ts('Is equal to'),
            '!=' => E::ts('Is not equal to'),
          ]);
          if (!empty($fieldSpec->getOptions())) {
            $form->addElement('select', "{$alias}_value", $fieldSpec->title, array('' => E::ts(' - Select - ')) + $fieldSpec->getOptions());
          }
          break;
        default:
          if ($fieldSpec->getOptions()) {
            if ($isRequired) {
              $required_fields[$alias] = $fieldSpec->title;
            } else {
              $fields[$alias] = $fieldSpec->title;
            }
            $form->addElement('select', "{$alias}_op", ts('Operator:'), [
              'IN' => E::ts('Is one of'),
              'NOT IN' => E::ts('Is not one of'),
            ]);
            $form->addElement('select', "{$alias}_value", $fieldSpec->title, $fieldSpec->getOptions(), array(
              'style' => 'min-width:250px',
              'class' => 'crm-select2 huge',
              'multiple' => 'multiple',
              'placeholder' => E::ts('- select -'),
            ));
          }
      }
    }
    $form->assign('filter_fields', $fields);
    $form->assign('filter_required_fields', $required_fields);

    $defaults = array();
    if (isset($source['configuration']['filter'])) {
      foreach($source['configuration']['filter'] as $alias => $filter) {
        $defaults[$alias.'_op'] = $filter['op'];
        $defaults[$alias.'_value'] = $filter['value'];
      }
    }
    $form->setDefaults($defaults);
  }

  /**
   * When this source has configuration specify the template file name
   * for the configuration form.
   *
   * @return false|string
   */
  public function getConfigurationTemplateFileName() {
    return "CRM/Dataprocessor/Form/Source/Configuration.tpl";
  }


  /**
   * Process the submitted values and create a configuration array
   *
   * @param $submittedValues
   * @return array
   */
  public function processConfiguration($submittedValues) {
    $configuration = array();
    $filter_config = array();
    foreach($this->getAvailableFilterFields()->getFields() as $fieldSpec) {
      $alias = $fieldSpec->name;
      if ($this->valueSubmittedAndNotEmpty($alias.'_value', $submittedValues)) {
        $filter_config[$alias] = array(
          'op' => $submittedValues[$alias.'_op'],
          'value' => $submittedValues[$alias.'_value']
        );
      }
    }
    $configuration['filter'] = $filter_config;

    return $configuration;
  }

  /**
   * Checks whether a value is submitted and not empty.
   *
   * @param $field
   * @param $values
   *
   * @return bool
   */
  protected function valueSubmittedAndNotEmpty($field, $values) {
    if (!isset($values[$field])) {
      return false;
    }
    if (is_array($values[$field]) && count($values[$field]) === 0) {
      return false;
    }
    if (is_string($values[$field]) && strlen($values[$field]) === 0) {
      return false;
    }
    return true;
  }

}
