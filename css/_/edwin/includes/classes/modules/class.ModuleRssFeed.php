<?php

/**
 * RSS Feed Module Class
 *
 * $LastChangedDate: 2014-04-08 09:53:37 +0200 (Di, 08 Apr 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2010 Q2E GmbH
 */
class ModuleRssFeed extends Module
{
  protected $_prefix = 'rf';

  /**
   * Content handler
   */
  public function show_innercontent()
  {
    if (isset($_POST["process"])) $this->edit_content();

    return $this->get_content();
  }

  protected function _getContentActionBoxButtonsTemplate()
  {
    return 'module_action_boxes_buttons_save_only.tpl';
  }

  /**
   * Edit content
   */
  private function edit_content()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $title = $post->readString('rf_title', Input::FILTER_PLAIN);
    $text = $post->readString('rf_text', Input::FILTER_PLAIN);

    // Store rss feed's common data.
    $sql = " UPDATE {$this->table_prefix}module_rssfeed "
         . " SET RTitle = '{$this->db->escape($title)}', "
         . "     RText = '{$this->db->escape($text)}' "
         . " WHERE FK_SID = $this->site_id ";
    $result = $this->db->query($sql);

    $selectedItems = array_keys($post->readArrayIntToInt('rf_page'));

    // First, delete all previously stored pages from database.
    $sql = " DELETE FROM {$this->table_prefix}module_rssfeed_items "
         . " WHERE FK_SID = {$this->site_id} ";
    $this->db->query($sql);

    // Store all selected pages (occur in the rss feed).
    foreach ($selectedItems as $id)
    {
      $sql = " INSERT INTO {$this->table_prefix}module_rssfeed_items (FK_SID, FK_CIID) "
           . " VALUES({$this->site_id}, $id) ";
      $this->db->query($sql);
    }

    $this->setMessage(Message::createSuccess($_LANG['rf_message_success']));
  }

  /**
   * Show content
   */
  private function get_content()
  {
    global $_LANG, $_LANG2;

    $this->_checkDatabase();

    // Get rss feed's common data.
    $sql = ' SELECT RID, RTitle, RText '
         . " FROM {$this->table_prefix}module_rssfeed "
         . " WHERE FK_SID = {$this->site_id} ";
    $row = $this->db->GetRow($sql);
    $title = $row['RTitle'];
    $text = $row['RText'];

    // Retrieve all selected content items. (items displayed in the rss feed)
    $sql = ' SELECT FK_CIID '
         . " FROM {$this->table_prefix}module_rssfeed_items "
         . " WHERE FK_SID = {$this->site_id} ";
    $items = $this->db->GetCol($sql);

    // Retrieve all logical page items.
    $pages = array();
    $sql = ' SELECT CIID, CIIdentifier, CTitle '
         . " FROM {$this->table_prefix}contentitem "
         . ' WHERE CType = 90 '
         . " AND FK_SID = {$this->site_id} "
         . ' ORDER BY CIIdentifier ASC ';
    $result = $this->db->query($sql);

    while ($row = $this->db->fetch_row($result))
    {
      $pages[] = array (
        'rf_page_label' => sprintf($_LANG['rf_page_label'], parseOutput($row['CTitle']),
                                   $row['CIIdentifier']) ,
        'rf_page' => '<input type="checkbox" name="rf_page['.$row['CIID']
                   . ']" id="rf_page['.$row['CIID'].']" value="1" class="rf_page"'
                   . (in_array($row['CIID'],$items) ? ' checked="checked"' : '').' />',
        'rf_link' => edwin_url() . 'index.php?action=content&amp;site='
                   . $this->site_id.'&amp;page='.$row['CIID'],
      );
    }
    $this->db->free_result($result);

    $this->tpl->load_tpl('content_rssfeed', 'modules/ModuleRssFeed.tpl');
    $this->tpl->parse_if('content_rssfeed', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('rf'));
    $this->tpl->parse_loop('content_rssfeed', $pages, 'pages');
    $content = $this->tpl->parsereturn('content_rssfeed', array_merge(array (
        'rf_function_label' => $_LANG["rf_function_label"],
        'rf_description_label' => $_LANG["rf_description_label"],
        'rf_description' => $_LANG["rf_description"],
        'rf_site_selection' => $this->_parseModuleSiteSelection('rssfeed', $_LANG['rf_site_label']),
        'rf_action' => 'index.php?action=mod_rssfeed',
        'rf_hidden_fields' => '<input type="hidden" name="site" value="' . $this->site_id . '" />',
        'rf_title' => $title,
        'rf_text' => $text,
        'rf_pagelisting_label' => $_LANG["rf_pagelisting_label"],
        'rf_title_label' => $_LANG["rf_title_label"],
        'rf_text_label' => $_LANG["rf_text_label"],
        'rf_module_action_boxes' => $this->_getContentActionBoxes(),
    ), $_LANG2['rf']));

    return array(
        'content' => $content,
    );
  }

  /**
   * Ensure that all necessary database entries exist and create them if
   * necessary.
   */
  protected function _checkDatabase()
  {
    $sql = ' SELECT RID '
         . " FROM {$this->table_prefix}module_rssfeed "
         . " WHERE FK_SID = {$this->site_id} ";
    $exists = $this->db->GetOne($sql);
    if ($exists) {
      return;
    }

    // insert the rss feed for the current site
    $sql = " INSERT INTO {$this->table_prefix}module_rssfeed (FK_SID) "
         . " VALUES ({$this->site_id}) ";
    $this->db->query($sql);
  }
}