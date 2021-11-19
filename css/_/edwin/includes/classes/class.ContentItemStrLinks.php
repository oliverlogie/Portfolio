<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2016-03-18 09:23:59 +0100 (Fr, 18 Mär 2016) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2010 Q2E GmbH
 */
class ContentItemStrLinks extends ContentItem
{
  /**
   * Creates a structure link if the POST parameter process_sl_create is set.
   */
  private function _createLink()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!isset($_POST['process_sl_create'])) {
      return;
    }

    list($link, $linkID) = $post->readContentItemLink('sl_link');

    if (!$linkID) {
      $this->setMessage(Message::createFailure($_LANG['sl_message_insufficient_input']));
      return;
    }

    $sql = ' SELECT ci.CIID, ci.FK_SID, ci.CTitle, ci.CIIdentifier '
         . " FROM {$this->table_prefix}structurelink sl "
         . " JOIN {$this->table_prefix}contentitem ci "
         . '      ON sl.FK_CIID = ci.CIID '
         . " WHERE FK_CIID_Link = $linkID ";
    $row = $this->db->GetRow($sql);

    if ($row)
    {
      // structure link exists on this page
      if ($row['CIID'] == $this->page_id) {
        $str = $_LANG['sl_message_duplicate_link'];
      }
      // structure link exists on another page
      else {
        $link = 'index.php?action=strlinks&amp;site='.$row['FK_SID'].'&amp;page='.$row['CIID'];
        $str = sprintf($_LANG['sl_message_duplicate_link_on_page'], $link,
                       parseOutput($row['CIIdentifier']), parseOutput($row['CTitle']));
      }

      $this->setMessage(Message::createFailure($str));
      unset($_POST['sl_link'],
            $_POST['sl_link_id']);
      return;
    }


    $sql = "INSERT INTO {$this->table_prefix}structurelink "
         . '(FK_CIID, FK_CIID_Link) '
         . "VALUES($this->page_id, $linkID) ";
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['sl_message_create_success']));

    unset($_POST['sl_link'],
          $_POST['sl_link_id']);
  }

  /**
   * Deletes a structure link if the GET parameter deleteStructureLinkID is set.
   */
  private function _deleteLink()
  {
    global $_LANG;

    if (!isset($_GET['deleteStructureLinkID'])) {
      return;
    }

    $id = (int)$_GET['deleteStructureLinkID'];

    // delete internal link
    $sql = " DELETE FROM {$this->table_prefix}structurelink "
         . " WHERE SLID = $id ";
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['sl_message_delete_success']));
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Edit Content                                                                          //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function edit_content()
  {
    // not used
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                                        //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function delete_content()
  {
    // not used, see method _deleteInternalLink()
  }

  public function get_content($params = array())
  {
    global $_LANG;

    // Perform create/update/move/delete of an internal link if necessary
    $this->_createLink();
    $this->_deleteLink();

    $currentPage = $this->_navigation->getCurrentPage();

    // read links
    $items = array();
    $sql = ' SELECT SLID, FK_CIID_Link, CIIdentifier '
         . " FROM {$this->table_prefix}structurelink sl "
         . " LEFT JOIN {$this->table_prefix}contentitem ci ON CIID = FK_CIID_Link "
         . " WHERE sl.FK_CIID = $this->page_id "
         . ' ORDER BY FK_SID ';
    $result = $this->db->query($sql);
    $linkCount = $this->db->num_rows($result);
    $invalidLinks = 0;
    $siteLabels = array();
    while ($row = $this->db->fetch_row($result))
    {
      $class = '';
      // Detect invalid and invisible links.
      if ($row['FK_CIID_Link']) {
        $linkedPage = $this->_navigation->getPageByID((int)$row['FK_CIID_Link']);
        if (!$linkedPage->isVisible()) {
          $class = 'invisible';
        }
      } else {
        $class = 'invalid';
        $invalidLinks++;
      }

      // Get the site's label
      $navigationSite = $linkedPage->getSite();
      $label = self::getLanguageSiteLabel($navigationSite);

      $link = $row['CIIdentifier'];
      $linkID = (int)$row['FK_CIID_Link'];
      $items[] = array(
        'sl_link' => $link,
        'sl_link_id' => $linkID,
        'sl_class' => $class,
        'sl_delete_link' => "index.php?action=strlinks&amp;site=$this->site_id&amp;page=$this->page_id&amp;deleteStructureLinkID={$row['SLID']}",
        'sl_site_label' => $label,
      );
    }
    $this->db->free_result($result);

    $contentLeft = '';
    $contentTop = $this->_getContentTop(self::ACTION_STRUCTURELINKS);

    if ($invalidLinks) {
      $this->setMessage(Message::createFailure(sprintf($_LANG['sl_message_invalid_links'], $invalidLinks)));
    }

    $action = 'index.php';
    $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                  . '<input type="hidden" name="page" value="' . $this->page_id . '" />'
                  . '<input type="hidden" name="action" value="' . $this->action . '" />';

    $autocompleteUrl = "index.php?action=response&site=$this->site_id"
                     . "&page=$this->page_id&request=ContentItemAutoComplete"
                     . "&excludeContentItems=$this->page_id&scope=global"
                     . "&includeContentTypeIDs=".$currentPage->getContentTypeId()
                     . "&excludeSiteIDs=".$this->site_id;

    $this->tpl->load_tpl('content_strlinks', 'content_strlinks.tpl');
    $this->tpl->parse_if('content_strlinks', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('sl'));
    $this->tpl->parse_loop('content_strlinks', $items, 'entries');
    $content = $this->tpl->parsereturn('content_strlinks', array(
      'sl_action' => $action,
      'sl_hidden_fields' => $hiddenFields,
      'sl_link' => isset($_POST['sl_link']) ? $_POST['sl_link'] : '',
      'sl_link_id' => isset($_POST['sl_link_id']) ? $_POST['sl_link_id'] : 0,
      'sl_count' => $linkCount,
      'sl_site' => $this->site_id,
      'sl_autocomplete_contentitem_url' => $autocompleteUrl,
    ));

    return array('content' => $content,
                 'content_left' => $contentLeft,
                 'content_top' => $contentTop,
                 'content_contenttype' => 'ContentItemIntLinks',
    );
  }
}

