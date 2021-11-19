<?php
/**
 * $LastChangedDate: 2019-05-10 11:58:32 +0200 (Fr, 10 Mai 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2015 Q2E GmbH
 */

class ModulePopUpManagement extends Module
{
  protected $_dbColumnPrefix = 'PU';

  /**
   * @var string
   */
  protected $_prefix = 'pu';

  /**
   * @var Input
   * @see ModulePopUpManagement::_input
   */
  protected $_input;

  /**
   * Module's model.
   *
   * @var PopUp
   */
  protected $_model;

  /**
   * @var ModelList
   */
  protected $_popUpOptions;

  /**
   * @var BaseForm
   */
  protected $_popUpOptionsForm;

  /**
   * {@inheritdoc}
   */
  public function show_innercontent()
  {
    $this->_create();
    $this->_update();
    $this->_delete();
    $this->_deleteImage();
    $this->_changeActivation();

    if (!empty($this->action[0])) {
      return $this->_showForm();
    }
    else {
      return $this->_showList();
    }
  }

  /**
   * @return array
   */
  private function _showList()
  {
    global $_LANG, $_LANG2;

    // Read items
    $items = $this->_getListLoopArray();
    // Parse the list template.
    $tplName = 'content_' . $this->_prefix;
    $this->tpl->load_tpl($tplName, 'modules/ModulePopUp_list.tpl');
    $this->tpl->parse_if($tplName, 'message', (bool) $this->_getMessage(), $this->_getMessageTemplateArray($this->_prefix));
    $this->tpl->parse_if($tplName, 'items_available', (bool) $items);
    $this->tpl->parse_loop($tplName, $items, 'items');
    $content = $this->tpl->parsereturn($tplName, array_merge(array(
      $this->_prefix . '_action'           => 'index.php?action=mod_popup',
      $this->_prefix . '_site_selection'   => $this->_parseModuleSiteSelection('popup', $_LANG[$this->_prefix . '_site_label']),
      $this->_prefix . '_list_label'       => $_LANG[$this->_prefix . '_function_list_label'],
      $this->_prefix . '_list_label2'      => $_LANG[$this->_prefix . '_function_list_label2'],
    ), $_LANG2[$this->_prefix]));

    return array(
      'content'      => $content,
      'content_left' => $this->_getContentLeft(),
    );
  }

  /**
   * Gets all list items ready to parse into a
   * loop template.
   *
   * @return array
   */
  private function _getListLoopArray()
  {
    global $_LANG;

    $condition = array('where'  => "FK_SID = {$this->db->escape($this->site_id)} ",
                       'order'  => "COALESCE(PUChangeDateTime, PUCreateDateTime) DESC");
    $models = $this->_getModel()->readPopUpItems($condition);
    $items = array();
    foreach ($models as $popUp) {
      /* @var $popUp PopUp */
      $activationLight = $this->_getActivationLight($popUp);
      $items[] = array(
        $this->_prefix . '_delete_link'     => "index.php?action=mod_popup&amp;deleteID={$popUp->id}",
        $this->_prefix . '_edit_link'       => "index.php?action=mod_popup&amp;action2=main;edit&amp;page={$popUp->id}&amp;site={$popUp->siteId}",
        $this->_prefix . '_activation_light_link'  => $this->_getActivationLink($popUp),
        $this->_prefix . '_activation_light'       => $activationLight,
        $this->_prefix . '_activation_light_label' => $_LANG['global_activation_light_'.$activationLight.'_label'],
        $this->_prefix . '_id'              => $popUp->id,
        $this->_prefix . '_title1'          => parseOutput($popUp->title1),
        $this->_prefix . '_title2'          => parseOutput($popUp->title2),
        $this->_prefix . '_title3'          => parseOutput($popUp->title3),
      );
    }

    if (!$items) {
      $this->setMessage(Message::createFailure($_LANG[$this->_prefix . '_message_no_items']));
    }

    return $items;
  }

  /**
   * Returns the activation link for a box.
   *
   * @param PopUp $model
   *
   * @return string
   */
  private function _getActivationLink(PopUp $model)
  {
    $activationLightLink = "index.php?action=mod_popup"
      . "&site=$this->site_id"
      . "&changeActivationBoxID={$model->id}&changeActivationBoxTo=";
    if ($model->isDisabled()) {
      $activationLightLink .= ContentBase::ACTIVATION_ENABLED;
    }
    else {
      $activationLightLink .= ContentBase::ACTIVATION_DISABLED;;
    }

    return $activationLightLink;
  }

  /**
   * Returns the activation light for an item.
   *
   * @param PopUp $model
   *
   * @return string
   *         The activation light string i.e. yellow, green, clock, ...
   */
  private function _getActivationLight(PopUp $model)
  {
    if ($model->isDisabled()) {
      $activationLight = ActivationLightInterface::RED;
    }
    else {
      $activationLight = ActivationLightInterface::GREEN;
    }

    return $activationLight;
  }

  /**
   * Shows the form to edit or create an item.
   *
   * @return array
   */
  private function _showForm()
  {
    global $_LANG, $_LANG2;

    $pfx = $this->_prefix;
    $popUp = $this->_getModel();

    if ($this->item_id) { // Edit data -> load data
      $function = 'edit';
    }
    else { // New data
      $function = 'new';
    }

    $imageSource1 = $popUp->image1;
    $imageSource2 = $popUp->image2;
    $imageSource3 = $popUp->image3;

    $hiddenFields = '<input type="hidden" name="action" value="mod_popup" />'
      . '<input type="hidden" name="action2" value="main;'.$function.'" />'
      . '<input type="hidden" name="page" value="' . $this->item_id . '" />';

    if ($this->_getMessage()) {
      $message = $this->_getMessage();
      $messageTemplateArray = $this->_getMessageTemplateArray($pfx);
    } else {
      $message = null;
      $messageTemplateArray = array();
    }

    $optionsForm = $this->_getOptionsForm();
    $optionsFormVariablesForLoop = $optionsForm->getTemplateVariablesForLoop();

    $tplName = 'content_' . $pfx;
    $this->tpl->load_tpl($tplName, 'modules/ModulePopUp.tpl');
    $this->tpl->parse_if($tplName, 'message', (bool) $message, $messageTemplateArray);
    $this->tpl->parse_if($tplName, 'delete_image1', $imageSource1, $this->get_delete_image('popup', $pfx, 1));
    $this->tpl->parse_if($tplName, 'delete_image2', $imageSource2, $this->get_delete_image('popup', $pfx, 2));
    $this->tpl->parse_if($tplName, 'delete_image3', $imageSource3, $this->get_delete_image('popup', $pfx, 3));
    $this->tpl->parse_if($tplName, 'display_behaviour_info_text', $this->item_id);
    $this->tpl->parse_loop($tplName, $optionsFormVariablesForLoop, 'options');
    $this->tpl->parse_if($tplName, 'options_available', (bool) $optionsFormVariablesForLoop);
    $this->tpl->parse_if($tplName, 'item_is_edited', $this->item_id);
    $content = $this->tpl->parsereturn($tplName, array_merge(
      $this->_getUploadedImageDetails($imageSource1, $pfx, $pfx, 1),
      $this->_getUploadedImageDetails($imageSource2, $pfx, $pfx, 2),
      $this->_getUploadedImageDetails($imageSource3, $pfx, $pfx, 3),
      $_LANG2[$pfx],
      $this->_getFormLabels(),
      $this->getInternalLinkHelper($popUp->linkId)->getTemplateVars($pfx),
      array (
        $pfx . '_title1'  => $popUp->title1,
        $pfx . '_title2'  => $popUp->title2,
        $pfx . '_title3'  => $popUp->title3,
        $pfx . '_text1'   => $popUp->text1,
        $pfx . '_text2'   => $popUp->text2,
        $pfx . '_text3'   => $popUp->text3,
        $pfx . '_link_id' => $popUp->linkId,
        $pfx . '_url'     => $popUp->url,
        $pfx . '_hidden_fields'    => $hiddenFields,
        $pfx . '_function_label'   => $_LANG[$pfx . '_function_'.$function.'_label'],
        $pfx . '_function_label2'  => $_LANG[$pfx . '_function_'.$function.'_label2'],
        $pfx . '_image_alt_label'  => $_LANG['m_image_alt_label'],
        $pfx . '_action'           => "index.php?action=mod_popup&amp;action2=main;$function&amp;site=$this->site_id&amp;page=$this->item_id",
        $pfx . '_required_resolution_label1' => $this->_getImageSizeInfo($pfx, 1),
        $pfx . '_required_resolution_label2' => $this->_getImageSizeInfo($pfx, 2),
        $pfx . '_required_resolution_label3' => $this->_getImageSizeInfo($pfx, 3),
        $pfx . '_large_image_available1' => $this->_getImageZoomLink($pfx, $imageSource1),
        $pfx . '_large_image_available2' => $this->_getImageZoomLink($pfx, $imageSource2),
        $pfx . '_large_image_available3' => $this->_getImageZoomLink($pfx, $imageSource3),
        $pfx . '_page_assignment' => $this->_parseModulePageAssignment(array('action2' => 'main;edit')),
        $pfx . '_display_on_info_text' => $this->_getDisplayOnInfoText(false, count($this->_readPageAssignments())),
        $pfx . '_site' => $this->site_id,
        $pfx . '_autocomplete_contentitem_global_url' => 'index.php?action=mod_response_popup&site=' . $this->site_id . '&request=ContentItemAutoComplete&scope=global',
        'module_action_boxes' => $this->_getContentActionBoxes(),
      )
    ));

    return array(
      'content'      => $content,
      'content_left' => $this->_getContentLeft(true),
    );
  }

  /**
   * @return BaseForm
   */
  private function _getOptionsForm()
  {
    if ($this->_popUpOptionsForm !== null) {
      return $this->_popUpOptionsForm;
    }

    $configOptions = ConfigHelper::get('options', $this->_prefix, $this->site_id);
    $builder = new BaseFormBuilder();
    $this->_popUpOptionsForm = $builder->build(array('fields' => $configOptions))
                                       ->setPrefix($this->_prefix);

    $popUpOptions = $this->_getPopUpOptions();
    foreach ($this->_popUpOptionsForm->getFields() as $field) {
      /* @var $field BaseFormField */
      $popUpOption = PopUpOption::searchKeyInList($field->getName(), $popUpOptions);

      if (!$popUpOption) {
        continue;
      }

      $value = explode("%$%", $popUpOption->value) ?: '';

      if ($field->getType() !== InterfaceField::TYPE_CHECKBOXGROUP && $value) {
        $value = $value[0];
      }

      // Cast select values to integer (required for strict comparision in AbstractForm::selectOptions)
      if ($field->getType() === InterfaceField::TYPE_SELECT) {
        $value = (int) $value;
      }

      $field->setValue($value);
    }

    return $this->_popUpOptionsForm;
  }

  /**
   * Gets the language labels of the model.
   */
  private function _getFormLabels()
  {
    global $_LANG;

    $labels = array();
    $modelFields = $this->_getModel()->getFields();
    foreach ($modelFields as $field)
    {
      /* @var $field Field */
      if ($field->isHidden()) {
        continue;
      }
      $tplName = $field->getTplName($this->_prefix);
      $labels[$tplName.'_label'] = $_LANG[$tplName.'_label'];
    }

    return $labels;
  }

  /**
   * @return PopUp
   */
  private function _getModel()
  {
    if ($this->_model === null) {
      $pfx = $this->_prefix;
      $popUp = new PopUp($this->db, $this->table_prefix, $pfx);
      if ((int)$this->item_id) {
        $this->_model = $popUp->readPopUpItemById($this->item_id);
      }
      else {
        $this->_model = $popUp;
      }

      // Set fields here. This allows all other methods to access model's newest field values.
      // Methods which want to access the original data values must create a new model with its id.
      if ($this->_input()->exists('process')) {
        list($link, $this->_model->linkId) = $this->_input()->readContentItemLink($pfx . "_link");
        $this->_model->url = $this->_input()->readString($pfx . "_url", Input::FILTER_PLAIN);
        $this->_model->title1 = $this->_input()->readString($pfx . '_title1');
        $this->_model->title2 = $this->_input()->readString($pfx . '_title2');
        $this->_model->title3 = $this->_input()->readString($pfx . '_title3');
        $this->_model->text1 = $this->_input()->readString($pfx . '_text1', Input::FILTER_CONTENT_TEXT);
        $this->_model->text2 = $this->_input()->readString($pfx . '_text2', Input::FILTER_CONTENT_TEXT);
        $this->_model->text3 = $this->_input()->readString($pfx . '_text3', Input::FILTER_CONTENT_TEXT);
      }
    }

    return $this->_model;
  }

  /**
   * @return ModelList
   */
  private function _getPopUpOptions()
  {
    if ($this->_popUpOptions !== null) {
      return $this->_popUpOptions;
    }

    $popUpOption = new PopUpOption($this->db, $this->table_prefix, $this->_prefix);
    if ((int)$this->item_id) {
      $this->_popUpOptions = $popUpOption->readWithPopUpId($this->item_id);
    }
    else {
      $this->_popUpOptions = new ModelList(array($popUpOption));
    }

    return $this->_popUpOptions;
  }

  /**
   * @return Input
   */
  private function _input()
  {
    if ($this->_input === null) {
      $this->_input = new Input(Input::SOURCE_REQUEST);
    }

    return $this->_input;
  }

  /**
   * Gets the frontend destination path to store images.
   */
  private function _getDestinationPrefix()
  {
    return 'pu';
  }

  /**
   * Creates an item.
   *
   * @return boolean
   *         True on success.
   */
  private function _create()
  {
    global $_LANG;

    if (!$this->_input()->exists('process') || $this->action[0] != 'new') {
      return false;
    }
    $pfx = $this->_prefix;
    $optionsForm = $this->_getOptionsForm()->parse();
    $popUp = $this->_getModel();
    // Validate form fields
    if ($popUp->validate() === false) {
      $this->setMessage($popUp->getValidationMsg());
      return false;
    }
    $now =  date('Y-m-d H:i:s');
    $popUp->createDateTime = $now;
    $popUp->changeDateTime = $now;
    $popUp->siteId = $this->site_id;
    // If there is no new image path ($_FILES array does not contain image path),
    // we set the old image path, because we do not want to update (delete) an existing image path
    $popUp->image1 = isset($_FILES[$pfx . '_image1']) ? $this->_storeImage($_FILES[$pfx . '_image1'], null, $pfx, 1, null, false, false, $this->_getDestinationPrefix(), true, false) : null;
    $popUp->image2 = isset($_FILES[$pfx . '_image2']) ? $this->_storeImage($_FILES[$pfx . '_image2'], null, $pfx, 2, null, false, false, $this->_getDestinationPrefix(), true, false) : null;
    $popUp->image3 = isset($_FILES[$pfx . '_image3']) ? $this->_storeImage($_FILES[$pfx . '_image3'], null, $pfx, 3, null, false, false, $this->_getDestinationPrefix(), true, false) : null;
    // Save model fields
    $popUp->create();

    // Create options
    $optionFields = $optionsForm->getFields();
    foreach ($optionFields as $field) {
      /* @var $field BaseFormField */
      $value = is_array($field->getValue()) ? implode('%$%', $field->getValue()) : $field->getValue();
      $popUpOption = new PopUpOption($this->db, $this->table_prefix);
      $popUpOption->key = $field->getName();
      $popUpOption->value = $value;
      $popUpOption->popUpId = $popUp->id;
      $popUpOption->create();
    }

    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_getBackLinkUrl(),
        Message::createSuccess($_LANG[$pfx . '_message_create_success']));
    }
    else {
      $this->_redirect($this->_parseUrl('edit', array('page' => $popUp->id)),
        Message::createSuccess($_LANG[$pfx . '_message_create_success']));
    }

    return true;
  }

  /**
   * Updates an item.
   *
   * @return boolean
   *         True on success.
   */
  private function _update()
  {
    global $_LANG;

    if (!$this->_input()->exists('process') || $this->action[0] != 'edit') {
      return false;
    }
    $pfx = $this->_prefix;
    $optionsForm = $this->_getOptionsForm()->parse();
    $popUp = $this->_getModel();

    // Validate form fields
    if ($popUp->validate() === false) {
      $this->setMessage($popUp->getValidationMsg());
      return false;
    }
    if (!Validation::isEmpty($popUp->url) && !Validation::isUrl($popUp->url)) {
      $this->setMessage(Message::createFailure($_LANG[$pfx . '_message_invalid_extlink']));
      return false;
    }

    $oldModel = $popUp->readPopUpItemById($this->item_id);
    // Set model's id to current module's element id (page id)
    $popUp->id = $this->item_id;
    $popUp->createDateTime = $oldModel->createDateTime;
    $popUp->changeDateTime = date('Y-m-d H:i:s');
    $image1 = @$this->_storeImage($_FILES[$pfx . '_image1'], $oldModel->image1, $pfx, 1, null, false, false, $this->_getDestinationPrefix(), true, false);
    $image2 = @$this->_storeImage($_FILES[$pfx . '_image2'], $oldModel->image2, $pfx, 2, null, false, false, $this->_getDestinationPrefix(), true, false);
    $image3 = @$this->_storeImage($_FILES[$pfx . '_image3'], $oldModel->image3, $pfx, 3, null, false, false, $this->_getDestinationPrefix(), true, false);
    $popUp->image1 = ($image1) ? $image1 : $oldModel->image1;
    $popUp->image2 = ($image2) ? $image2 : $oldModel->image2;
    $popUp->image3 = ($image3) ? $image3 : $oldModel->image3;
    // Save model fields
    $popUp->update();

    // Update options
    $optionFields = $optionsForm->getFields();
    foreach ($optionFields as $field) {
      /* @var $field BaseFormField */
      $popUpOption = new PopUpOption($this->db, $this->table_prefix);
      $key = $field->getName();
      $value = is_array($field->getValue()) ? implode('%$%', $field->getValue()) : $field->getValue();
      $popUpOption = $popUpOption->readWithKeyAndPopUpId($key, $popUp->id);
      $popUpOption->key = $key;
      $popUpOption->value = $value;
      $popUpOption->popUpId = $popUp->id;
      if ($popUpOption->id) {
        $popUpOption->update();
      }
      else {
        $popUpOption->create();
      }
    }

    $message = $this->_getMessage() ?: Message::createSuccess($_LANG[$this->_prefix . '_message_update_success']);
    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_getBackLinkUrl(), $message);
    }
    else {
      $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)), $message);
    }

    return true;
  }

  /**
   * @return bool
   */
  private function _changeActivation()
  {
    global $_LANG;

    $id = $this->_input()->readInt('changeActivationBoxID');
    $type = $this->_input()->readString('changeActivationBoxTo', Input::FILTER_NONE);

    if (!$id || !$type) {
      return false;
    }

    switch ( $type ) {
      case ContentBase::ACTIVATION_ENABLED:
        $to = 0;
        break;
      case ContentBase::ACTIVATION_DISABLED:
        $to = 1;
        break;
      default: return false; // invalid activation status
    }

    $pfx = $this->_prefix;
    $popUp = $this->_getModel()->readPopUpItemById($id);
    $popUp->disabled = $to;
    $popUp->changeDateTime = date('Y-m-d H:i:s');
    $popUp->update();

    $this->setMessage(Message::createSuccess($_LANG[$pfx . '_message_activation_' . $type]));

    return true;
  }

  /**
   * @return bool
   */
  private function _delete()
  {
    global $_LANG;

    $id = $this->_input()->readInt('deleteID');
    if (!$id) {
      return false;
    }

    // Read pop-up model.
    $popUp = $this->_getModel()->readPopUpItemById($id);

    // Delete images.
    $sql = 'SELECT PUImage1, PUImage2, PUImage3 '
         . "FROM {$this->table_prefix}module_popup "
         . "WHERE PUID = {$this->db->escape($id)} ";
    $images = $this->db->GetRow($sql);
    self::_deleteImageFiles($images);

    // Delete pop-up.
    $popUp->delete();

    $this->setMessage(Message::createSuccess($_LANG[$this->_prefix . '_message_delete_success']));
    return true;
  }

  /**
   * Reads the image number from a get parameter
   * and deletes the image.
   */
  private function _deleteImage()
  {
    $get = new Input(Input::SOURCE_GET);

    $imageNumber = $get->readInt('dimg');
    if (!$imageNumber) {
      return;
    }
    $this->delete_content_image('popup', 'popup', 'PUID', 'PUImage', $imageNumber);
  }

  /**
   * Deletes an image.
   * @see Module::delete_content_image()
   */
  protected function delete_content_image($module, $table, $key, $col, $number) {
    global $_LANG;
    $image = $this->db->GetOne("SELECT $col$number FROM {$this->table_prefix}module_$table WHERE $key = $this->item_id");
    if ($image) {
      self::_deleteImageFiles($image);
      $this->db->query("UPDATE {$this->table_prefix}module_$table SET $col$number = '' WHERE $key = $this->item_id");
    }

    $this->setMessage(Message::createSuccess($_LANG['pu_message_delete_image_success']));
  }
}