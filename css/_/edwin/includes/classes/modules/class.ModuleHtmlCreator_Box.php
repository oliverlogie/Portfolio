<?php

/**
 * class.ModuleHtmlCreator_Box.php
 *
 * Manages boxes of html creator drafts.
 * Boxes are not shown if a new html draft
 * has not been saved yet.
 *
 * $LastChangedDate: 2018-03-01 15:11:51 +0100 (Do, 01 Mrz 2018) $
 * $LastChangedBy: ham $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2012 Q2E GmbH
 */

class ModuleHtmlCreator_Box extends Module
{
  /**
   * The database column prefix.
   *
   * @var string
   */
  protected $dbColumnPrefix = 'HCB';

  /**
   * Module's prefix used for configuration, template
   * and language variables.
   *
   * @var string
   */
  protected $_prefix = 'hc_box';

  /**
   * Module's model.
   *
   * @var HtmlCreatorBox
   */
  protected $_model = null;

  /**
   * @var ModuleHtmlCreator
   */
  private $_parent;

  private $_boxChanged = false;

  public function __construct($allSites, $site_id, Template $tpl, db $db, $table_prefix, $action = '', $item_id = '',
                              User $user = null, Session $session = null, Navigation $navigation, $originalAction = '',
                              ModuleHtmlCreator $parent)
  {
    $action = (is_array($action)) ? implode(';', $action) : $action;
    parent::__construct($allSites, $site_id, $tpl, $db, $table_prefix, $action, $item_id, $user, $session, $navigation, $originalAction = '');

    $this->_parent = $parent;
  }

  /**
   * Gets the maximal number of possible
   * boxes (from configuration).
   *
   * @return number
   */
  public function maxElements()
  {
    if (ConfigHelper::get('hc_number_of_boxes')) {
      return ConfigHelper::get('hc_number_of_boxes');
    }

    return 0;
  }

  /**
   * Gets the number of boxes for this item.
   *
   * @return number
   */
  public function getNumberOfBoxes()
  {
    $model = $this->getModel();
    $numberOfBoxes = (int) $model->readNumberOfBoxesWithParentId($this->item_id);

    return $numberOfBoxes;
  }

  /**
   * Checks if it is possible to create
   * a new box.
   *
   * @return boolean
   */
  public function isNewElementPossible()
  {
    if ($this->getNumberOfBoxes() < $this->maxElements()) {
      return true;
    }

    return false;
  }

  /**
   * Returns true if a box has been changed (edited,
   * deleted, moved and so on)
   *
   * @return boolean
   */
  public function hasBoxChanged()
  {
    return $this->_boxChanged;
  }

  /**
   * Gets the position of the box, that
   * was recently modified.
   *
   * @return number
   */
  public function getActivePosition()
  {
    $req = new Input(Input::SOURCE_REQUEST);
    $boxModels = $this->getModel()->readHtmlCreatorBoxesFromParent($this->item_id);
    $activePosition = 0;
    foreach ($boxModels as $model) {
      if ($req->exists('hc_box') && $model->id == $req->readInt('hc_box')) {
        return $model->position;
      }
    }

    return $activePosition;
  }

  /**
   * Gets the full content (all boxes) and
   * is also the entry point of this class.
   *
   * @return string
   */
  public function getContent()
  {
    global $_LANG2;

    // An existing item is required to manage boxes
    if (!$this->item_id) {
      return '';
    }

    $this->_create();
    $this->_update();
    $this->_move();
    $this->_delete();
    $this->_deleteImage();

    $pfx = $this->_prefix;
    $boxItems = $this->_getBoxes();
    $tplName = 'content_' . $pfx;
    $activePosition = $this->getActivePosition();
    $this->tpl->load_tpl($tplName, 'modules/ModuleHtmlCreator_box.tpl');
    $this->tpl->parse_loop($tplName, $boxItems, 'box_items');
    foreach ($boxItems as $item) {
      $position = $item[$pfx . '_position'];
      $id = $item[$pfx . '_id'];
      $this->tpl->parse_if($tplName, "message{$position}", $position == $activePosition && $this->_getMessage(), $this->_getMessageTemplateArray($pfx));
      $this->tpl->parse_if($tplName, "delete_image{$position}", $item[$pfx . '_image'], array(
        $pfx . '_delete_image_link' => "index.php?action=mod_htmlcreator&action2=edit&amp;site={$this->site_id}&amp;page={$this->item_id}&amp;box={$id}&amp;deleteBoxImage={$id}",
      ));
    }
    $content = $this->tpl->parsereturn($tplName, array_merge($_LANG2[$this->_parent->getPrefix()], array()));

    return $content;
  }

  /**
   * @return HtmlCreatorBox
   */
  public function getModel()
  {
    if ($this->_model === null) {
      $model = new HtmlCreatorBox($this->db, $this->table_prefix, $this->_prefix);
      $this->_model = $model;
    }
    return $this->_model;
  }

  protected function delete_content_image($module, $table, $primaryColumn, $imageColumn, $number)
  {
    throw new Exception(get_class($this) . '::delete_content_image() is not available here.');
  }

  /**
   * Gets all boxes of this item.
   *
   * @return array
   */
  private function _getBoxes()
  {
    global $_LANG;

    $pfx = $this->_prefix;
    $boxItems = array();
    $boxModels = $this->getModel()->readHtmlCreatorBoxesFromParent($this->item_id);
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_html_creator_box",
                                         'HCBID', 'HCBPosition', 'FK_HCID', $this->item_id,
                                         array(), 'HCBDeleted');

    foreach ($boxModels as $model) {
      /* @var $model HtmlCreatorBox */
      $this->_setBoxImageConfigurationFromConfig($model);

      $moveUpPosition = $positionHelper->getMoveUpPosition($model->position);
      $moveDownPosition = $positionHelper->getMoveDownPosition($model->position);
      $uploadedImageDetails = $this->_getUploadedImageDetails($model->image, $pfx, $pfx, 0);

      $boxItems[$model->id] = array_merge($uploadedImageDetails,
        array(
          $pfx . '_action'          => "index.php?action=mod_htmlcreator&action2=edit&amp;site={$this->site_id}&amp;page={$this->item_id}",
          $pfx . '_delete_link'     => "index.php?action=mod_htmlcreator&action2=edit&amp;site={$this->site_id}&amp;page={$this->item_id}&amp;deleteBoxID={$model->id}#a_boxes",
          $pfx . '_id'              => $model->id,
          $pfx . '_image'           => $model->image,
          $pfx . '_url'             => $model->url,
          $pfx . '_image_alt_label' => $_LANG['m_image_alt_label'],
          $pfx . '_move_down_link'  => "index.php?action=mod_htmlcreator&amp;action2=edit&amp;site={$this->site_id}&amp;page={$this->item_id}&amp;moveID={$model->id}&amp;moveTo=$moveDownPosition",
          $pfx . '_move_up_link'    => "index.php?action=mod_htmlcreator&amp;action2=edit&amp;site={$this->site_id}&amp;page={$this->item_id}&amp;moveID={$model->id}&amp;moveTo=$moveUpPosition",
          $pfx . '_position'        => $model->position,
          $pfx . '_required_resolution_label' => $this->_getImageSizeInfo($pfx . $model->position, 0),
          $pfx . '_text'            => $model->text,
          $pfx . '_title'           => $model->title,
          $pfx . '_type_label'      => $this->_getBoxConfigValue($model, 'label'),
          $pfx . '_custom_config_styles' => $this->_getBoxCustomConfigStyles($model),
          $pfx . '_title_label'     => $this->_getBoxLangValue($model, 'title'),
          $pfx . '_text_label'     => $this->_getBoxLangValue($model, 'text'),
          $pfx . '_image_label'     => $this->_getBoxLangValue($model, 'image'),
          $pfx . '_url_label'     => $this->_getBoxLangValue($model, 'url'),
        )
      );
    }

    return $boxItems;
  }

  /**
   * Creates a box.
   *
   * @param boolean $system (optional, default false)
   *        Set to true if a box should be created by the system
   *        and not by user.
   * @return boolean
   *         True on success.
   */
  private function _create($system = false)
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);
    $model = $this->getModel();

    if (!$post->exists('process_new_element') && !$system) {
      return false;
    }

    $model->template = $post->readString('hc_new_element_template');
    // Validate form fields
    if ($model->validate() === false) {
      $this->setMessage($model->getValidationMsg());
      return false;
    }
    if (!$model->template) {
      $this->setMessage(Message::createFailure($_LANG['hc_box_message_failure_create_box_missing_template']));
      return false;
    }

    $model->parentId = $this->item_id;
    // Set next possible position
    if (!$model->position) {
      $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_html_creator_box",
                                           'HCBID', 'HCBPosition', 'FK_HCID', $this->item_id,
                                           array(), 'HCBDeleted');
      $model->position = $positionHelper->getHighestPosition() + 1;
    }

    // Save model fields
    $model->create();

    if (!$system) {
      $this->_boxChanged = true;
      $this->setMessage(Message::createSuccess($_LANG[$this->_prefix . '_message_create_success']));
    }

    return true;
  }

  /**
   * Delete an item, if $_GET parameter 'deleteID' is set
   */
  private function _delete()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);
    $id = $get->readInt('deleteBoxID');
    if (!$id) {
      return false;
    }
    $model = $this->getModel()->readHtmlCreatorBoxItemById($id);
    $model->deleted = 1;

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_html_creator_box",
                                         'HCBID', 'HCBPosition', 'FK_HCID', $this->item_id,
                                         array(), 'HCBDeleted');
    // move element to highest position to resort all other elements
    $positionHelper->move($id, $positionHelper->getHighestPosition());

    $model->update();
    $this->_boxChanged = true;
    $this->setMessage(Message::createSuccess($_LANG[$this->_prefix . '_message_delete_success']));
  }

  /**
   * Reads the image number from a get parameter
   * and deletes the image.
   */
  private function _deleteImage()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $boxId = $get->readInt('deleteBoxImage');
    if (!$boxId) {
      return;
    }
    
    $this->_boxChanged = true;

    // Do not really delete image file. Delete only
    // the pointer (path) in the database field.
    // Existing image files are maybe used
    // in a newsletter or elsewhere and^ should always remain in /img/htmlcreator
    $sql = " UPDATE {$this->table_prefix}module_html_creator_box "
         . " SET HCBImage = '' "
         . " WHERE HCBID = {$this->db->escape($boxId)} ";
    $this->db->query($sql);

    $this->_redirect(
      $this->_parseUrl('edit', array('site' => $this->site_id, 'page' => $this->item_id)), 
      Message::createSuccess($_LANG['hc_message_delete_image_success'])
    );
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

    $post = new Input(Input::SOURCE_POST);
    $pfx = $this->_prefix;

    if (!$post->exists('process_hc_box')) {
      return false;
    }

    $id = $post->readKey('process_hc_box');
    $this->_model = $this->_model->readHtmlCreatorBoxItemById($id);

    $this->_setBoxImageConfigurationFromConfig($this->_model);

    $this->_model->title = $post->readString($pfx . $id . "_title");
    $this->_model->text = $post->readString($pfx . $id . "_text", Input::FILTER_CONTENT_TEXT);
    $image = $this->_storeImage($_FILES[$pfx . $id . "_image"], null, $pfx . $this->_model->position, 1, null, false, false, $this->_getDestinationPrefix(), true, false);
    $this->_model->image = ($image) ? $image : $this->_model->image;
    $this->_model->url = $post->readString($pfx . $id . '_url');

    // Validate form fields
    if ($this->_model->validate() === false) {
      $this->setMessage($this->_model->getValidationMsg());
      return false;
    }

    // Save model fields
    $this->_model->update();
    $this->_boxChanged = true;

    $this->setMessage(Message::createSuccess($_LANG[$pfx . '_message_update_success']));
  }

  /**
   * Moves an item.
   *
   * @return boolean
   *         True on success.
   */
  private function _move()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);
    if (!$get->exists('moveID', 'moveTo')) {
      return;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_html_creator_box",
                                         'HCBID', 'HCBPosition', 'FK_HCID', $this->item_id,
                                         array(), 'HCBDeleted');
    $moved = $positionHelper->move($get->readInt('moveID'), $get->readInt('moveTo'));

    if ($moved) {
      $this->_boxChanged = true;
      $this->setMessage(Message::createSuccess($_LANG[$this->_prefix . '_message_move_success']));
      return true;
    }

    return false;
  }

  /**
   * Gets the destination prefix of a path
   * to store images.
   *
   * @return string
   */
  private function _getDestinationPrefix()
  {
    return 'htmlcreator/hc_box';
  }

  /**
   * @param HtmlCreatorBox $model
   * @param string         $name
   *
   * @return mixed | null
   */
  private function _getBoxConfigValue(HtmlCreatorBox $model, $name)
  {
    list($parentType, $type) = explode('.', $model->template);

    $config = ConfigHelper::get('hc_config', null, $this->site_id);
    $config = $config[$parentType];
    $config = $config['boxes'][$type];

    return isset($config[$name]) ? $config[$name] : null;
  }

  /**
   * @param HtmlCreatorBox $model
   *
   * @return string
   */
  private function _getBoxCustomConfigStyles(HtmlCreatorBox $model)
  {
    $fields = $this->_getBoxConfigValue($model, 'fields');

    $styles = '<style>';
    foreach ($fields as $name)
    {
      $styles .= '#hcbox_' . $model->position . ' .display_hc_box_' . $name . '{display:block;visibility:visible}' . "\n";
    }

    $styles .= '</style>';

    return $styles;
  }

  /**
   * @param HtmlCreatorBox $model
   */
  private function _setBoxImageConfigurationFromConfig(HtmlCreatorBox $model)
  {
    $config = $this->_getBoxConfigValue($model, 'config');
    $config = $config['image'];

    ConfigHelper::set('hc_box' . $model->position . '_image_width', $config['width']);
    ConfigHelper::set('hc_box' . $model->position . '_image_height', $config['height']);
    ConfigHelper::set('hc_box' . $model->position . '_large_image_width', $config['width']);
    ConfigHelper::set('hc_box' . $model->position . '_large_image_height', $config['height']);
  }

  /**
   * @param HtmlCreatorBox $model
   * @param string         $name
   *
   * @return string
   */
  private function _getBoxLangValue(HtmlCreatorBox $model, $name)
  {
    $config = $this->_getBoxConfigValue($model, 'lang');
    return is_array($config) && isset($config[$name]) ? $config[$name] : '';
  }
}

