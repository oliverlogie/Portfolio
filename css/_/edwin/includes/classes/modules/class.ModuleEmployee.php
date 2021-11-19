<?php

/**
 * Employee Module Class
 *
 * $LastChangedDate: 2019-05-10 11:58:32 +0200 (Fr, 10 Mai 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */
class ModuleEmployee extends Module
{
  public static $subClasses = array(
      'department' => 'ModuleEmployeeDepartment',
      'location'   => 'ModuleEmployeeLocation',
  );

  /**
   * The database column prefix.
   *
   * @var string
   */
  protected $_dbColumnPrefix = 'E';

  /**
   * Module's prefix used for configuration, template
   * and language variables.
   *
   * @var string
   */
  protected $_prefix = 'ee';

  /**
   * Module's model.
   *
   * @var Employee
   */
  protected $_model = null;

  /**
   * Department model.
   *
   * @var EmployeeDepartment
   */
  protected $_department = null;

  /**
   * Contains the available filter criteria including SQL WHERE clauses.
   * The format is "name" => ( "DBColumn1 = '", "' OR DBColumn2 LIKE '%", "%'").
   * The filter expression is inserted between the array elements.
   *
   * @var array
   */
  private $_listFilters = array(
    "name"       => array("EFirstname LIKE '%", "%' OR ELastname LIKE '%", "%' OR ETitle LIKE '%", "%'"),
    "email"      => array("EEmail LIKE '%", "%'"),
    "specialism" => array("ESpecialism LIKE '%", "%'"),
    "function"   => array("EFunction LIKE '%", "%'"),
    "job_title"  => array("EJobTitle LIKE '%", "%'"),
    "department" => array(),
    "location"   => array(),
  );

  /**
   * Shows module's content.
   *
   * @see Module::show_innercontent()
   */
  public function show_innercontent()
  {
    $get = new Input(Input::SOURCE_GET);
    $post = new Input(Input::SOURCE_POST);

    // Create model instance
    $emplFields = $post->readMultipleArrayStringToString($this->_prefix.'_field');
    $this->_model = new Employee($this->db, $this->table_prefix, $this->_prefix, $emplFields);

    // Create attribute instance
    $attribute = new Attribute($this->db, $this->table_prefix);
    $departments = $attribute->readAttributesByAGlobalIdentifier(AttributeGlobal::ID_EMPLOYEE_DEPARTMENT);
    $locations = $attribute->readAttributesByAGlobalIdentifier(AttributeGlobal::ID_EMPLOYEE_LOCATION);
    // Create department and location field instances
    $departmentsArray = array();
    foreach ($departments as $department) {
      $departmentsArray[$department->id] = parseOutput($department->title);
    }
    $locationsArray = array();
    foreach ($locations as $location) {
      $locationsArray[$location->id] = parseOutput($location->title);
    }
    /* @var $departmentField Field */
    $departmentField = $this->_model->createFieldInstance('department');
    $departmentField->setPredefined($departmentsArray);
    $departmentValue = (isset($emplFields[$this->_prefix.'_department'])) ? $emplFields[$this->_prefix.'_department'] : null;
    $departmentField->setValue($departmentValue);
    /* @var $locationField Field */
    $locationField = $this->_model->createFieldInstance('location');
    $locationField->setPredefined($locationsArray);
    $locationValue = (isset($emplFields[$this->_prefix.'_location'])) ? $emplFields[$this->_prefix.'_location'] : null;
    $locationField->setValue($locationValue);

    // Perform create/update/move/delete of a side box if necessary
    $this->_createEmployee();
    $this->_updateEmployee();
    $this->_moveEmployee();
    $this->_deleteEmployee();

    // Delete a side box image.
    $this->_deleteEmployeeImage();

    if (!empty($this->action[0])) {
      return $this->_showForm();
    } else {
      return $this->_showList();
    }
  }

  /**
   * Deletes an image, but do not redirect the user.
   * @see Module::delete_content_image()
   */
  protected function delete_content_image($module, $table, $key, $col, $number) {
    $image = $this->db->GetOne("SELECT $col$number FROM {$this->table_prefix}module_$table WHERE $key = $this->item_id");
    if ($image) {
      self::_deleteImageFiles($image);
      $this->db->query("UPDATE {$this->table_prefix}module_$table SET $col$number = '' WHERE $key = $this->item_id");
    }
  }

  /**
   * Returns delete data (label, question label, link) to delete
   * an image.
   * @see Module::get_delete_image()
   */
  protected function get_delete_image($module,$prefix,$image_number) {
    global $_LANG;

    $delete_link = "index.php?action=mod_".$module."&amp;action2=main;edit&amp;site=".$this->site_id."&amp;page=".$this->item_id."&amp;dimg=".$image_number;
    $delete_data = array( $prefix.'_delete_image_label' => (isset($_LANG[$prefix."_delete_image_label"]) ? $_LANG[$prefix."_delete_image_label"] : $_LANG["global_delete_image_label"]),
                          $prefix.'_delete_image_question_label' => (isset($_LANG[$prefix."_delete_image_question_label"]) ? $_LANG[$prefix."_delete_image_question_label"] : $_LANG["global_delete_image_question_label"]),
                          $prefix.'_delete_image'.$image_number.'_link' => $delete_link );

    return $delete_data;
  }

  /**
   * Creates a side box.
   */
  private function _createEmployee()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') || $this->action[0] != 'new') {
      return;
    }

    $title1 = $post->readString('ee_title1', Input::FILTER_PLAIN);
    $title2 = $post->readString('ee_title2', Input::FILTER_PLAIN);
    $title3 = $post->readString('ee_title3', Input::FILTER_PLAIN);
    $text1 = $post->readString('ee_text1', Input::FILTER_CONTENT_TEXT);
    $text2 = $post->readString('ee_text2', Input::FILTER_CONTENT_TEXT);
    $text3 = $post->readString('ee_text3', Input::FILTER_CONTENT_TEXT);
    $noRandom = (int)$post->readBool('ee_norandom');
    list($link, $linkID) = $post->readContentItemLink('ee_link');
    $extLink = $post->readString('ee_url', Input::FILTER_PLAIN);

    // Process attached campaign
    $cgAttached = new CampaignAttached($this->db, $this->table_prefix);
    $cgAId = $cgAttached->process(array(
      'm_cg'             => $post->readInt('m_cg'),
      'm_cg_recipient'   => $post->readString('m_cg_recipient', Input::FILTER_PLAIN),
      'm_cg_data_origin' => $post->readString('m_cg_data_origin', Input::FILTER_PLAIN),
    ));

    if ($extLink) { // validate url protocol
      $valid = false;
      $protocols = $this->_configHelper->get('url_protocols', 'ee');
      foreach ($protocols as $protocol) {
        if (mb_substr($extLink, 0, mb_strlen($protocol)) === $protocol) {
          $valid = true;
          break;
        }
      }

      if (!$valid) {
        $this->setMessage(Message::createFailure(sprintf($_LANG['ee_message_invalid_url_protocol'], implode(', ', $protocols))));
        return;
      }
    }

    if ($this->_model->validate() === false)
    {
      $this->setMessage($this->_model->getValidationMsg());
      return false;
    }
    $this->_model->hourlyRate = str_replace(',', '.', $this->_model->hourlyRate);

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_employee",
                                         'EID', 'EPosition',
                                         'FK_SID', $this->site_id);
    $position = $positionHelper->getHighestPosition() + 1;

    $sql = "INSERT INTO {$this->table_prefix}module_employee "
         . '(ETitle1, ETitle2, ETitle3, EText1, EText2, EText3, EPosition, '
         . ' ENoRandom, EUrl, FK_CGAID, FK_CIID, FK_SID) '
         . "VALUES ('{$this->db->escape($title1)}', '{$this->db->escape($title2)}', "
         . "        '{$this->db->escape($title3)}', '{$this->db->escape($text1)}', "
         . "        '{$this->db->escape($text2)}', '{$this->db->escape($text3)}', "
         . "        $position, $noRandom, '{$this->db->escape($extLink)}', "
         . "        '{$cgAId}', $linkID, $this->site_id "
         . " ) ";
    $result = $this->db->query($sql);

    // Set the item ID to the inserted side box so that the _storeImage
    // method can assign the correct file names to the image files.
    $this->item_id = $this->db->insert_id();

    $this->_model->id = $this->item_id;
    $this->_model->siteId = $this->site_id;
    $this->_model->update();

    $image1 = isset($_FILES['ee_image1']) ? $this->_storeImage($_FILES['ee_image1'], null, 'ee', 1) : '';
    $image2 = isset($_FILES['ee_image2']) ? $this->_storeImage($_FILES['ee_image2'], null, 'ee', 2) : '';
    $image3 = isset($_FILES['ee_image3']) ? $this->_storeImage($_FILES['ee_image3'], null, 'ee', 3) : '';

    $sql = "UPDATE {$this->table_prefix}module_employee "
         . "SET EImage1 = '$image1', "
         . "    EImage2 = '$image2', "
         . "    EImage3 = '$image3' "
         . "WHERE EID = $this->item_id ";
    $result = $this->db->query($sql);

    $message = $this->_getMessage() ?: Message::createSuccess($_LANG['ee_message_create_success']);
    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_getBackLinkUrl(), $message);
    }
    else {
      $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)), $message);
    }
  }

  /**
   * Updates a side box.
   */
  private function _updateEmployee()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') || $this->action[0] != 'edit') {
      return;
    }

    $title1 = $post->readString('ee_title1', Input::FILTER_PLAIN);
    $title2 = $post->readString('ee_title2', Input::FILTER_PLAIN);
    $title3 = $post->readString('ee_title3', Input::FILTER_PLAIN);
    $text1 = $post->readString('ee_text1', Input::FILTER_CONTENT_TEXT);
    $text2 = $post->readString('ee_text2', Input::FILTER_CONTENT_TEXT);
    $text3 = $post->readString('ee_text3', Input::FILTER_CONTENT_TEXT);
    $noRandom = (int)$post->readBool('ee_norandom');
    list($link, $linkID) = $post->readContentItemLink('ee_link');
    $extLink = $post->readString('ee_url', Input::FILTER_PLAIN);

    // Process attached campaign
    $cgAttached = new CampaignAttached($this->db, $this->table_prefix);
    $cgAttached->id = $this->db->GetOne("SELECT FK_CGAID FROM {$this->table_prefix}module_employee WHERE EID = $this->item_id ");
    $cgAId = $cgAttached->process(array(
      'm_cg'             => $post->readInt('m_cg'),
      'm_cg_recipient'   => $post->readString('m_cg_recipient', Input::FILTER_PLAIN),
      'm_cg_data_origin' => $post->readString('m_cg_data_origin', Input::FILTER_PLAIN),
    ));
    if ($cgAId === false)
    {
      $this->setMessage($cgAttached->getMessage());
      return;
    }

    if ($extLink) { // validate url protocol
      $valid = false;
      $protocols = $this->_configHelper->getVar('url_protocols', 'ee');

      foreach ($protocols as $protocol) {
        if (mb_substr($extLink, 0, mb_strlen($protocol)) === $protocol) {
          $valid = true;
          break;
        }
      }

      if (!$valid) {
        $this->setMessage(Message::createFailure(sprintf($_LANG['ee_message_invalid_url_protocol'], implode(', ', $protocols))));
        return;
      }
    }

    if ($this->_model->validate() === false)
    {
      $this->setMessage($this->_model->getValidationMsg());
      return false;
    }
    $this->_model->hourlyRate = str_replace(',', '.', $this->_model->hourlyRate);

    // Image upload.
    $sql = 'SELECT EImage1, EImage2, EImage3 '
         . "FROM {$this->table_prefix}module_employee "
         . "WHERE EID = $this->item_id ";
    $existingImages = $this->db->GetRow($sql);
    $image1 = $existingImages['EImage1'];
    if (isset($_FILES['ee_image1']) && $uploadedImage = $this->_storeImage($_FILES['ee_image1'], $image1, 'ee', 1)) {
      $image1 = $uploadedImage;
    }
    $image2 = $existingImages['EImage2'];
    if (isset($_FILES['ee_image2']) && $uploadedImage = $this->_storeImage($_FILES['ee_image2'], $image2, 'ee', 2)) {
      $image2 = $uploadedImage;
    }
    $image3 = $existingImages['EImage3'];
    if (isset($_FILES['ee_image3']) && $uploadedImage = $this->_storeImage($_FILES['ee_image3'], $image3, 'ee', 3)) {
      $image3 = $uploadedImage;
    }

    $sql = " UPDATE {$this->table_prefix}module_employee "
         . " SET ETitle1 = '{$this->db->escape($title1)}', "
         . "     ETitle2 = '{$this->db->escape($title2)}', "
         . "     ETitle3 = '{$this->db->escape($title3)}', "
         . "     EText1 = '{$this->db->escape($text1)}', "
         . "     EText2 = '{$this->db->escape($text2)}', "
         . "     EText3 = '{$this->db->escape($text3)}', "
         . "     ENoRandom = $noRandom, "
         . "     FK_CIID = $linkID, "
         . "     EUrl = '{$this->db->escape($extLink)}', "
         . "     EImage1 = '$image1', "
         . "     EImage2 = '$image2', "
         . "     EImage3 = '$image3', "
         . "     FK_CGAID = '{$cgAId}' "
         . " WHERE EID = $this->item_id ";
    $result = $this->db->query($sql);

    $this->_model->siteId = $this->site_id;
    $this->_model->id = $this->item_id;
    $this->_model->update();

    $message = $this->_getMessage() ?: Message::createSuccess($_LANG['ee_message_update_success']);
    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_getBackLinkUrl(), $message);
    }
    else {
      $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)), $message);
    }
  }

  /**
   * Moves a side box if the GET parameters moveID and moveTo are set.
   */
  private function _moveEmployee()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $moveID = $get->readInt('moveID');
    $moveTo = $get->readInt('moveTo');
    if (!$moveID || !$moveTo) {
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_employee",
                                         'EID', 'EPosition',
                                         'FK_SID', $this->site_id);
    $moved = $positionHelper->move($moveID, $moveTo);
    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['ee_message_move_success']));
    }
  }

  /**
   * Deletes a side box if the GET parameter deleteEmployeeID is set.
   */
  private function _deleteEmployee()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $ID = $get->readInt('deleteEmployeeID');
    if (!$ID) {
      return;
    }

    // Delete images.
    $sql = 'SELECT EImage1, EImage2, EImage3 '
         . "FROM {$this->table_prefix}module_employee "
         . "WHERE EID = $ID ";
    $images= $this->db->GetRow($sql);
    self::_deleteImageFiles($images);

    // Delete the side boxes assignments.
    $sql = "DELETE FROM {$this->table_prefix}module_employee_assignment "
         . "WHERE FK_EID = $ID ";
    $result = $this->db->query($sql);

    // Delete attached campaigns
    $cgAttached = new CampaignAttached($this->db, $this->table_prefix);
    $cgAttached->id = $this->db->GetOne("SELECT FK_CGAID FROM {$this->table_prefix}module_employee WHERE EID = $ID ");
    $cgAttached->delete();

    // move item to last position before deleting it
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_employee",
                                         'EID', 'EPosition',
                                         'FK_SID', $this->site_id);
    $positionHelper->move($ID, $positionHelper->getHighestPosition());

    // Delete employee.
    $this->_model->id = $ID;
    $this->_model->delete();

    if ($result) {
      if (isset($_LANG['ee_message_deleteitem_success'])) {
        return $_LANG['ee_message_deleteitem_success'];
      }

      return $_LANG['global_message_deleteitem_success'];
    }

    return false;
  }

  /**
   * Deletes a side box image if the GET parameter dimg is set.
   */
  private function _deleteEmployeeImage()
  {
    $get = new Input(Input::SOURCE_GET);

    $imageNumber = $get->readInt('dimg');
    if (!$imageNumber) {
      return;
    }

    $this->delete_content_image('employee', 'employee', 'EID', 'EImage', $imageNumber);
  }

  /**
   * Shows the form for creating or editing side boxes.
   */
  private function _showForm()
  {
    global $_LANG, $_LANG2;

    $post = new Input(Input::SOURCE_POST);

    $noRandomConfig = ConfigHelper::get('ee_no_random', '', $this->site_id);
    $cgAttached = new CampaignAttached($this->db, $this->table_prefix);

    // edit employee -> load data
    if ($this->item_id)
    {
      // Let the model know what about we are talking here
      if ($post->exists('ee_title1')) {
        $emplFields = $post->readMultipleArrayStringToString($this->_prefix.'_field');
        $this->_model->setFields($emplFields);
      }
      else {
        $this->_model = $this->_model->readEmployeeById($this->item_id);
        // Create attribute instance
        $attribute = new Attribute($this->db, $this->table_prefix);
        $attributeEmployee = new EmployeeAttribute($this->db, $this->table_prefix);
        $departments = $attribute->readAttributesByAGlobalIdentifier(AttributeGlobal::ID_EMPLOYEE_DEPARTMENT);
        $locations = $attribute->readAttributesByAGlobalIdentifier(AttributeGlobal::ID_EMPLOYEE_LOCATION);
        // Create department and location field instances
        $departmentsArray = array();
        foreach ($departments as $department) {
          $departmentsArray[$department->id] = parseOutput($department->title);
        }
        $locationsArray = array();
        foreach ($locations as $location) {
          $locationsArray[$location->id] = parseOutput($location->title);
        }
        /* @var $departmentField Field */
        $departmentField = $this->_model->createFieldInstance('department');
        $departmentField->setPredefined($departmentsArray);
        $departmentValue = $attributeEmployee->readEmployeeAttributeIdsByEmployeeId($this->item_id, AttributeGlobal::ID_EMPLOYEE_DEPARTMENT);
        $departmentField->setValue($departmentValue);
        /* @var $locationField Field */
        $locationField = $this->_model->createFieldInstance('location');
        $locationField->setPredefined($locationsArray);
        $locationValue = $attributeEmployee->readEmployeeAttributeIdsByEmployeeId($this->item_id, AttributeGlobal::ID_EMPLOYEE_LOCATION);
        $locationField->setValue($locationValue);
      }
      $this->_model->hourlyRate = str_replace('.', $_LANG['ee_decimal_point'], $this->_model->hourlyRate);

      $sql = 'SELECT EID, ETitle1, ETitle2, ETitle3, EText1, EText2, EText3, '
           . '       EImage1, EImage2, EImage3, ENoRandom, EUrl, '
           . '       ee.FK_CIID, CIID, CIIdentifier, c.FK_SID, ee.FK_CGAID '
           . "FROM {$this->table_prefix}module_employee ee "
           . "LEFT JOIN {$this->table_prefix}contentitem c ON ee.FK_CIID = c.CIID "
           . "WHERE EID = $this->item_id ";
      $row = $this->db->GetRow($sql);
      $title1 = $row['ETitle1'];
      $title2 = $row['ETitle2'];
      $title3 = $row['ETitle3'];
      $text1 = $row['EText1'];
      $text2 = $row['EText2'];
      $text3 = $row['EText3'];
      $imageSource1 = $row['EImage1'];
      $imageSource2 = $row['EImage2'];
      $imageSource3 = $row['EImage3'];
      $noRandom = $row['ENoRandom'] ? ' checked="checked"' : '';
      $extLink = $row['EUrl'];
      $cgAttached = $cgAttached->readCampaignAttachedById($row['FK_CGAID']);

      // Detect invalid and invisible links.

      $pageParameter = array('action2' => 'main;edit',);
      $function = 'edit';
    }
    else { // new employee
      $title1 = $post->readString('ee_title1', Input::FILTER_PLAIN);
      $title2 = $post->readString('ee_title2', Input::FILTER_PLAIN);
      $title3 = $post->readString('ee_title3', Input::FILTER_PLAIN);
      $text1 = $post->readString('ee_text1', Input::FILTER_CONTENT_TEXT);
      $text2 = $post->readString('ee_text2', Input::FILTER_CONTENT_TEXT);
      $text3 = $post->readString('ee_text3', Input::FILTER_CONTENT_TEXT);
      $extLink = $post->readString('ee_url', Input::FILTER_PLAIN);
      $cgAttached->parentId = $post->readInt('m_cg');
      $cgAttached->dataOrigin = $post->readString('m_cg_data_origin', Input::FILTER_PLAIN);
      $cgAttached->recipient = $post->readString('m_cg_recipient', Input::FILTER_PLAIN);
      $imageSource1 = '';
      $imageSource2 = '';
      $imageSource3 = '';
      $pageParameter = array();
      $noRandom = '';
      $function = 'new';
    }

    $ignoredFields = array_merge(array('id'), ConfigHelper::get('ee_hidden_fields', '', $this->site_id));
    $action = "index.php?action=mod_employee&amp;action2=main;$function&amp;site=$this->site_id";
    $action .= ($function == 'edit') ? "&amp;page=$this->item_id" : '';
    $hiddenFields = '<input type="hidden" name="action" value="mod_employee" />'
                  . '<input type="hidden" name="action2" value="' . $function . '" />'
                  . '<input type="hidden" name="page" value="' . $this->item_id . '" />'
                  . '<input type="hidden" name="site" value="' . $this->site_id . '" />';
    $autoCompleteUrl = 'index.php?action=mod_response_employee&site=' . $this->site_id
                     . '&request=ContentItemAutoComplete';

    $this->tpl->load_tpl('content_employee', 'modules/ModuleEmployee.tpl');
    $this->tpl->parse_if('content_employee', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ee'));
    $this->tpl->parse_if('content_employee', 'delete_image1', $imageSource1, $this->get_delete_image('employee', 'ee', 1));
    $this->tpl->parse_if('content_employee', 'delete_image2', $imageSource2, $this->get_delete_image('employee', 'ee', 2));
    $this->tpl->parse_if('content_employee', 'delete_image3', $imageSource3, $this->get_delete_image('employee', 'ee', 3));
    $this->tpl->parse_if('content_employee', 'create_assignments', !$noRandomConfig);
    $this->tpl->parse_if('content_employee', 'item_is_edited', $this->item_id);
    $this->tpl->parse_if('content_employee', 'item_is_edited', $this->item_id);

    $content = $this->tpl->parsereturn('content_employee', array_merge($this->getInternalLinkHelper($row['FK_CIID'] ?? 0)->getTemplateVars('ee'), array(
      'ee_title1' => $title1,
      'ee_title2' => $title2,
      'ee_title3' => $title3,
      'ee_text1' => $text1,
      'ee_text2' => $text2,
      'ee_text3' => $text3,
      'ee_image_src1' => $this->get_normal_image('ee', $imageSource1),
      'ee_image_src2' => $this->get_normal_image('ee', $imageSource2),
      'ee_image_src3' => $this->get_normal_image('ee', $imageSource3),
      'ee_required_resolution_label1' => $this->_getImageSizeInfo('ee', 1),
      'ee_required_resolution_label2' => $this->_getImageSizeInfo('ee', 2),
      'ee_required_resolution_label3' => $this->_getImageSizeInfo('ee', 3),
      'ee_image_tpl_width1' => $this->_configHelper->getImageTemplateSize('ee', 'width', 1),
      'ee_image_tpl_height1' => $this->_configHelper->getImageTemplateSize('ee', 'height', 1),
      'ee_image_tpl_width2' => $this->_configHelper->getImageTemplateSize('ee', 'width', 2),
      'ee_image_tpl_height2' => $this->_configHelper->getImageTemplateSize('ee', 'height', 2),
      'ee_image_tpl_width3' => $this->_configHelper->getImageTemplateSize('ee', 'width', 3),
      'ee_image_tpl_height3' => $this->_configHelper->getImageTemplateSize('ee', 'height', 3),
      'ee_large_image_available1' => $this->_getImageZoomLink('ee', $imageSource1),
      'ee_large_image_available2' => $this->_getImageZoomLink('ee', $imageSource2),
      'ee_large_image_available3' => $this->_getImageZoomLink('ee', $imageSource3),
      'ee_norandom' => $noRandom,
      'ee_url' => $extLink,
      'ee_site' => $this->site_id,
      'ee_function_label' => $_LANG["ee_function_{$function}_label"],
      'ee_function_label2' => $_LANG["ee_function_{$function}_label2"],
      'ee_action' => $action,
      'ee_hidden_fields' => $hiddenFields,
      'ee_autocomplete_contentitem_global_url' => $autoCompleteUrl . '&scope=global',
      'ee_module_action_boxes' => $this->_getContentActionBoxes(),
      'ee_campaign_form_attachment' => $this->_parseModuleCampaignFormAttachment($cgAttached),
      'ee_page_assignment' => $this->_parseModulePageAssignment($pageParameter),
      'ee_display_on_info_text' => $this->_getDisplayOnInfoText(!$noRandom, count($this->_readPageAssignments())),
      'ee_employee_data_fields' => $this->_parseModuleFormFields($ignoredFields),
      'ee_employee_data_error' => ($this->_model->getValidationMsg()) ? 1 : 0,
    ), $_LANG2['ee']));

    return array(
      'content'      => $content,
      'content_left' => $this->_getContentLeft(true),
    );
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Show Contents in a List                                                               //
  ///////////////////////////////////////////////////////////////////////////////////////////
  private function _showList()
  {
    global $_LANG, $_LANG2;

    $request = new Input(Input::SOURCE_REQUEST);

    // Initialize filtering
    $listFilterKeys = array_keys($this->_listFilters);
    $filterType = coalesce($request->readString('filter_type'),
                           $this->session->read('ee_filter_type'),
                           $listFilterKeys[0]);
    if (!isset($this->_listFilters[$filterType])) {
      $filterType = $listFilterKeys[0];
    }
    // If filter_text was sent with the request it has to be used, even if it's empty.
    if ($request->exists('ee_filter_text')) {
      $filterText = $request->readString('ee_filter_text');
    }
    else {
      $filterText = coalesce($this->session->read('ee_filter_text'), '');
    }

    if ($filterType == 'location' && $request->exists('location')) {
      $filterId = $request->readInt('location');
    }
    else if ($filterType == 'department' && $request->exists('department')) {
      $filterId = $request->readInt('department');
    }
    else if ($request->exists('filter_id')) {
      $filterId = $request->readInt('filter_id');
    }
    else {
      $filterId = coalesce($this->session->read('ee_filter_id'), 0);
    }
    $this->session->save('ee_filter_type', $filterType);
    $this->session->save('ee_filter_text', $filterText);
    $this->session->save('ee_filter_id', $filterId);
    $filterUrl = "filter_type=$filterType&amp;filter_text=" . urlencode($filterText). "&amp;filter_id=".$filterId;
    $maxLength = ConfigHelper::get('m_mod_filtertext_maxlength');
    $aftertext = ConfigHelper::get('m_mod_filtertext_aftertext');
    $shortFilterText = StringHelper::setText($filterText)
                       ->purge()
                       ->truncate($maxLength, $aftertext)
                       ->getText();
    // Handle filtering
    // Create filter dropdown
    $filterTypeOptions = '';
    foreach (array_keys($this->_listFilters) as $filter)
    {
      $selected = '';
      if ($filterType == $filter) {
        $selected = 'selected="selected"';
      }
      $filterTypeOptions .= '<option value="'.$filter.'" '.$selected.'>'.$_LANG["ee_filter_type_$filter"].'</option>';
    }
    $sqlFilter = '';
    if ($filterText) {
      $sqlFilter = 'AND ' . implode($filterText, $this->_listFilters[$filterType]) . ' ';
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_employee",
                                         'EID', 'EPosition',
                                         'FK_SID', $this->site_id);

    $sqlJoin = '';
    if ($filterId && ($filterType == 'location' || $filterType == 'department')) {
      $sqlJoin = " INNER JOIN {$this->table_prefix}module_employee_attribute "
                . "   ON FK_EID = EID "
                . " INNER JOIN {$this->table_prefix}module_attribute "
                . "   ON FK_AVID = AVID AND AVID = '{$this->db->escape($filterId)}' ";
    }
    // Read employees
    $employee_items = array ();
    $result = $this->_readEmployees($sqlFilter, $sqlJoin);
    while ($row = $this->db->fetch_row($result))
    {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['EPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['EPosition']);

      // Detect invalid and invisible links.
      $internalLink = $this->getInternalLinkHelper($row['FK_CIID']);
      $intLink = '';
      if ($internalLink->isValid()) {
        $intLink = sprintf($_LANG['ee_intlink_link'], $internalLink->getEditUrl(), $internalLink->getHierarchicalTitle("/"));
      }

      $employee_items[] = array_merge($internalLink->getTemplateVars($this->_prefix), array(
        'ee_title1' => parseOutput($row['ETitle1']),
        'ee_text1' => parseOutput($row['EText1']),
        'ee_image_src1' => ($row['EImage1'] ? '../'.$row['EImage1'] : ( $row['EImage2'] ? '../'.$row['EImage2'] : 'img/no_image.png')),
        'ee_id' => $row['EID'],
        'ee_position' => $row['EPosition'],
        'ee_content_link' => "index.php?action=mod_employee&amp;action2=main;edit&amp;site=$this->site_id&amp;page={$row['EID']}&amp;$filterUrl",
        'ee_delete_link' => "index.php?action=mod_employee&amp;deleteEmployeeID={$row['EID']}&amp;$filterUrl",
        'ee_move_up_link' => "index.php?action=mod_employee&amp;site=$this->site_id&amp;moveID={$row['EID']}&amp;moveTo=$moveUpPosition&amp;$filterUrl",
        'ee_move_down_link' => "index.php?action=mod_employee&amp;site=$this->site_id&amp;moveID={$row['EID']}&amp;moveTo=$moveDownPosition&amp;$filterUrl",
        'ee_extlink_link' => $row['EUrl'] ? sprintf($_LANG['ee_extlink_link'], $row['EUrl'], $row['EUrl']) : '',
        'ee_intlink_link' => $intLink,
        'ee_filter_type_select' => $filterTypeOptions,
        'ee_empl_title' => parseOutput($row['ETitle']),
        'ee_empl_firstname' => parseOutput($row['EFirstname']),
        'ee_empl_lastname' => parseOutput($row['ELastname']),
        'ee_empl_email' => parseOutput($row['EEmail']),
      ));
    }
    $this->db->free_result($result);

    if (!$employee_items)
    {
      if ($sqlFilter) {
        $msg = sprintf($_LANG['ee_message_no_employee_with_filter'], $shortFilterText);
      }
      else {
        $msg = $_LANG['ee_message_no_employee'];
      }
      $this->setMessage(Message::createFailure($msg));
    }

    // Create attribute instance
    $attribute = new Attribute($this->db, $this->table_prefix);
    $departments = $attribute->readAttributesByAGlobalIdentifier(AttributeGlobal::ID_EMPLOYEE_DEPARTMENT);
    $locations = $attribute->readAttributesByAGlobalIdentifier(AttributeGlobal::ID_EMPLOYEE_LOCATION);
    // Create department and location field instances
    $departmentsArray = array();
    foreach ($departments as $department) {
      $selected = ($filterType == 'department' && $department->id == $filterId) ? 'selected="selected"' : '';
      $departmentsArray[$department->id] = array(
        'ee_department_id'       => $department->id,
        'ee_department_title'    => parseOutput($department->title),
        'ee_department_selected' => $selected,
      );
    }
    $locationsArray = array();
    foreach ($locations as $location) {
      $selected = ($filterType == 'location' && $location->id == $filterId) ? 'selected="selected"' : '';
      $locationsArray[$location->id] = array(
        'ee_location_id'    => $location->id,
        'ee_location_title' => parseOutput($location->title),
        'ee_location_selected' => $selected,
      );
    }

    if ($filterText) {
      $filterActiveLabel = sprintf($_LANG["ee_filter_active_label"], $_LANG["ee_filter_type_$filterType"],
                                   parseOutput($filterText), parseOutput($shortFilterText));
    }
    else if ($filterId && $filterType == 'department') {
      $filterActiveLabel = sprintf($_LANG["ee_filter_active_label"], $_LANG["ee_filter_type_$filterType"],
                                   $departmentsArray[$filterId]['ee_department_title'],
                                   $departmentsArray[$filterId]['ee_department_title']);
    }
    else if ($filterId && $filterType == 'location') {
      $filterActiveLabel = sprintf($_LANG["ee_filter_active_label"], $_LANG["ee_filter_type_$filterType"],
                                   $locationsArray[$filterId]['ee_location_title'],
                                   $locationsArray[$filterId]['ee_location_title']);
    }
    else {
      $filterActiveLabel = $_LANG["ee_filter_inactive_label"];
    }

    $action = "index.php?action=mod_employee&amp;$filterUrl";
    $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />';
    // Parse the list template.
    $this->tpl->load_tpl('employee', 'modules/ModuleEmployee_list.tpl');
    $this->tpl->parse_if('employee', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ee'));
    $this->tpl->parse_if('employee', 'filter_set', $filterText || $filterId);
    $this->tpl->parse_if('employee', 'filter_set', $filterText || $filterId);
    $this->tpl->parse_loop('employee', $employee_items, 'employee_items');
    $this->tpl->parse_loop('employee', $departmentsArray, 'department_items');
    $this->tpl->parse_loop('employee', $locationsArray, 'location_items');
    $content = $this->tpl->parsereturn('employee', array_merge(array(
      'ee_action'              => $action,
      'ee_departmentfilter_style' => ($filterType == 'department') ? '' : 'display:none;',
      'ee_dragdrop_link_js'    => "index.php?action=mod_employee&site=$this->site_id&moveID=#moveID#&moveTo=#moveTo#",
      'ee_filter_active_label' => $filterActiveLabel,
      'ee_filter_text'         => $filterText,
      'ee_filter_type_options' => $filterTypeOptions,
      'ee_hidden_fields'       => $hiddenFields,
      'ee_list_label'          => $_LANG['ee_function_list_label'],
      'ee_list_label2'         => $_LANG['ee_function_list_label2'],
      'ee_locationfilter_style' => ($filterType == 'location') ? '' : 'display:none;',
      'ee_site_selection'      => $this->_parseModuleSiteSelection('employee', $_LANG['ee_site_label']),
      'ee_textfilter_style'    => ($filterType != 'department' && $filterType != 'location') ? '' : 'display:none;',
    ), $_LANG2['ee']));

    return array(
      'content'      => $content,
      'content_left' => $this->_getContentLeft(),
    );
  }

  private function _readEmployees($sqlFilter, $sqlJoin)
  {
    $sql = 'SELECT EID, ETitle1, EText1, EImage1, EImage2, EPosition, EUrl, '
         . '       ETitle, EFirstname, ELastname, EEmail, '
         . '       ee.FK_CIID, CIID, CTitle, CIIdentifier, c.FK_SID '
         . "FROM {$this->table_prefix}module_employee ee "
         . "LEFT JOIN {$this->table_prefix}contentitem c ON ee.FK_CIID = c.CIID "
         . " {$sqlJoin} "
         . "WHERE ee.FK_SID = '$this->site_id' "
         . "  {$sqlFilter} "
         . 'ORDER BY EPosition ASC ';

    $result = $this->db->query($sql);

    return $result;
  }
}
