<?php

  /**
   * Newsletter Module Class
   *
   * $LastChangedDate: 2017-12-22 13:22:49 +0100 (Fr, 22 Dez 2017) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */

  class ModuleInfoTicker extends Module
  {
    protected $_prefix = 'it';

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Content Handler                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function show_innercontent(){
      if (isset($_POST["process"])) $this->edit_content();

      return $this->get_content();
    }

    protected function _getContentActionBoxButtonsTemplate()
    {
      return 'module_action_boxes_buttons_save_only.tpl';
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Edit Content                                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function edit_content(){
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);

      $text = $post->readString('it_text', Input::FILTER_PLAIN);
      $rotationTime = $post->readInt('it_rotationtime');
      $random = (int)$post->readBool('it_random');

      $sql = "UPDATE {$this->table_prefix}module_infoticker "
           . "SET IText = '{$this->db->escape($text)}', "
           . "    IRotationTime = $rotationTime, "
           . "    IRandom = $random "
           . "WHERE FK_SID = $this->site_id ";
      $result = $this->db->query($sql);

      if ($result) {
        $this->setMessage(Message::createSuccess($_LANG["it_message_success"]));
      }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Show Content                                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    private function get_content()
    {
      global $_LANG, $_LANG2;

      $result = $this->db->query("SELECT count(IID) as already_created from ".$this->table_prefix."module_infoticker WHERE FK_SID=".$this->site_id);
      $row = $this->db->fetch_row($result);
      if (!$row["already_created"])
        $result = $this->db->query("INSERT INTO ".$this->table_prefix."module_infoticker (FK_SID) VALUES('".$this->site_id."')");
      $this->db->free_result($result);

      $result = $this->db->query("SELECT IText,IRotationTime,IRandom from ".$this->table_prefix."module_infoticker WHERE FK_SID=".$this->site_id);
      $row = $this->db->fetch_row($result);
      $it_text = $row["IText"];
      $it_rotationtime = intval($row["IRotationTime"]);
      $it_random = "<input type=\"checkbox\" class=\"it_random\"name=\"it_random\" ".($row["IRandom"]==1 ? "checked=\"checked\"": "")."/>";
      $this->db->free_result($result);

      $this->tpl->load_tpl('content_exportdata', 'modules/ModuleInfoTicker.tpl');
      $this->tpl->parse_if('content_exportdata', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('it'));
      $it_content = $this->tpl->parsereturn('content_exportdata', array_merge(array (
          'it_function_label' => $_LANG["it_function_label"],
          'it_description_label' => $_LANG["it_description_label"],
          'it_description' => $_LANG["it_description"],
          'it_site_selection' => $this->_parseModuleSiteSelection('infoticker', $_LANG['it_site_label']),
          'it_action' => 'index.php?action=mod_infoticker',
          'it_hidden_fields' => '<input type="hidden" name="site" value="' . $this->site_id . '" />',
          'it_module_action_boxes' => $this->_getContentActionBoxes(),
          'it_text' => $it_text,
          'it_rotationtime' => $it_rotationtime,
          'it_random' => $it_random,
          'it_text_label' => $_LANG["it_text_label"],
          'it_rotationtime_label' => $_LANG["it_rotationtime_label"],
          'it_random_label' => $_LANG["it_random_label"] ),
      $_LANG2['it']));

      return array(
          'content' => $it_content,
      );
    }
  }

