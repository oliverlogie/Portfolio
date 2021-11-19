<?php

/**
 * AbstractModuleAttribute Module Class.
 * This class should be parent of each object, that
 * is dealing with attributes.
 *
 * $LastChangedDate: 2017-10-16 07:32:10 +0200 (Mo, 16 Okt 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Anton Jungwirth
 * @copyright (c) 2012 Q2E GmbH
 */

abstract class AbstractModuleAttribute extends Module
{
  /**
   * AttributeGlobal id.
   *
   * @var int
   */
  protected $_typeId = 0;

  /**
   * Submodule's shortname.
   *
   * @var string
   */
  protected $_subModuleShortname = '';

  /**
   * Shows module's content.
   *
   * @param $subModuleShortname
   *        The shortname of a sub module.
   */
  public function show_innercontent($subModuleShortname = '')
  {
    $post = new Input(Input::SOURCE_POST);

    $this->_subModuleShortname = $subModuleShortname;
    $this->_model = new Attribute($this->db, $this->table_prefix, $this->_prefix);
    $fields = $post->readMultipleArrayStringToString($this->_prefix.'_field');
    $fields['parentId'] = $this->_typeId;
    $this->_model->setFields($fields);
    $textFields = $post->readMultipleArrayStringToString($this->_prefix.'_field', Input::FILTER_CONTENT_TEXT);
    $this->_model->text = (isset($textFields[$this->_prefix.'_text'])) ? $textFields[$this->_prefix.'_text'] : null;

    $this->_create();
    $this->_delete();
    $this->_move();
    $this->_update();

    if (empty($this->action[0])) {
      return $this->_showList();
    }
    else {
      return $this->_showForm();
    }
  }

  protected static function _deleteAttribute(Db $db, $tablePrefix, $id)
  {
    $sql = " SELECT AVID, AVImage, FK_AID, FK_SID "
         . " FROM {$tablePrefix}module_attribute "
         . " JOIN {$tablePrefix}module_attribute_global "
         . "      ON FK_AID = AID "
         . " WHERE AVID = $id " ;
    $row = $db->GetRow($sql);

    if (!$row) return false;

    // delete attribute from existing relationship before deleting the
    // attribute itself ( ModuleAttribute::_updateRelationship requires
    // existing attribute )
    $item = array(
      'id' => $id,
      'siteId' => $row['FK_SID'],
    );
    self::_updateRelationship($db, $tablePrefix, $item, 0, 0);

    // move attribute to highest position before deleting it
    $positionHelper = new PositionHelper($db, "{$tablePrefix}module_attribute",
                                         'AVID', 'AVPosition',
                                         'FK_AID', $row['FK_AID']);
    $positionHelper->move($id, $positionHelper->getHighestPosition());

    self::_deleteImageFiles($row["AVImage"]);

    // Delete the employee attributes
    $sql = "DELETE FROM {$tablePrefix}module_employee_attribute "
         . "WHERE FK_AVID = $id ";
    $db->query($sql);

    $sql = " DELETE FROM {$tablePrefix}module_attribute "
         . " WHERE AVID = $id ";
    $db->query($sql);

    // we have to delete all products from ContentItemPP contentitems using
    // the currently deleted attribute
    $sql = " SELECT PPPID, FK_CIID "
         . " FROM {$tablePrefix}contentitem_pp_product "
         . " JOIN {$tablePrefix}contentitem_pp_product_attribute "
         . "   ON FK_PPPID = PPPID "
         . " WHERE FK_AVID = $id ";
    $col = $db->GetAssoc($sql);

    foreach ($col as $key => $val)
      ContentItemPP_Products::deleteItemById($db, $tablePrefix, $key, $val);

    return true;
  }

  /**
   * Update attribute relationship, if both parameters $relationship and
   * $targetItem are specified, the attribute will be added to relationship,
   * while there $targetItem will be ignored.
   *
   * @param Db $db
   *        The database object
   * @param string $tablePrefix
   *        The database table prefix
   * @param array $item
   *        - id : the id of the attribute to update relationship for
   *        - siteId : the site id of the site the attribute belongs to
   * @param int $relationship
   *        The id of the relationship to add attribute to
   * @param int $targetItem
   *        The id of the attribute to create a new relation with
   *
   * @return void
   */
  protected static function _updateRelationship(Db $db, $tablePrefix, $item, $relationship, $targetItem)
  {
    global $_LANG;

    $id = $item['id'];
    $siteId = $item['siteId'];

    // get attrtibute relation data
    $sql = " SELECT FK_ALID "
         . " FROM {$tablePrefix}module_attribute "
         . " WHERE AVID = $id ";
    $old = $db->GetOne($sql);

    // if the attribute's relationship changed / was removed
    if ($old && $old != $relationship) {
      $sql = " SELECT COUNT(*) "
           . " FROM {$tablePrefix}module_attribute "
           . " WHERE FK_ALID = $old ";
      $count = (int)$db->GetOne($sql);
      // there will be only one attrtibute left, after deleting current attrtibute, so we
      // delete relationship and remove all attrtibute items
      if ($count <= 2) {
        $sql = " SELECT ALPosition "
             . " FROM {$tablePrefix}module_attribute_link_group "
             . " WHERE ALID = $old ";
        $pos = $db->GetOne($sql);

        $sql = " DELETE "
             . " FROM {$tablePrefix}module_attribute_link_group "
             . " WHERE ALID = $old ";
        $db->query($sql);

        $sql = " UPDATE {$tablePrefix}module_attribute_link_group "
             . " SET ALPosition = ALPosition - 1 "
             . " WHERE ALPosition > $pos ";
        $db->query($sql);

        $sql = " UPDATE {$tablePrefix}module_attribute "
             . " SET FK_ALID = 0 "
             . " WHERE FK_ALID = $old ";
        $db->query($sql);
      }
      // remove current attrtibute from relationship
      else {
        $sql = " UPDATE {$tablePrefix}module_attribute "
             . " SET FK_ALID = 0 "
             . " WHERE AVID = $id ";
        $db->query($sql);
      }
    }

    // existing relationship
    if ($relationship) {
      // remove attrtibute from same site from relationship as only items from
      // different pages should be linked
      $sql = " UPDATE {$tablePrefix}module_attribute "
           . " JOIN {$tablePrefix}module_attribute_global "
           . "      ON FK_AID = AID "
           . " SET FK_ALID = 0 "
           . " WHERE FK_ALID = $relationship "
           . "   AND FK_SID = $siteId ";
      $db->query($sql);

      // add new attrtibute item to relationship
      $sql = " UPDATE {$tablePrefix}module_attribute "
           . " SET FK_ALID = $relationship "
           . " WHERE AVID = $id ";
      $db->query($sql);
    }
    // new relationship
    else if ($targetItem) {
      $sql = " SELECT MAX(ALPosition) "
           . " FROM {$tablePrefix}module_attribute_link_group ";
      $pos = (int)$db->GetOne($sql) + 1;

      $sql = " INSERT INTO {$tablePrefix}module_attribute_link_group "
           . " (ALPosition) VALUES ($pos) ";
      $db->query($sql);
      $newRelation = $db->insert_id();

      // add both attributes to relationship
      $sql = " UPDATE {$tablePrefix}module_attribute "
           . " SET FK_ALID = $newRelation "
           . " WHERE AVID IN ( $id, $targetItem ) ";
      $db->query($sql);
    }
  }

  protected function _create()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') || $this->action[0] != 'new' || !$this->_typeId) {
      return false;
    }

    $fields = $post->readMultipleArrayStringToString($this->_prefix.'_field');
    $fields['parentId'] = $this->_typeId;
    $this->_model->setFields($fields);
    $textFields = $post->readMultipleArrayStringToString($this->_prefix.'_field', Input::FILTER_CONTENT_TEXT);
    $this->_model->text = (isset($textFields[$this->_prefix.'_text'])) ? $textFields[$this->_prefix.'_text'] : null;

    // Validate form fields
    if ($this->_model->validate() === false) {
      $this->setMessage($this->_model->getValidationMsg());
      return false;
    }

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}{$this->_model->table()}",
                                         'AVID', 'AVPosition',
                                         'FK_AID', $this->_typeId);
    $this->_model->position = $positionHelper->getHighestPosition() + 1;
    // Save model fields
    $this->_model->create();

    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_getBackLinkUrl(),
          Message::createSuccess($this->_langVar('message_newitem_success')));
    }
    else {
      $this->_redirect($this->_parseUrl('edit', array('page' => $this->_model->id)),
          Message::createSuccess($this->_langVar('message_newitem_success')));
    }
  }

  protected function _delete()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);
    $id = $get->readInt('did');
    if (!$id) {
      return false;
    }

    if (self::_deleteAttribute($this->db, $this->table_prefix, $id)) {
      $this->setMessage(Message::createSuccess($this->_langVar('message_deleteitem_success')));
    }
  }

  /**
   * Moves an attribute if the GET parameters moveID and moveTo are set.
   *
   * @return void
   */
  protected function _move()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    if (!$get->exists('moveID', 'moveTo')) {
      return;
    }

    $moveId = $get->readInt('moveID');
    $moveTo = $get->readInt('moveTo');

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_attribute",
                                       'AVID', 'AVPosition',
                                       'FK_AID', $this->_typeId);
    $moved = $positionHelper->move($moveId, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($this->_langVar('message_move_success')));
    }
  }

  protected function _update()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!$post->exists('process') || $this->action[0] != 'edit' || !$this->_typeId) {
      return false;
    }
    // Validate form fields
    if ($this->_model->validate() === false) {
      $this->setMessage($this->_model->getValidationMsg());
      return false;
    }
    $this->_model->id = $this->item_id;
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}{$this->_model->table()}",
                                         'AVID', 'AVPosition',
                                         'FK_AID', $this->_typeId);
    $this->_model->position = $positionHelper->getPositionById($this->item_id);
    // Save model fields
    $this->_model->update();

    if ($this->_redirectAfterProcessingRequested('list')) {
      $this->_redirect($this->_getBackLinkUrl(),
          Message::createSuccess($this->_langVar('message_edititem_success')));
    }
    else {
      $url = $this->_parseUrl('edit', array('page' => $this->item_id));
      $this->_redirect($url, Message::createSuccess($this->_langVar('message_edititem_success')));
    }
  }

  protected abstract function _showForm();

  /**
   * Shows list of all attributes with specified type.
   *
   * @see Module::show_innercontent
   */
  protected function _showList()
  {
    global $_LANG, $_LANG2;

    $mod = "mod_{$this->getShortname()}";

    $attrGlobal = new AttributeGlobal($this->db, $this->table_prefix, $this->_prefix);
    $condition = array('select' => array('id', 'title'),
                       'where'  => "FK_SID = $this->site_id",
                       'order'  => "APosition ASC");
    $types = $attrGlobal->readAttributeGlobals($condition);
    if (!count($types)) {
      $this->setMessage(Message::createFailure($this->_langVar('message_no_attribute_types')));
    }
    $options = array();
    foreach ($types as $type) {
      if (!$this->_typeId) {
        $this->_typeId = $type->id;
      }
      $options[$type->id] = parseOutput($type->title);
    }
    $typeOptions = AbstractForm::selectOptions($options, $this->_typeId);
    $currentType = $attrGlobal->readAttributeGlobalById($this->_typeId);
    // read attribute values
    $items = $this->_getListLoopArray();
    if (!$items) {
      $this->setMessage(Message::createFailure($this->_langVar('message_no_attribute')));
    }

    $hiddenFields = '<input type="hidden" name="type_id" value="'.$this->_typeId.'" />'
                  . '<input type="hidden" name="site" value="'.$this->site_id.'" />';

    $tplName = 'content_'.$this->_prefix;
    $this->tpl->load_tpl($tplName, 'modules/ModuleAttribute_list.tpl');
    $this->tpl->parse_if($tplName, 'at_attribute_types_available', $typeOptions);
    $this->tpl->parse_if($tplName, 'message', $this->_getMessage(), $this->_getMessageTemplateArray('at'));
    $this->tpl->parse_if($tplName, 'show_images', $currentType->images);
    $this->tpl->parse_if($tplName, 'show_images', $currentType->images);
    $this->tpl->parse_loop($tplName, $items, 'attribute_items');
    $content = $this->tpl->parsereturn($tplName, array_merge(array (
      'at_action'                    => "index.php?action={$mod}",
      'at_attribute_move_label'      => $this->_langVar('attribute_move_label'),
      'at_choose_type_label'         => $this->_langVar('choose_type_label'),
      'at_deleteitem_question_label' => $this->_langVar('deleteitem_question_label'),
      'at_dragdrop_link_js'          => "index.php?action={$mod}{$this->_getAction2Param(false)}&site=$this->site_id&type_id=$this->_typeId&moveID=#moveID#&moveTo=#moveTo#",
      'at_hidden_fields'             => $hiddenFields,
      'at_list_label'                => $this->_langVar('list_label'),
      'at_list_label2'               => $this->_langVar('list_label2'),
      'at_site_selection'            => parent::_parseModuleSiteSelection($this->getShortname(), $this->_langVar('site_label'), $this->_subModuleShortname),
      'at_text_label'                => $this->_langVar('text_label'),
      'at_title_label'               => $this->_langVar('title_label'),
      'at_type_id'                   => $this->_typeId,
      'at_type_options'              => $typeOptions,
    $_LANG2['at'])));

    return array(
      'content'      => $content,
      'content_left' => $this->_getContentLeft(),
    );
  }

  protected function _getModuleUrlParts()
  {
    return array_merge(parent::_getModuleUrlParts(), array(
        'type_id' => $this->_typeId,
    ));
  }

  /**
   * Gets the "action2" parameter.
   *
   * @param $encodeAmpersand (optional)
   *        Default true. If false ampersands in the url are not going
   *        to be encoded.
   * @return string
   */
  private function _getAction2Param($encodeAmpersand = true)
  {
    $ampersand = ($encodeAmpersand) ? '&amp;' : '&';
    if ($this->_subModuleShortname) {
      $action2 = "{$ampersand}action2={$this->_subModuleShortname}";
    }
    else {
      $action2 = "{$ampersand}action2=main";
    }

    return $action2;
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

    $condition = array('select' => array('id', 'title', 'image', 'position'),
                       'where'  => "FK_AID = {$this->_typeId}",
                       'order'  => 'AVPosition ASC');
    $models = $this->_model->readAttribute($condition);
    $items = array();
    $mod = "mod_{$this->getShortname()}";
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_attribute",
                                         'AVID', 'AVPosition',
                                         'FK_AID', $this->_typeId);
    foreach ($models as $model) {
      /* @var $model Attribute */

      $moveUpPosition = $positionHelper->getMoveUpPosition($model->position);
      $moveDownPosition = $positionHelper->getMoveDownPosition($model->position);

      $action2 = $this->_getAction2Param();
      $items[] = array(
        'at_attribute_down_label' => $this->_langVar('attribute_down_label'),
        'at_attribute_down_link'  => "index.php?action={$mod}{$action2}&amp;site=$this->site_id&amp;type_id=$this->_typeId&amp;moveID={$model->id}&amp;moveTo=$moveDownPosition",
        'at_attribute_up_label'   => $this->_langVar('attribute_up_label'),
        'at_attribute_up_link'    => "index.php?action={$mod}{$action2}&amp;site=$this->site_id&amp;type_id=$this->_typeId&amp;moveID={$model->id}&amp;moveTo=$moveUpPosition",
        'at_box_label'            => $this->_langVar('box_label'),
        'at_content_label'        => $this->_langVar('content_label'),
        'at_content_link'         => "index.php?action={$mod}{$action2};edit&amp;site=".$this->site_id."&amp;type_id=".$this->_typeId."&amp;page=".$model->id,
        'at_delete_label'         => $this->_langVar('delete_label'),
        'at_delete_link'          => "index.php?action={$mod}{$action2}&amp;did={$model->id}&amp;type_id={$this->_typeId}",
        'at_id'                   => $model->id,
        'at_image_src'            => $model->image ? '../' . $model->image : 'img/no_image.png',
        'at_position'             => $model->position,
        'at_title'                => parseOutput($model->title),
      );
    }

    return $items;
  }
}