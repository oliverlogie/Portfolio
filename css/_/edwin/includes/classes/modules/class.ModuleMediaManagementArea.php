<?php

/**
 * MediaManagementArea Module Class
 *
 * $LastChangedDate: 2014-04-08 09:53:37 +0200 (Di, 08 Apr 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Stefan Podskubka
 * @copyright (c) 2009 Q2E GmbH
 */
class ModuleMediaManagementArea extends Module
{
  /**
   * Read all content items from the database that contain download areas.
   *
   * @return array
   *        Contains a list of content items (CIID -> CTitle, CPosition,
   *        FK_CIID, FK_SID, CNT_Files, DAID).
   */
  private function _getDownloadContentItemsList()
  {
    $sql = 'SELECT CIID, CIIdentifier, CTitle, CPosition, ci.FK_CIID, ci.FK_SID, '
         . '       count(DFID) AS CNT_Files '
         . "FROM {$this->table_prefix}contentitem ci "
         . "JOIN {$this->table_prefix}contentitem_dl_area cidla ON ci.CIID = cidla.FK_CIID "
         . "LEFT JOIN {$this->table_prefix}contentitem_dl_area_file cidlaf ON cidla.DAID = cidlaf.FK_DAID "
         . "WHERE FK_SID = $this->site_id "
         . 'GROUP BY CIID, CIIdentifier, CTitle, CPosition, ci.FK_CIID, ci.FK_SID '
         . 'ORDER BY CIIdentifier ';
    return $this->db->GetAssoc($sql);
  }

  /**
   * show a list containing all decentral files
   *
   * @return array contains backend content
   */
  private function show_list()
  {
    global $_LANG, $_LANG2;

    // read content items and decentral files from the database
    $contentitems_list = $this->_getDownloadContentItemsList();

    $contentitems_items = array();
    if ($contentitems_list) {
      // add content items to $contentitems_items
      foreach ($contentitems_list as $ciid => $ci) {
        $sql = 'SELECT DAID, DAPosition '
             . "FROM {$this->table_prefix}contentitem_dl_area "
             . "WHERE FK_CIID = $ciid "
             . 'ORDER BY DAPosition '
             . 'LIMIT 1 ';
        $area = $this->db->GetRow($sql);
        $contentitems_items[] = array(
          'ma_contentitem_link' => "index.php?action=content&amp;site={$ci['FK_SID']}&amp;page=$ciid&amp;area={$area['DAID']}#a_area{$area['DAPosition']}_files",
          'ma_contentitem_title' => parseOutput($this->_getHierarchicalTitle($ciid, ConfigHelper::get('hierarchical_title_separator', 'ma'))),
          'ma_file_count' => $ci['CNT_Files'],
        );
      }
    }
    else {
      $this->setMessage(Message::createFailure($_LANG['ma_message_no_area']));
    }

    // parse list template
    $this->tpl->load_tpl("content_arealist", "modules/ModuleMediaManagementArea_list.tpl");
    $this->tpl->parse_if('content_arealist', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('ma'));
    $this->tpl->parse_loop("content_arealist", $contentitems_items, "contentitems");
    $ma_content = $this->tpl->parsereturn("content_arealist", array_merge($_LANG2["ma"], array(
      "ma_action" => "index.php?action=mod_mediamanagement&amp;action2=area",
      "ma_site_selection" => $this->_parseModuleSiteSelection('mediamanagement', $_LANG["ma_site_label"], 'area'),
      "ma_list_label" => $_LANG["ma_function_list_label"],
      "ma_list_label2" => $_LANG["ma_function_list_label2"],
    )));

    return array(
        'content' => $ma_content,
    );
  }

  // Public functions
  ///////////////////
  public function show_innercontent () {
    return $this->show_list();
  }
}

