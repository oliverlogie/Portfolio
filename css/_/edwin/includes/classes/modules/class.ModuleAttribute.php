<?php

  /**
   * Attribute Module Class
   *
   * $LastChangedDate: 2017-08-21 13:04:29 +0200 (Mo, 21 Aug 2017) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */
  class ModuleAttribute extends AbstractModuleAttribute
  {
    public static $subClasses = array('global' => 'ModuleAttributeGlobal');

    protected $_prefix = 'at';

    ////////////////////////////////////////////////////////////////////////////
    // Content Handler                                                        //
    ////////////////////////////////////////////////////////////////////////////
    public function show_innercontent($subModuleShortname = '')
    {
      $request = new Input(Input::SOURCE_REQUEST);
      $this->_typeId = $request->readInt('type_id');
      $this->_model = new Attribute($this->db, $this->table_prefix, $this->_prefix);

      if (isset($_POST["process"]) && $this->action[0]=="new") $this->create_content();
      if (isset($_POST["process"]) && $this->action[0]=="edit") $this->edit_content();
      if (isset($_GET["did"])) $this->_delete();

      // Perform move of an attribute value if necessary.
      $this->_move();

      if (empty($this->action[0])) {
        return $this->_showList();
      } else {
        return $this->_showForm();
      }
    }

    ////////////////////////////////////////////////////////////////////////////
    // Create Content                                                         //
    ////////////////////////////////////////////////////////////////////////////
    private function create_content(){
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);

      $result = $this->db->query("SELECT max(AVPosition) as max_position from ".$this->table_prefix."module_attribute WHERE FK_AID=".$this->_typeId);
      $row = $this->db->fetch_row($result);
      $max_position = $row["max_position"];
      $this->db->free_result($result);

      $title = $post->readString('at_title', Input::FILTER_PLAIN);
      $text = $post->readString('at_text', Input::FILTER_CONTENT_TEXT);
      $targetItem = $post->readInt('at_link_item', 0);
      $relationship = $post->readInt('at_relationship', 0);
      $position = $max_position + 1;

      if (!$title) {
        $this->setMessage(Message::createFailure($_LANG['at_message_insufficient_input']));
      }
      else{
        $sql = "INSERT INTO {$this->table_prefix}module_attribute "
             . '(AVTitle, AVText, AVPosition, FK_AID) '
             . "VALUES ('{$this->db->escape($title)}', '{$this->db->escape($text)}', "
             . "        $position, $this->_typeId) ";
        $result = $this->db->query($sql);
        $this->item_id = $this->db->insert_id();

        if (isset($_FILES['at_image']) && $at_image = $this->_storeImage($_FILES['at_image'], '', 'at', 0, null, false, true) ) {
          $result = $this->db->query("UPDATE ".$this->table_prefix."module_attribute SET AVImage='".$at_image."' WHERE AVID=".$this->item_id);
        }

        $item = array(
          'id' => $this->item_id,
          'siteId' => $this->site_id,
        );
        self::_updateRelationship($this->db, $this->table_prefix, $item, $relationship, $targetItem);

        if ($result) {
          if ($this->_redirectAfterProcessingRequested('list')) {
            $this->_redirect($this->_getBackLinkUrl(),
                Message::createSuccess($_LANG['at_message_newitem_success']));
          }
          else {
            $url = $this->_parseUrl('edit', array('page' => $this->item_id));
            $this->_redirect($url, Message::createSuccess($_LANG['at_message_newitem_success']));
          }
        }
      }
    }

    ////////////////////////////////////////////////////////////////////////////
    // Edit Content                                                           //
    ////////////////////////////////////////////////////////////////////////////
    private function edit_content()
    {
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);

      $title = $post->readString('at_title', Input::FILTER_PLAIN);
      $text = $post->readString('at_text', Input::FILTER_CONTENT_TEXT);
      $targetItem = $post->readInt('at_link_item', 0);
      $relationship = $post->readInt('at_relationship', 0);

      if (!$title) {
        $this->setMessage(Message::createFailure($_LANG['at_message_insufficient_input']));
      }
      else
      {
        $sql = 'SELECT AVImage '
             . "FROM {$this->table_prefix}module_attribute "
             . "WHERE AVID = $this->item_id ";
        $existingImage = $this->db->GetOne($sql);
        $image = $existingImage;
        if (isset($_FILES['at_image']) && $uploadedImage = $this->_storeImage($_FILES['at_image'], $image, 'at', 0, null, false, true)) {
          $image = $uploadedImage;
        }

        $sql = "UPDATE {$this->table_prefix}module_attribute "
             . "SET AVTitle = '{$this->db->escape($title)}', "
             . "    AVText = '{$this->db->escape($text)}', "
             . "    AVImage = '$image' "
             . "WHERE AVID = $this->item_id ";
        $result = $this->db->query($sql);

        $item = array(
          'id' => $this->item_id,
          'siteId' => $this->site_id,
        );
        self::_updateRelationship($this->db, $this->table_prefix, $item, $relationship, $targetItem);

        if (!$this->_getMessage() && $result) {
          if ($this->_redirectAfterProcessingRequested('list')) {
            $this->_redirect($this->_getBackLinkUrl(),
                Message::createSuccess($_LANG['at_message_edititem_success']));
          }
          else {
            $url = $this->_parseUrl('edit', array('page' => $this->item_id));
            $this->_redirect($url, Message::createSuccess($_LANG['at_message_edititem_success']));
          }
        }
      }
    }

    ////////////////////////////////////////////////////////////////////////////
    // Show Content                                                           //
    ////////////////////////////////////////////////////////////////////////////
    protected function _showForm(){
      global $_LANG, $_LANG2;

      $post = new Input(Input::SOURCE_POST);

      if ($this->item_id){ // edit attribute -> load data
        $sql = " SELECT AVID, AVTitle, AVText, AVImage, AImages, FK_ALID, FK_AGID "
             . " FROM {$this->table_prefix}module_attribute "
             . " JOIN {$this->table_prefix}module_attribute_global "
             . "   ON FK_AID = AID "
             . " WHERE AVID = $this->item_id ";
        $result = $this->db->query($sql);
        $row = $this->db->fetch_row($result);

        $at_title = $row["AVTitle"];
        $at_text = $row["AVText"];
        $at_image_src = $row["AVImage"];
        $showImages = (bool)$row["AImages"];
        $relationship = (int)$row['FK_ALID'];
        $linkedItem = 0;
        $groupRelation = (int)$row['FK_AGID'];

        $this->db->free_result($result);
        $at_function = "edit";
      }
      else{ // new attribute
        $at_title = "";
        $at_text = "";
        $at_image_src = "";
        $at_function = "new";

        $at_title = $post->readString('at_title', Input::FILTER_PLAIN);
        $at_text = $post->readString('at_text', Input::FILTER_CONTENT_TEXT);
        $relationship = $post->readInt('at_relationship', 0);
        $linkedItem = $post->readInt('at_link_item', 0);
        $function = 'new';

        $sql = " SELECT AImages, FK_AGID "
             . " FROM {$this->table_prefix}module_attribute_global "
             . " WHERE AID = $this->_typeId ";
        $row = $this->db->GetRow($sql);

        $showImages = ($row) ? (bool)$row['AImages'] : false;
        $groupRelation = ($row) ? (int)$row['FK_AGID'] : 0;
      }

      // get attribute relationship select /////////////////////////////////////
      $relationshipOptions = ''; // groups of linked attributes
      $attrOptions = ''; // attributes not linked / not in relations
      if ($groupRelation) {
        $sql = " SELECT AVID, AVTitle, FK_SID, FK_ALID "
             . " FROM {$this->table_prefix}module_attribute "
             . " JOIN {$this->table_prefix}module_attribute_global "
             . "      ON FK_AID = AID "
             . " WHERE FK_AGID = $groupRelation "
             . " ORDER BY FK_SID, ATitle ASC ";
        $result = $this->db->query($sql);

        while ($row = $this->db->fetch_row($result)) {
          $id = $row['AVID'];
          $groupId = $row['FK_ALID'];
          if (!$groupId) {
            if ($id == $this->item_id || $row['FK_SID'] == $this->site_id)
              continue;
            $attrOptions .= '<option value="' . $id. '">' . parseOutput($row['AVTitle']) . '</option>';
          }
          else {
            $relationships[$groupId][] = $row;
          }
        }
        $this->db->free_result($result);

        $attrOptions = '<option value="0">' . $_LANG['at_option_none_label'] . '</option>' . $attrOptions;

        $relationshipOptions = '<option value="0">' . $_LANG['at_option_none_label'] . '</option>';
        if (isset($relationships)) {
          foreach ($relationships as $key => $tmpGroups) {
            $tmp = array();
            foreach ($tmpGroups as $row ) {
              $tmp[] = $row['AVTitle'];
            }
            $relationshipOptions .= '<option value="' . $row['FK_ALID'] . '" '
                                . ( $key == $relationship ? 'selected="selected"' : '' )
                                . '>' . parseOutput(implode(' - ', $tmp)) . '</option>';
          }
        }
      }
      ////////////////////////////////////////////////////////////////////////////

      $this->tpl->load_tpl('content_attribute', 'modules/ModuleAttribute.tpl');
      $this->tpl->parse_if('content_attribute', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('at'));
      $this->tpl->parse_if('content_attribute', 'show_images', $showImages);
      $this->tpl->parse_if('content_attribute', 'attr_group_relations_available', $groupRelation);
      $at_content = $this->tpl->parsereturn('content_attribute', array_merge(array(
        'at_title' => $at_title,
        'at_text' => $at_text,
        'at_image_src' => $this->get_normal_image('at', $at_image_src),
        'at_large_image_available' => $this->_getImageZoomLink('at', $at_image_src),
        'at_relationship_options' => $relationshipOptions,
        'at_attribute_options' => $attrOptions,
        'at_title_label' => $_LANG["at_title_label"],
        'at_text_label' => $_LANG["at_text_label"],
        'at_image_label' => $_LANG["at_image_label"],
        'at_function_label' => ($this->item_id ? $_LANG["at_function_edit_label"] : $_LANG["at_function_new_label"]),
        'at_action' => "index.php",
        'at_hidden_fields' => '<input type="hidden" name="action" value="mod_attribute" /><input type="hidden" name="action2" value="main;'.$at_function.'" /><input type="hidden" name="page" value="'.$this->item_id.'" /><input type="hidden" name="site" value="'.$this->site_id.'" /><input type="hidden" name="type_id" value="'.$this->_typeId.'" />',
        'module_action_boxes' => $this->_getContentActionBoxes(),
        'at_required_resolution_label' => $this->_getImageSizeInfo('at', 1),
        'at_image_alt_label' => $_LANG["at_image_alt_label"]
      ), $this->_getUploadedImageDetails($at_image_src, $this->_prefix, $this->_prefix, 0),
        $_LANG2['at']));

      return array(
          'content'      => $at_content,
          'content_left' => $this->_getContentLeft(true),
      );

    }
  }