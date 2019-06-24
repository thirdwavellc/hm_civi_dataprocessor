<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

use CRM_Dataprocessor_ExtensionUtil as E;

class CRM_DataprocessorSearch_Form_CaseSearch extends CRM_DataprocessorSearch_Form_AbstractSearch {

  /**
   * Returns the name of the default Entity
   *
   * @return string
   */
  public function getDefaultEntity() {
    return 'Contact';
  }

  /**
   * Returns the url for view of the record action
   *
   * @param $row
   *
   * @return false|string
   */
  protected function link($row) {
    $record = $row['record'];
    $idFieldName = $this->getIdFieldName();
    $contactIdFieldName = $this->getContactIdFieldName();
    $caseId = $record[$idFieldName]->formattedValue;
    $contactId = $record[$contactIdFieldName]->formattedValue;
    return CRM_Utils_System::url('civicrm/contact/view/case', 'reset=1&action=view&id='.$caseId.'&cid='.$contactId.'&context=search');
  }

  /**
   * Returns the link text for view of the record action
   *
   * @param $row
   *
   * @return false|string
   */
  protected function linkText($row) {
    return E::ts('Manage case');
  }

  /**
   * Return the data processor name
   *
   * @return String
   */
  protected function getDataProcessorName() {
    $dataProcessorName = str_replace('civicrm/dataprocessor_case_search/', '', CRM_Utils_System::getUrlPath());
    return $dataProcessorName;
  }

  /**
   * Returns the name of the output for this search
   *
   * @return string
   */
  protected function getOutputName() {
    return 'case_search';
  }

  /**
   * Checks whether the output has a valid configuration
   *
   * @return bool
   */
  protected function isConfigurationValid() {
    if (!isset($this->dataProcessorOutput['configuration']['case_id_field'])) {
      return false;
    }
    if (!isset($this->dataProcessorOutput['configuration']['contact_id_field'])) {
      return false;
    }
    return true;
  }

  /**
   * Returns the name of the ID field in the dataset.
   *
   * @return string
   */
  protected function getIdFieldName() {
    return $this->dataProcessorOutput['configuration']['case_id_field'];
  }

  /**
   * Returns the name of the ID field in the dataset.
   *
   * @return string
   */
  protected function getContactIdFieldName() {
    return $this->dataProcessorOutput['configuration']['contact_id_field'];
  }

  /**
   * @return string
   */
  protected function getEntityTable() {
    return 'civicrm_case';
  }

  /**
   * Builds the list of tasks or actions that a searcher can perform on a result set.
   *
   * @return array
   */
  public function buildTaskList() {
    if (!$this->_taskList) {
      $this->_taskList = CRM_Case_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission());
    }
    return $this->_taskList;
  }

  /**
   * Returns whether the ID field is Visible
   *
   * @return bool
   */
  protected function isIdFieldVisible() {
    if (isset($this->dataProcessorOutput['configuration']['hide_id_fields']) && $this->dataProcessorOutput['configuration']['hide_id_fields']) {
      return false;
    }
    return true;
  }

  /**
   * Returns an array with hidden columns
   *
   * @return array
   */
  protected function getHiddenFields() {
    $hiddenFields = array();
    if (!$this->isIdFieldVisible()) {
      $hiddenFields[] = $this->getIdFieldName();
      $hiddenFields[] = $this->getContactIdFieldName();
    }
    return $hiddenFields;
  }

}