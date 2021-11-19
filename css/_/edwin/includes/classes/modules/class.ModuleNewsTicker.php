<?php

/**
 * Newsletter Module Class
 *
 * $LastChangedDate: 2017-08-21 14:24:21 +0200 (Mo, 21 Aug 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */

class ModuleNewsTicker extends Module
{
  protected $_prefix = 'nt';

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Content Handler                                                                       //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function show_innercontent()
  {
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
  private function edit_content()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $nt_selected_items = implode(',', array_keys($post->readArrayIntToInt('nt_page')));

    $title = $post->readString('nt_title', Input::FILTER_PLAIN);
    $text = $post->readString('nt_text', Input::FILTER_PLAIN);

    $sql = 'SELECT FK_SID '
         . "FROM {$this->table_prefix}module_newsticker "
         . "WHERE FK_SID = $this->site_id ";
    $exists = $this->db->GetOne($sql);
    if ($exists) {
      $sql = "UPDATE {$this->table_prefix}module_newsticker "
           . "SET TTitle = '{$this->db->escape($title)}', "
           . "    TText = '{$this->db->escape($text)}', "
           . "    TSelectedItems = '{$this->db->escape($nt_selected_items)}' "
           . "WHERE FK_SID = $this->site_id ";
      $result = $this->db->query($sql);
    } else {
      $sql = "INSERT INTO {$this->table_prefix}module_newsticker "
           . '(TTitle, TText, TSelectedItems, FK_SID) '
           . "VALUES('{$this->db->escape($title)}', '{$this->db->escape($text)}', "
           . "       '{$this->db->escape($nt_selected_items)}', $this->site_id) ";
      $result = $this->db->query($sql);
    }

    $sql = 'SELECT TImage '
         . "FROM {$this->table_prefix}module_newsticker "
         . "WHERE FK_SID = $this->site_id ";
    $existingImage = $this->db->GetOne($sql);
    $nt_image = '';
    if (isset($_FILES['nt_image'])) {
      $nt_image = $this->_storeImage($_FILES['nt_image'], $existingImage, 'nt', 1, $this->site_id);
    }
    if ($nt_image) {
      $result = $this->db->query("UPDATE {$this->table_prefix}module_newsticker SET TImage = '$nt_image' WHERE FK_SID = $this->site_id");
    }

    if ($result) {
      $this->setMessage(Message::createSuccess($_LANG['nt_message_success']));
    }
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Show Content                                                                          //
  ///////////////////////////////////////////////////////////////////////////////////////////
  private function get_content()
  {
    global $_LANG, $_LANG2;

    $result = $this->db->query("SELECT TTitle,TText,TImage,TSelectedItems from ".$this->table_prefix."module_newsticker WHERE FK_SID=".$this->site_id);
    $row = $this->db->fetch_row($result);
    $nt_title = $row["TTitle"];
    $nt_text = $row["TText"];
    $nt_image_src = $this->_noContentImage;
    if ($row["TImage"]) $nt_image_src = $this->get_large_image($this->_prefix, $row["TImage"]);
    $nt_selected_items = explode(",",$row["TSelectedItems"]);
    $this->db->free_result($result);

    $nt_pages = array();
    $sql = 'SELECT CIID, CIIdentifier, CTitle '
         . "FROM {$this->table_prefix}contentitem "
         . 'WHERE FK_CTID = 76 '
         . "AND FK_SID = $this->site_id "
         . 'ORDER BY CTitle ASC ';
    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result)){
      $nt_pages[] = array ( 'nt_page_label' => sprintf($_LANG["nt_page_label"], parseOutput($row["CTitle"]), $row["CIIdentifier"]) ,
                            'nt_page' => '<input type="checkbox" name="nt_page['.$row["CIID"].']" id="nt_page['.$row["CIID"].']" value="1" class="nt_page"'.(in_array($row["CIID"],$nt_selected_items) ? ' checked="checked"' : "")." />",
                            'nt_link' => "index.php?action=content&amp;site=".$this->site_id."&amp;page=".$row["CIID"] );
    }
    $this->db->free_result($result);

    $this->tpl->load_tpl('content_newsticker', 'modules/ModuleNewsTicker.tpl');
    $this->tpl->parse_if('content_newsticker', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('nt'));
    $this->tpl->parse_loop('content_newsticker', $nt_pages, 'pages');
    $nt_content = $this->tpl->parsereturn('content_newsticker', array_merge(array (
        'nt_function_label' => $_LANG["nt_function_label"],
        'nt_description' => $_LANG["nt_description"],
        'nt_site_selection' => $this->_parseModuleSiteSelection('newsticker', $_LANG['nt_site_label']),
        'nt_action' => 'index.php?action=mod_newsticker',
        'nt_hidden_fields' => '<input type="hidden" name="site" value="' . $this->site_id . '" />',
        'nt_title' => $nt_title,
        'nt_text' => $nt_text,
        'nt_image_src' => $nt_image_src,
        'nt_pagelisting_label' => $_LANG["nt_pagelisting_label"],
        'nt_title_label' => $_LANG["nt_title_label"],
        'nt_text_label' => $_LANG["nt_text_label"],
        'nt_image_label' => $_LANG["nt_image_label"],
        'nt_module_action_boxes' => $this->_getContentActionBoxes(),
    ), $_LANG2['nt']));

    return array(
        'content' => $nt_content,
    );
  }
}

