<?php

/**
 * class.ModuleHtmlCreator.php
 *
 * $LastChangedDate: 2018-03-01 15:06:01 +0100 (Do, 01 Mrz 2018) $
 * $LastChangedBy: ham $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2012 Q2E GmbH
 */

class ModuleHtmlCreator extends Module
{
  /**
   * The database column prefix.
   *
   * @var string
   */
  protected $_dbColumnPrefix = 'HC';

  /**
   * Module's prefix used for configuration, template
   * and language variables.
   *
   * @var string
   */
  protected $_prefix = 'hc';

  /**
   * Module's model.
   *
   * @var HtmlCreator
   */
  protected $_model = null;

  /**
   * @var ModuleHtmlCreator_Box
   */
  private $_boxInstance;

  /**
   * Shows module's content.
   *
   * @see Module::show_innercontent()
   */
  public function show_innercontent()
  {
    $post = new Input(Input::SOURCE_POST);

    try {
      $this->_deleteImage();

      if (isset($this->action[0])) {
        switch($this->action[0]) {
          case 'copy':
            return $this->_copy();
            break;
          case 'delete':
            return $this->_delete();
            break;
          case 'export_html':
            return $this->_exportHtml();
            break;
          case 'export_zip':
            return $this->_exportZip();
            break;
          case 'new':
            if ($post->exists('process')) {
              return $this->_create();
            }
            else {
              return $this->_showCreateForm();
            }
            break;
          case 'show_html':
            return $this->_showHtml();
            break;
          default:
            if ($this->item_id) {
              if ($post->exists('process')) {
                return $this->_update();
              }
              else {
                return $this->_showForm();
              }
            }
        }
      }
    }
    // show list for runtime exceptions ( thrown by i.e. _getModel() )
    catch(RuntimeException $e) {
      header('Location: index.php?action=mod_htmlcreator&site=' . $this->site_id);
      exit;
    }

    // do not catch runtime exceptions from
    return $this->_showList();
  }

  protected function delete_content_image($module, $table, $primaryColumn, $imageColumn, $number)
  {
    global $_LANG;

    $model = $this->_getModel();

    // Do not really delete image file. Delete only
    // the pointer (path) in the database field.
    // Existing image files are maybe used
    // in a newsletter or elsewhere and should always remain in /img/htmlcreator
    $this->db->query("UPDATE {$this->table_prefix}module_$table SET $imageColumn$number = '' WHERE $primaryColumn = '{$model->id}'");

    $this->_redirect($this->_parseUrl('edit', array('page' => $model->id)),
      Message::createSuccess($_LANG['hc_message_delete_image_success']));
  }

  protected function _getContentLeftLinks()
  {
    $links = parent::_getContentLeftLinks();
    if ($this->item_id) {
      $links[] = array($this->_parseUrl('show_html', array('page' => $this->item_id)),
                       $this->_langVar('moduleleft_show_html_label'));
      $links[] = array($this->_parseUrl('export_html', array('page' => $this->item_id)),
                       $this->_langVar('moduleleft_export_html_label'));
      $links[] = array($this->_parseUrl('export_zip', array('page' => $this->item_id)),
                       $this->_langVar('moduleleft_export_zip_label'));
    }

    return $links;
  }

  /**
   * Creates a duplicate of an item.
   *
   * @return boolean
   *         True on success.
   */
  private function _copy()
  {
    global $_LANG;

    $model = $this->_getModel();
    // 1. Copy the general parts of the item
    $newModel = new HtmlCreator($this->db, $this->table_prefix, $this->_prefix);
    $ignore = array('image1', 'image2', 'image3', 'copiedFromId', 'createDateTime', 'changeDateTime');
    $newModel->setFieldsByModel($model, $ignore);
    $newModel->copiedFromId = $model->id;
    $now = date('Y-m-d H:i:s');
    $newModel->createDateTime = $now;
    $newModel->changeDateTime = $now;
    $newModel->title1 = $newModel->title1 . $_LANG['hc_copy_label'];
    // Create new item
    $newModel->create();
    // Copy images and use new model id to generate images
    $newModel->image1 = ($model->image1) ? CopyHelper::createImage($model->image1, $newModel->id, $this->site_id) : '';
    $newModel->image2 = ($model->image2) ? CopyHelper::createImage($model->image2, $newModel->id, $this->site_id) : '';
    $newModel->image3 = ($model->image3) ? CopyHelper::createImage($model->image3, $newModel->id, $this->site_id) : '';
    // Update model to may save new images
    $newModel->update();

    // 2. Copy boxes of the item
    $newBoxModel = new HtmlCreatorBox($this->db, $this->table_prefix, $this->_prefix);
    $boxModels = $newBoxModel->readHtmlCreatorBoxesFromParent($model->id);
    $ignore = array('image');
    foreach ($boxModels as $boxModel) {
      /* @var $boxModel HtmlCreatorBox */
      $newBoxModel->reset();
      $newBoxModel->setFieldsByModel($boxModel, $ignore);
      $newBoxModel->parentId = $newModel->id;
      // Create new box
      $newBoxModel->create();
      // Copy image and use new box model id to generate images
      $newBoxModel->image = ($boxModel->image) ? CopyHelper::createImage($boxModel->image, $newBoxModel->id, $this->site_id) : '';
      // Update model to may save new image
      $newBoxModel->update();
    }

    $this->_redirect($this->_parseUrl('edit', array('page' => $newModel->id)),
      Message::createSuccess($_LANG[$this->_prefix . '_message_copy_success']));
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

    $model = $this->_getModel();
    // Validate form fields
    if ($model->validate() === false) {
      $this->setMessage($model->getValidationMsg());
      return false;
    }

    $now = date('Y-m-d H:i:s');
    $model->createDateTime = $now;
    $model->changeDateTime = $now;

    // Save model fields
    $model->create();

    // Set the item ID to the inserted item so that the _storeImage
    // method can assign the correct file names to the image files.
    $this->item_id = $model->id;

    // Save images after an id is available
    $image1 = isset($_FILES[$this->_prefix . '_image1']) ? $_FILES[$this->_prefix . '_image1'] : null;
    $image2 = isset($_FILES[$this->_prefix . '_image2']) ? $_FILES[$this->_prefix . '_image2'] : null;
    $image3 = isset($_FILES[$this->_prefix . '_image3']) ? $_FILES[$this->_prefix . '_image3'] : null;
    $image1 = $this->_storeImage($image1, null, $this->_prefix, 1, null, false, false, $this->_getDestinationPrefix(), true, false);
    $image2 = $this->_storeImage($image2, null, $this->_prefix, 2, null, false, false, $this->_getDestinationPrefix(), true, false);
    $image3 = $this->_storeImage($image3, null, $this->_prefix, 3, null, false, false, $this->_getDestinationPrefix(), true, false);
    $model->image1 = ($image1) ? $image1 : '';
    $model->image2 = ($image2) ? $image2 : '';
    $model->image3 = ($image3) ? $image3 : '';

    // Now update the model with may uploaded images
    $model->update();

    $message = $this->_getMessage() ?: Message::createSuccess($_LANG[$this->_prefix . '_message_create_success']);
    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_getBackLinkUrl(), $message);
    }
    else {
      $this->_redirect($this->_parseUrl('edit', array('page' => $model->id)), $message);
    }
  }

  /**
   * Deletes the requested html template
   */
  private function _delete()
  {
    global $_LANG;

    $model = $this->_getModel();
    $model->changeDateTime = date('Y-m-d H:i:s');
    $model->deleted = 1;
    $model->update();

    $this->_redirect($this->_parseUrl(),
      Message::createSuccess($_LANG[$this->_prefix . '_message_delete_success']));
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
    $this->delete_content_image('htmlcreator', 'html_creator', 'HCID', 'HCImage', $imageNumber);
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

    $model = $this->_getModel();
    $this->_setModuleConfigFromSelectedTemplate($model->template);

    // Validate form fields
    if ($model->validate() === false) {
      $this->setMessage($model->getValidationMsg());
      return false;
    }

    $oldModel = $model->readHtmlCreatorItemById($this->item_id);
    // Set model's id to current module's element id (page id)
    $model->id = $this->item_id;
    $model->createDateTime = $oldModel->createDateTime;
    $model->changeDateTime = date('Y-m-d H:i:s');
    // If there is no new image path ($_FILES array does not contain image path),
    // we set the old image path, because we do not want to update (delete) an existing image path
    $image1 = $this->_storeImage($_FILES[$this->_prefix . '_image1'], null, $this->_prefix, 1, null, false, false, $this->_getDestinationPrefix(), true, false);
    $image2 = $this->_storeImage($_FILES[$this->_prefix . '_image2'], null, $this->_prefix, 2, null, false, false, $this->_getDestinationPrefix(), true, false);
    $image3 = $this->_storeImage($_FILES[$this->_prefix . '_image3'], null, $this->_prefix, 3, null, false, false, $this->_getDestinationPrefix(), true, false);
    $model->image1 = ($image1) ? $image1 : $oldModel->image1;
    $model->image2 = ($image2) ? $image2 : $oldModel->image2;
    $model->image3 = ($image3) ? $image3 : $oldModel->image3;
    // Save model fields
    $model->update();

    $message = $this->_getMessage() ?: Message::createSuccess($_LANG[$this->_prefix . '_message_update_success']);
    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_getBackLinkUrl(), $message);
    }
    else {
      $this->_redirect($this->_parseUrl('edit', array('page' => $this->item_id)), $message);
    }
  }

  /**
   * Parses the content of the item into a configured and
   * selected template and exports an HTML file.
   */
  private function _exportHtml()
  {
    // Add export log entry
    $exportLog = new HtmlCreatorExportLog($this->db, $this->table_prefix, $this->_prefix);
    $exportLog->uId = $this->_user->getID();
    $exportLog->parentId = $this->_getModel()->id;
    $exportLog->dateTime = date('Y-m-d H:i:s');
    $exportLog->create();

    $content = $this->_getParsedTemplateContent($this->_getRootUrl());

    // output template as download
    $filename = $this->_getModel()->template . '_'.date('Y-m-d');
    header('Content-Disposition: attachment; filename="' . $filename . '.html"');
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
  }

  /**
   * Show a HTML "preview" in browser. This is not a real preview, as it shows
   * stored data only.
   */
  private function _showHtml()
  {
    echo $this->_getParsedTemplateContent($this->_getRootUrl());
    exit;
  }

  /**
   * Export the HTML content with all image resources as a bundled ZIP file.
   */
  private function _exportZip()
  {
    // Add export log entry
    $exportLog = new HtmlCreatorExportLog($this->db, $this->table_prefix, $this->_prefix);
    $exportLog->uId = $this->_user->getID();
    $exportLog->parentId = $this->_getModel()->id;
    $exportLog->dateTime = date('Y-m-d H:i:s');
    $exportLog->create();

    $content = $this->_getParsedTemplateContent('');
    $templateName = $this->_getConfigValue($this->_getModel()->template, 'template');

    // fetch all image files this template uses
    $files = array();
    preg_match_all('/((pix|img)\/htmlcreator\/(' . $templateName . '\/)?.+\.(jpg|png|gif))/ui', $content, $matches);
    foreach ($matches[0] as $match) {
      $files[] = $match;
    }

    // output template as ZIP download with all resources attached
    $filename = $this->_getModel()->template . '_'.date('Y-m-d');
    $zip = new ZipStream\ZipStream($filename . '.zip');
    $zip->addFile('index.html', $content);
    foreach ($files as $file) {
      $zip->addFileFromPath($file, '../' . $file);
    }
    $zip->finish();
  }

  /**
   * @param string $baseUrl
   *
   * @return string
   */
  private function _getParsedTemplateContent($baseUrl)
  {
    $model = $this->_getModel();
    $template = $model->template;
    $boxModel = $this->_getBoxInstance()->getModel();
    $boxModels = $boxModel->readHtmlCreatorActiveBoxesFromParent($model->id);

    $data = $model->toArray();
    $data['image1'] = $data['image1'] ? $baseUrl . $data['image1'] : '';
    $data['image2'] = $data['image2'] ? $baseUrl . $data['image2'] : '';
    $data['image3'] = $data['image3'] ? $baseUrl . $data['image3'] : '';

    $data['boxes'] = array();
    foreach ($boxModels as $m) {
      $array = $m->toArray();
      $array['image'] = $m->image ? $baseUrl . $m->image : '';

      $data['boxes'][] = $array;
    }

    $data['_'] = array();
    $data['_']['url'] = $baseUrl;

    // parse template
    ob_start();
    include $this->_getHtmlCreatorTemplateFile($template);
    $content = ob_get_contents();
    ob_clean();

    return $content;
  }

  /**
   * Gets the path to the template file to parse the HTML draft into.
   *
   * @param string $selectedTemplate
   *
   * @return string
   */
  private function _getHtmlCreatorTemplateFile($selectedTemplate)
  {
    $templateName = $this->_getConfigValue($selectedTemplate, 'template');

    return ConfigHelper::get('INCLUDE_DIR') . 'templates/htmlcreator/' . $templateName . '.php';
  }

  /**
   * Gets the frontend destination path to store images.
   */
  private function _getDestinationPrefix()
  {
    return 'htmlcreator/hc';
  }

  /**
   * @return ModuleHtmlCreator_Box
   */
  private function _getBoxInstance()
  {
    if ($this->_boxInstance !== null) {
      return $this->_boxInstance;
    }

    $this->_boxInstance = new ModuleHtmlCreator_Box($this->_allSites, $this->site_id, $this->tpl, $this->db, $this->table_prefix,
                                                    $this->action, $this->item_id, $this->_user, $this->session, $this->_navigation,
                                                    $this->originalAction, $this);
    return $this->_boxInstance;
  }

  /**
   * Gets the language labels of the model.
   */
  private function _getFormLabels()
  {
    $labels = array();
    $modelFields = $this->_getModel()->getFields();
    foreach ($modelFields as $name => $field)
    {
      /* @var $field Field */
      if ($field->isHidden()) {
        continue;
      }
      $tplName = $field->getTplName($this->_prefix);
      $labels[$tplName.'_label'] = $this->_getBoxLangValue($this->_getModel()->template, $field->getName());
    }

    return $labels;
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

    $condition = array('order'  => "COALESCE(HCChangeDateTime, HCCreateDateTime) DESC",
                       'where'  => "HCDeleted = 0 AND FK_SID = {$this->site_id} ");
    $models = $this->_getModel()->readHtmlCreatorItems($condition);
    $items = array();
    foreach ($models as $model) {
      /* @var $model HtmlCreator */
      $items[] = array(
        $this->_prefix . '_copy_link'       => "index.php?action=mod_htmlcreator&amp;action2=main;copy&amp;page={$model->id}",
        $this->_prefix . '_delete_link'     => "index.php?action=mod_htmlcreator&amp;action2=main;delete&amp;page={$model->id}",
        $this->_prefix . '_edit_link'       => "index.php?action=mod_htmlcreator&amp;action2=main;edit&amp;page={$model->id}",
        $this->_prefix . '_id'              => $model->id,
        $this->_prefix . '_title'           => $model->title1 ? parseOutput($model->title1) : sprintf($_LANG['hc_list_title_label_undefined'], date('Y-m-d H:i', ContentBase::strToTime($model->createDateTime))),
      );
    }

    if (!$items) {
      $this->setMessage(Message::createFailure($_LANG[$this->_prefix . '_message_no_items']));
    }

    return $items;
  }

  /**
   * Prepares the given text for output.
   * Notice that it does not protect e-mail addresses,
   * because this feature is not needed at the moment.
   *
   * @param string $text
   *
   * @return string
   */
  private function _outputText($text)
  {
    $text = parseOutputAnchorLinks($text);
    $text = parseOutputExternalLinks($text);
    $text = parseOutputInternalLinks($text);
    $text = parseOutputFileLinks($text);

    $text = htmlentities($text, ENT_NOQUOTES, ConfigHelper::get('charset'));
    $text = str_replace('&lt;', '<', $text);
    $text = str_replace('&gt;', '>', $text);
    $text = str_replace('&amp;', '&', $text);
    $text = str_replace('€', '&euro;', $text);

    return $text;
  }

  /**
   * Prepares the given title for output.
   *
   * @param string $text
   *
   * @return string
   */
  private function _outputTitle($title)
  {
    $title = strip_tags($title);
    $title = htmlentities($title, ENT_QUOTES, ConfigHelper::get('charset'));
    $title = str_replace('€', '&euro;', $title);

    return $title;
  }

  /**
   * Shows the form to edit an item.
   *
   * @return array
   */
  private function _showForm()
  {
    global $_LANG, $_LANG2;

    $pfx = $this->_prefix;
    $model = $this->_getModel();

    if ($this->item_id) { // Edit data -> load data
      $function = 'edit';
    }
    else { // New data
      return $this->_showCreateForm();
    }

    $this->_setModuleConfigFromSelectedTemplate($model->template);

    $imageSource1 = $model->image1;
    $imageSource2 = $model->image2;
    $imageSource3 = $model->image3;
    $fieldTemplate = $model->getFieldInstance('template');
    $templateOptions = AbstractForm::selectOptions($fieldTemplate->getPredefined(), $model->template);

    $options = $fieldTemplate->getPredefined();
    $selectedTemplate = $model->template ?: array_pop(array_reverse(array_keys($options)));

    /* @var $title1 Field */
    $title1 = $model->getFieldInstance('title1');
    $title1Msg = $title1->getFirstMsg();
    $title1FailureClass = ($title1->hasValidationErrors()) ? 'm_ff_field_failure' : '';

    $hiddenFields = '<input type="hidden" name="action" value="mod_htmlcreator" />'
                  . '<input type="hidden" name="action2" value="main;'.$function.'" />'
                  . '<input type="hidden" name="page" value="' . $this->item_id . '" />';

    $boxContent = $this->_getBoxInstance()->getContent();
    if ($this->_getBoxInstance()->hasBoxChanged()) {
      $model->changeDateTime = date('Y-m-d H:i:s');
      $model->update();
    }

    // Get warning message, if the maximum number of boxes has been reached
    $subMsg = null;
    if (! $this->_getBoxInstance()->isNewElementPossible()) {
      $subMsg = Message::createFailure($_LANG[$pfx . '_message_max_elements']);
    }

    $scrollToAnchor = isset($_REQUEST['scrollToAnchor']) ? $_REQUEST['scrollToAnchor'] : '';
    if (!$scrollToAnchor && $this->_getBoxInstance()->hasBoxChanged()) {
      $scrollToAnchor = 'a_boxes';
    }

    // First try to get message from ModuleHtmlCreator, then look for a
    // ModuleHtmlCreator_Box message
    if ($this->_getMessage()) {
      $message = $this->_getMessage();
      $messageTemplateArray = $this->_getMessageTemplateArray($pfx);
    } else if ($this->_getBoxInstance()->_getMessage()) {
      $message = $this->_getBoxInstance()->_getMessage();
      $messageTemplateArray = $this->_getBoxInstance()->_getMessageTemplateArray($pfx);
    } else {
      $message = null;
      $messageTemplateArray = array();
    }

    $tplName = 'content_' . $pfx;
    $this->tpl->load_tpl($tplName, 'modules/ModuleHtmlCreator.tpl');
    $this->tpl->parse_if($tplName, 'message', $message, $messageTemplateArray);
    $this->tpl->parse_if($tplName, 'hc_msg_title1', ($title1Msg), array(
      $pfx . '_field_msg_text' => ($title1Msg) ? $title1Msg->getText() : '',
      $pfx . '_field_msg_type' => ($title1Msg) ? $title1Msg->getType() : '',
    ));
    $this->tpl->parse_if($tplName, 'delete_image1', $imageSource1, $this->get_delete_image('htmlcreator', $pfx, 1));
    $this->tpl->parse_if($tplName, 'delete_image2', $imageSource2, $this->get_delete_image('htmlcreator', $pfx, 2));
    $this->tpl->parse_if($tplName, 'delete_image3', $imageSource3, $this->get_delete_image('htmlcreator', $pfx, 3));
    $this->tpl->parse_if($tplName, 'hc_add_subelement', $this->_getBoxInstance()->isNewElementPossible() && $this->item_id);
    $this->tpl->parse_if($tplName, 'sub_message', $subMsg, ($subMsg) ? $subMsg->getTemplateArray($pfx) : array());
    $this->tpl->parse_if($tplName, $pfx . '_boxes_available_msg', !$this->item_id);
    $content = $this->tpl->parsereturn($tplName, array_merge(
      $this->_getUploadedImageDetails($imageSource1, $pfx, $pfx, 1),
      $this->_getUploadedImageDetails($imageSource2, $pfx, $pfx, 2),
      $this->_getUploadedImageDetails($imageSource3, $pfx, $pfx, 3),
      $_LANG2[$pfx],
      $this->_getFormLabels(),
      array (
        $pfx . '_custom_config_styles' => $this->_getCustomConfigStylesFromConfig($selectedTemplate),
        $pfx . '_box_items' => $boxContent,
        $pfx . '_template_options' => $templateOptions,
        $pfx . '_title1_failure' => $title1FailureClass,
        $pfx . '_title1' => $model->title1,
        $pfx . '_title2' => $model->title2,
        $pfx . '_title3' => $model->title3,
        $pfx . '_text1' => $model->text1,
        $pfx . '_text2' => $model->text2,
        $pfx . '_text3' => $model->text3,
        $pfx . '_url' => $model->url,
        $pfx . '_hidden_fields'    => $hiddenFields,
        $pfx . '_function_label'   => $_LANG[$pfx . '_function_'.$function.'_label'],
        $pfx . '_image_alt_label'  => $_LANG['m_image_alt_label'],
        $pfx . '_action'           => 'index.php',
        $pfx . '_action_new_element' => "index.php?action=mod_htmlcreator&action2=main;edit&site=$this->site_id&page=$this->item_id#a_boxes",
        $pfx . '_required_resolution_label1' => $this->_getImageSizeInfo($pfx, 1),
        $pfx . '_required_resolution_label2' => $this->_getImageSizeInfo($pfx, 2),
        $pfx . '_required_resolution_label3' => $this->_getImageSizeInfo($pfx, 3),
        $pfx . '_box_active_position' => $this->_getBoxInstance()->getActivePosition(),
        $pfx . '_box_dragdrop_link_js'  => "index.php?action=mod_htmlcreator&action2=main;edit&site=$this->site_id&page=$this->item_id&moveID=#moveID#&moveTo=#moveTo##a_boxes",
        $pfx . '_scroll_to_anchor' => $scrollToAnchor,
        $pfx . '_new_element_template_options' => $this->_getBoxTemplateTypeOptions($selectedTemplate),
        'module_action_boxes' => $this->_getContentActionBoxes(),
      )
    ));

    return array(
      'content'      => $content,
      'content_left' => $this->_getContentLeft(true),
    );
  }

  /**
   * Shows the form to create an item.
   *
   * @return array
   */
  private function _showCreateForm()
  {
    global $_LANG, $_LANG2;

    $pfx = $this->_prefix;
    $model = $this->_getModel();
    $function = 'new';

    $fieldTemplate = $model->getFieldInstance('template');
    $templateOptions = AbstractForm::selectOptions($fieldTemplate->getPredefined(), $model->template);

    $options = $fieldTemplate->getPredefined();
    $predefined = array_reverse(array_keys($options));
    $selectedTemplate = $model->template ?: array_pop($predefined);

    $hiddenFields = '<input type="hidden" name="action" value="mod_htmlcreator" />'
                  . '<input type="hidden" name="action2" value="main;'.$function.'" />'
                  . '<input type="hidden" name="page" value="0" />';

    // First try to get message from ModuleHtmlCreator, then look for a
    // ModuleHtmlCreator_Box message
    if ($this->_getMessage()) {
      $message = $this->_getMessage();
      $messageTemplateArray = $this->_getMessageTemplateArray($pfx);
    } else if ($this->_getBoxInstance()->_getMessage()) {
      $message = $this->_getBoxInstance()->_getMessage();
      $messageTemplateArray = $this->_getBoxInstance()->_getMessageTemplateArray($pfx);
    } else {
      $message = null;
      $messageTemplateArray = array();
    }

    $tplName = 'content_' . $pfx;
    $this->tpl->load_tpl($tplName, 'modules/ModuleHtmlCreator_new.tpl');
    $this->tpl->parse_if($tplName, 'message', $message, $messageTemplateArray);

    $content = $this->tpl->parsereturn($tplName, array_merge(
      $_LANG2[$pfx],
      array (
        $pfx . '_custom_config_styles'         => '<style>.display_hc_template{display:block;visibility:visible}</style>',
        $pfx . '_template_options'             => $templateOptions,
        $pfx . '_hidden_fields'                => $hiddenFields,
        $pfx . '_function_label'               => $_LANG[$pfx . '_function_' . $function . '_label'],
        $pfx . '_action'                       => 'index.php',
        $pfx . '_new_element_template_options' => $this->_getBoxTemplateTypeOptions($selectedTemplate),
        'module_action_boxes'                  => $this->_getContentActionBoxes(),
      )
    ));

    return array(
      'content'      => $content,
      'content_left' => $this->_getContentLeft(true),
    );
  }

  /**
   * Shows a list of available items.
   *
   * @return array
   */
  private function _showList()
  {
    global $_LANG, $_LANG2;

    // Read items
    $items = $this->_getListLoopArray();
    // Parse the list template.
    $tplName = 'content_' . $this->_prefix;
    $this->tpl->load_tpl($tplName, 'modules/ModuleHtmlCreator_list.tpl');
    $this->tpl->parse_if($tplName, 'message', $this->_getMessage(), $this->_getMessageTemplateArray($this->_prefix));
    $this->tpl->parse_if($tplName, 'items_available', $items);
    $this->tpl->parse_loop($tplName, $items, 'items');
    $content = $this->tpl->parsereturn($tplName, array_merge(array(
      $this->_prefix . '_action'           => 'index.php?action=mod_htmlcreator',
      $this->_prefix . '_list_label'       => $_LANG[$this->_prefix . '_function_list_label'],
      $this->_prefix . '_site_selection'   => $this->_parseModuleSiteSelection('htmlcreator', $_LANG['hc_site_label'], 'main'),
    ), $_LANG2[$this->_prefix]));

    return array(
      'content'      => $content,
      'content_left' => $this->_getContentLeft(),
    );
  }

  /**
   * @return HtmlCreator
   */
  private function _getModel()
  {
    if ($this->_model === null) {
      $pfx = $this->_prefix;
      $post = new Input(Input::SOURCE_POST);
      $model = new HtmlCreator($this->db, $this->table_prefix, $pfx);
      if ((int)$this->item_id) {
        $models = $model->read('HCID = ' . (int)$this->item_id . ' AND FK_SID = ' . (int)$this->site_id);
        $this->_model = $models->first();

        if (!$this->_model) {
          throw new RuntimeException("Could not find model with id '" . (int)$this->item_id . "'.");
        }
      }
      else {
        $this->_model = $model;
        $this->_model->siteId = $this->site_id;
      }
      // Set template chooser options from configuration
      $fieldTemplate = $this->_model->getFieldInstance('template');
      $fieldTemplate->setPredefined($this->_getAvailableTemplatesFromConfig());

      // Set fields here. This allows all other methods to access model's newest field values.
      // Methods which want to access the original data values must create a new model with its id.
      if ($post->exists('process')) {
        $this->_model->title1 = $post->readString($pfx . '_title1');
        $this->_model->title2 = $post->readString($pfx . '_title2');
        $this->_model->title3 = $post->readString($pfx . '_title3');
        $this->_model->text1 = $post->readString($pfx . '_text1', Input::FILTER_CONTENT_TEXT);
        $this->_model->text2 = $post->readString($pfx . '_text2', Input::FILTER_CONTENT_TEXT);
        $this->_model->text3 = $post->readString($pfx . '_text3', Input::FILTER_CONTENT_TEXT);
        $this->_model->url = $post->readString($pfx . '_url');
        if ($post->exists($pfx . '_template')) {
          $templateValue = $post->readString($pfx . '_template');
          $fieldTemplate->setValue($templateValue);
        }
      }
    }

    return $this->_model;
  }

  /**
   * @param string $selectedTemplate
   *
   * @return string
   */
  private function _getCustomConfigStylesFromConfig($selectedTemplate)
  {
    $config = ConfigHelper::get('hc_config', null, $this->site_id);

    $styles = '<style>';
    $styles.= '.display_hc_template{display:block;visibility:visible}' . "\n";

    if (isset($config[$selectedTemplate]) && isset($config[$selectedTemplate]['fields'])) {

      foreach ($config[$selectedTemplate]['fields'] as $fieldname) {
        $styles .= '.display_' . $this->_prefix . '_' . $fieldname . '{display:block;visibility:visible;}' . "\n";
      }

    }

    if (isset($config[$selectedTemplate]) && isset($config[$selectedTemplate]['boxes'])) {
      $styles .= '.display_' . $this->_prefix . '_boxes{display:block;visibility:visible;}' . "\n";
    }

    $styles .= '</style>';

    return $styles;
  }

  /**
   * @return array
   */
  private function _getAvailableTemplatesFromConfig()
  {
    $templates = array();
    $config = ConfigHelper::get('hc_config', null, $this->site_id);

    foreach ($config as $templateSet => $settings) {
      $templates[$templateSet] = $settings['label'];
    }

    return $templates;
  }

  /**
   * @param string $selectedTemplate
   */
  private function _setModuleConfigFromSelectedTemplate($selectedTemplate)
  {
    $config = $this->_getConfigValue($selectedTemplate, 'config');

    if (is_array($config)) {

      if (isset($config['image']) && is_array($config['image'])) {
        foreach ($config['image'] as $number => $image) {
          $number = $number > 0 ? $number : '';

          ConfigHelper::set('hc_image_width' . $number, $image['width']);
          ConfigHelper::set('hc_image_height' . $number, $image['height']);
          ConfigHelper::set('hc_large_image_width' . $number, $image['width']);
          ConfigHelper::set('hc_large_image_height' . $number, $image['height']);
        }
      }

      if (isset($config['number_of_boxes'])) {
        ConfigHelper::set('hc_number_of_boxes', $config['number_of_boxes']);
      }
    }
  }

  /**
   * @param string $selectedTemplate
   *
   * @return string
   */
  private function _getBoxTemplateTypeOptions($selectedTemplate)
  {
    $boxes = $this->_getConfigValue($selectedTemplate, 'boxes');

    $options = '';
    if (is_array($boxes)) {
      foreach ($boxes as $name => $settings) {
        $options .= '<option value="' . $selectedTemplate . '.' . $name . '">' . $settings['label'] . '</option>';
      }
    }

    return $options;
  }

  /**
   * @param string $selectedTemplate
   * @param string $name
   *
   * @return string
   */
  private function _getBoxLangValue($selectedTemplate, $name)
  {
    $config = $this->_getConfigValue($selectedTemplate, 'lang');

    return is_array($config) && isset($config[$name]) ? $config[$name] : '';
  }

  /**
   * @param string $selectedTemplate
   * @param string $name
   *
   * @return mixed | null
   */
  private function _getConfigValue($selectedTemplate, $name)
  {
    $config = ConfigHelper::get('hc_config', null, $this->site_id);
    $config = $config[$selectedTemplate];

    return isset($config[$name]) ? $config[$name] : null;
  }

  /**
   * @return string
   */
  private function _getRootUrl()
  {
    if (mb_strpos(root_url(), 'edwin/../') !== false) {
      return mb_substr(root_url(), 0, mb_strpos(root_url(), 'edwin/../'));
    }
    else {
      return root_url();
    }
  }
}
