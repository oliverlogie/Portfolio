<?php

/**
 * Announcement Module Class
 *
 * $LastChangedDate: 2017-10-10 11:49:53 +0200 (Di, 10 Okt 2017) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */
class ModuleAnnouncement extends Module
{
  protected $_prefix = 'an';

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Content Handler                                                                       //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function show_innercontent(){

    if (isset($_POST["process"]) && $this->action[0]=="edit") $this->edit_content();

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

    $titles = $post->readArrayIntToString('an_title');
    $texts = $post->readArrayIntToString('an_text');
    $dates = $post->readArrayIntToString('an_date');
    $times = $post->readArrayIntToString('an_time');

    $result = '';
    foreach ($titles as $id => $value){
      $sql = "UPDATE {$this->table_prefix}module_announcement "
         . "SET ATitle = '$titles[$id]', "
         . "    AText = '$texts[$id]', "
         . "    ADateTime = ".($dates[$id] && $times[$id] ? "'".date("Y-m-d H:i",strtotime($dates[$id]." ".$times[$id]))."'" : "NULL")." "
         . "WHERE AID = $id ";
      $result = $this->db->query($sql);

    }

    if ($result) {
      $this->setMessage(Message::createSuccess($_LANG['an_message_edit_success']));
    }
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Show Content                                                                          //
  ///////////////////////////////////////////////////////////////////////////////////////////
  private function get_content(){
    global $_LANG,$_LANG2;

    // read announcements
    $this->item_id = 0;
    $announcement_rows = array();
    $result = $this->db->query("SELECT AID,ATitle,ADateTime,AText from ".$this->table_prefix."module_announcement ORDER BY APosition ASC");
    while ($row = $this->db->fetch_row($result)){
      $announcement_rows[] = array( "an_id" => $row["AID"],
                                    "an_date" => ($row["ADateTime"] ? date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'an'),strtotime($row["ADateTime"])) : ""),
                                    "an_time" => ($row["ADateTime"] ? date(ConfigHelper::get('an_time_format'),strtotime($row["ADateTime"])) : ""),
                                    "an_title" => $row["ATitle"],
                                    "an_text" => $row["AText"],
                                    "an_title_heading_label" => sprintf($_LANG["an_title_heading_label"],$row["ATitle"]) );
    }
    $this->db->free_result($result);

    $this->tpl->load_tpl('content_announcement', 'modules/ModuleAnnouncement.tpl');
    $this->tpl->parse_if('content_announcement', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('an'));
    $this->tpl->parse_loop('content_announcement', $announcement_rows, 'announcement_rows');
    $an_content = $this->tpl->parsereturn('content_announcement', array_merge(
    array(
      'an_function_label' => $_LANG["an_function_label"],
      'an_function_label2' => $_LANG["an_function_label2"],
      'an_date_label' => $_LANG["an_date_label"],
      'an_time_label' => $_LANG["an_time_label"],
      'an_title_label' => $_LANG["an_title_label"],
      'an_text_label' => $_LANG["an_text_label"],
      'an_site' => $this->site_id,
      'an_action' => "index.php",
      'an_hidden_fields' => '<input type="hidden" name="action" value="mod_announcement" /><input type="hidden" name="action2" value="edit" /><input type="hidden" name="page" value="'.$this->item_id.'" />',
      'an_actions_label' => $_LANG['m_actions_label'],
      'an_image_alt_label' => $_LANG['m_image_alt_label'],
      'an_module_action_boxes' => $this->_getContentActionBoxes(),
    ),
    $_LANG2['an']));

    return array(
        'content' => $an_content,
    );
  }
}