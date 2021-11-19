<?php

/**
 * DownloadTicker Module Class
 *
 * $LastChangedDate: 2014-04-08 09:53:37 +0200 (Di, 08 Apr 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Stefan Podskubka
 * @copyright (c) 2009 Q2E GmbH
 */
class ModuleDownloadTicker extends Module
{
  /**
   * Creates a top-download if the POST parameter process_dt_create is set.
   */
  private function _createTopDownload()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!isset($_POST["process_dt_create"])) {
      return;
    }

    list($download, $downloadType, $downloadID) = $post->readDownloadLink('dt_download');
    $downloadTitle = $post->readString('dt_download_title', Input::FILTER_RIGHT_TITLE);
    list($link, $linkID) = $post->readContentItemLink('dt_link');
    $linkTitle = $post->readString('dt_link_title', Input::FILTER_RIGHT_TITLE);

    if (!$downloadType || !$downloadID || !$linkID) {
      $this->setMessage(Message::createFailure($_LANG["dt_message_insufficient_input"]));
      return;
    }

    $sql = 'SELECT COUNT(DTID) + 1 '
         . "FROM {$this->table_prefix}module_downloadticker "
         . "WHERE FK_SID = $this->site_id ";
    $position = $this->db->GetOne($sql);

    switch ($downloadType) {
      case 'file':
        $sql = "INSERT INTO {$this->table_prefix}module_downloadticker "
             . '(DTPosition, FK_FID, DTDownloadTitle, FK_CIID, DTLinkTitle, FK_SID) '
             . "VALUES($position, $downloadID, '{$this->db->escape($downloadTitle)}', "
             . "       $linkID, '{$this->db->escape($linkTitle)}', $this->site_id) ";
        $this->db->query($sql);
        break;
      case 'dlfile':
        $sql = "INSERT INTO {$this->table_prefix}module_downloadticker "
             . '(DTPosition, FK_DFID, DTDownloadTitle, FK_CIID, DTLinkTitle, FK_SID) '
             . "VALUES($position, $downloadID, '{$this->db->escape($downloadTitle)}', "
             . "       $linkID, '{$this->db->escape($linkTitle)}', $this->site_id) ";
        $this->db->query($sql);
        break;
      case 'centralfile':
        $sql = "INSERT INTO {$this->table_prefix}module_downloadticker "
             . '(DTPosition, FK_CFID, DTDownloadTitle, FK_CIID, DTLinkTitle, FK_SID) '
             . "VALUES($position, $downloadID, '{$this->db->escape($downloadTitle)}', "
             . "       $linkID, '{$this->db->escape($linkTitle)}', $this->site_id) ";
        $this->db->query($sql);
        break;
      default:
        trigger_error("Unknown download type '$downloadType'.", E_USER_WARNING);
        $this->setMessage(Message::createFailure($_LANG['dt_message_insufficient_input']));
        return;
    }

    $this->setMessage(Message::createSuccess($_LANG['dt_message_newitem_success']));

    unset($_POST['dt_download']);
    unset($_POST['dt_download_type']);
    unset($_POST['dt_download_id']);
    unset($_POST['dt_download_title']);
    unset($_POST['dt_link']);
    unset($_POST['dt_link_id']);
    unset($_POST['dt_link_title']);
  }

  /**
   * Updates a top-download if the POST parameter process_dt_edit is set.
   */
  private function _updateTopDownload()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!isset($_POST["process_dt_edit"])) {
      return;
    }

    $ID = $post->readKey('process_dt_edit');

    list($download, $downloadType, $downloadID) = $post->readDownloadLink('dt_download');
    $downloadTitle = $post->readString('dt_download_title', Input::FILTER_RIGHT_TITLE);
    list($link, $linkID) = $post->readContentItemLink('dt_link');
    $linkTitle = $post->readString('dt_link_title', Input::FILTER_RIGHT_TITLE);

    if (!$downloadType || !$downloadID || !$linkID) {
      $this->setMessage(Message::createFailure($_LANG["dt_message_insufficient_input"]));
      $_GET["editID"] = $ID;
      return;
    }

    switch ($downloadType) {
      case 'file':
        $sql = "UPDATE {$this->table_prefix}module_downloadticker "
             . "SET FK_FID = $downloadID, "
             . '    FK_DFID = NULL, '
             . '    FK_CFID = NULL, '
             . "    DTDownloadTitle = '{$this->db->escape($downloadTitle)}', "
             . "    FK_CIID = $linkID, "
             . "    DTLinkTitle = '{$this->db->escape($linkTitle)}' "
             . "WHERE DTID = $ID ";
        $this->db->query($sql);
        break;
      case 'dlfile':
        $sql = "UPDATE {$this->table_prefix}module_downloadticker "
             . 'SET FK_FID = NULL, '
             . "    FK_DFID = $downloadID, "
             . '    FK_CFID = NULL, '
             . "    DTDownloadTitle = '{$this->db->escape($downloadTitle)}', "
             . "    FK_CIID = $linkID, "
             . "    DTLinkTitle = '{$this->db->escape($linkTitle)}' "
             . "WHERE DTID = $ID ";
        $this->db->query($sql);
        break;
      case 'centralfile':
        $sql = "UPDATE {$this->table_prefix}module_downloadticker "
             . 'SET FK_FID = NULL, '
             . '    FK_DFID = NULL, '
             . "    FK_CFID = $downloadID, "
             . "    DTDownloadTitle = '{$this->db->escape($downloadTitle)}', "
             . "    FK_CIID = $linkID, "
             . "    DTLinkTitle = '{$this->db->escape($linkTitle)}' "
             . "WHERE DTID = $ID ";
        $this->db->query($sql);
        break;
      default:
        trigger_error("Unknown download type '$downloadType'.", E_USER_WARNING);
        $this->setMessage(Message::createFailure($_LANG['dt_message_insufficient_input']));
        return;
    }

    $this->setMessage(Message::createSuccess($_LANG['dt_message_edititem_success']));
  }

  /**
   * Moves a top-download if the GET parameters moveID and moveTo are set.
   */
  private function _moveTopDownload()
  {
    global $_LANG;

    if (!isset($_GET["moveID"], $_GET["moveTo"])) {
      return;
    }

    $moveID = (int)$_GET["moveID"];
    $moveTo = (int)$_GET["moveTo"];

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_downloadticker",
                                         'DTID', 'DTPosition',
                                         'FK_SID', $this->site_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['dt_message_move_success']));
    }
  }

  /**
   * Delete download ticker file link entry.
   *
   * @param int $dtid
   *        The download ticker id.
   */
  public static function deleteFilelink($dtid, $db, $tablePrefix, $siteId)
  {
    if (!$dtid) return;

    $sql = 'SELECT DTPosition '
         . "FROM {$tablePrefix}module_downloadticker "
         . "WHERE DTID = $dtid ";
    $deletedPosition = $db->GetOne($sql);

    // delete top-download database entry
    $sql = "DELETE FROM {$tablePrefix}module_downloadticker "
         . "WHERE DTID = $dtid ";
    $db->query($sql);

    // move following top-downloads one position up
    $sql = "UPDATE {$tablePrefix}module_downloadticker "
         . 'SET DTPosition = DTPosition - 1 '
         . "WHERE DTPosition > $deletedPosition "
         . "AND FK_SID = $siteId "
         . 'ORDER BY DTPosition ASC ';
    $db->query($sql);
  }

  /**
   * Deletes a top-download if the GET parameter deleteID is set.
   */
  private function _deleteTopDownload()
  {
    global $_LANG;

    if (!isset($_GET["deleteID"])) {
      return;
    }

    $dtid = (int)$_GET["deleteID"];

    self::deleteFilelink($dtid, $this->db, $this->table_prefix, $this->site_id);

    $this->setMessage(Message::createSuccess($_LANG["dt_message_deleteitem_success"]));
  }

  private function _showList()
  {
    global $_LANG, $_LANG2;

    $post = new Input(Input::SOURCE_POST);

    $editTopDownloadID = isset($_GET["editID"]) ? (int)$_GET["editID"] : 0;
    $editTopDownloadData = array();

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}module_downloadticker",
                                         'DTID', 'DTPosition',
                                         'FK_SID', $this->site_id);

    $sql = 'SELECT DTDownloadTitle, DTLinkTitle, DTID, DTPosition, '
         . '       FID, FTitle, FFile, '
         . '       DFID, DFTitle, DFFile, '
         . '       CFID, CFTitle, CFFile, '
         . '       CIID, CTitle, CIIdentifier '
         . "FROM {$this->table_prefix}module_downloadticker mdt "
         . "LEFT JOIN {$this->table_prefix}file f ON mdt.FK_FID = f.FID "
         . "LEFT JOIN {$this->table_prefix}contentitem_dl_area_file cidlaf ON mdt.FK_DFID = cidlaf.DFID "
         . "LEFT JOIN {$this->table_prefix}centralfile cf ON mdt.FK_CFID = cf.CFID "
         . "LEFT JOIN {$this->table_prefix}contentitem ci ON mdt.FK_CIID = ci.CIID "
         . "WHERE mdt.FK_SID = $this->site_id "
         . 'ORDER BY DTPosition ';
    $result = $this->db->query($sql);

    $topDownloadCount = $this->db->num_rows($result);
    $topDownloads = array();
    $invalidLinks = 0;
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['DTPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['DTPosition']);

      $topDownloadClass = 'normal';
      if ($row['DTID'] == $editTopDownloadID) {
        $topDownloadClass = 'edit';
      }
      // detect invalid download links
      if (!$row['FID'] && !$row['DFID'] && !$row['CFID']) {
        $topDownloadClass = 'invalid';
        $invalidLinks++;
      }
      // Detect invalid and invisible links.
      if ($row['CIID']) {
        $linkedPage = $this->_navigation->getPageByID((int)$row['CIID']);
        if (!$linkedPage->isVisible()) {
          $topDownloadClass = 'invisible';
        }
      } else {
        $topDownloadClass = 'invalid';
        $invalidLinks++;
      }

      $downloadTitle = coalesce($row['DTDownloadTitle'], $row['FTitle'], $row['DFTitle'], $row['CFTitle']);
      $linkTitle = coalesce($row['DTLinkTitle'], $row['CTitle']);
      $file = coalesce($row['FFile'], $row['DFFile'], $row['CFFile']);

      $topDownloads[$row['DTID']] = array(
        'dt_id' => $row['DTID'],
        'dt_position' => $row['DTPosition'],
        'dt_download_title' => parseOutput($downloadTitle),
        'dt_download_title_type' => $row['DTDownloadTitle'] ? 'overridden' : 'inherited',
        'dt_download_title_custom' => parseOutput($row['DTDownloadTitle']),
        'dt_download_file' => "../$file",
        'dt_download_filename' => parseOutput(mb_substr(mb_strrchr("../$file", '/'), 1)),
        'dt_download' => parseOutput(mb_substr(mb_strrchr("../$file", '/'), 1)),
        'dt_download_type' => $row['FID'] ? 'file' : ($row['DFID'] ? 'dlfile' : 'centralfile'),
        'dt_download_id' => coalesce($row['FID'], $row['DFID'], $row['CFID']),
        'dt_download_size' => formatFileSize(filesize("../$file")),
        'dt_link_title' => parseOutput($linkTitle),
        'dt_link_title_type' => $row['DTLinkTitle'] ? 'overridden' : 'inherited',
        'dt_link_title_custom' => parseOutput($row['DTLinkTitle']),
        'dt_link' => $row['CIIdentifier'],
        'dt_link_id' => $row['CIID'],
        'dt_class' => $topDownloadClass,
        'dt_edit_link' => "index.php?action=mod_downloadticker&amp;site=$this->site_id&amp;editID={$row['DTID']}",
        'dt_move_up_link' => "index.php?action=mod_downloadticker&amp;site=$this->site_id&amp;moveID={$row['DTID']}&amp;moveTo=$moveUpPosition",
        'dt_move_down_link' => "index.php?action=mod_downloadticker&amp;site=$this->site_id&amp;moveID={$row['DTID']}&amp;moveTo=$moveDownPosition",
        'dt_delete_link' => "index.php?action=mod_downloadticker&amp;site=$this->site_id&amp;deleteID={$row['DTID']}",
      );

      // this row has to be edited
      if ($row['DTID'] == $editTopDownloadID) {
        $dt_download = $topDownloads[$row['DTID']]['dt_download'];
        $dt_download_type = $topDownloads[$row['DTID']]['dt_download_type'];
        $dt_download_id = $topDownloads[$row['DTID']]['dt_download_id'];
        $dt_download_title = $topDownloads[$row['DTID']]['dt_download_title_custom'];
        $dt_link = $topDownloads[$row['DTID']]['dt_link'];
        $dt_link_id = $topDownloads[$row['DTID']]['dt_link_id'];
        $dt_link_title = $topDownloads[$row['DTID']]['dt_link_title_custom'];
        if (isset($_POST['dt_download_edit'])) {
          $dt_download = $post->readString('dt_download_edit', Input::FILTER_PLAIN);
        }
        if (isset($_POST['dt_download_type_edit'])) {
          $dt_download_type = $post->readString('dt_download_type_edit', Input::FILTER_PLAIN);
        }
        if (isset($_POST['dt_download_id_edit'])) {
          $dt_download_id = (int)$_POST['dt_download_id_edit'];
        }
        if (isset($_POST['dt_download_title_edit'])) {
          $dt_download_title = $post->readString('dt_download_title_edit', Input::FILTER_RIGHT_TITLE);
        }
        if (isset($_POST['dt_link_edit'])) {
          $dt_link = trim($post->readString('dt_link_edit', Input::FILTER_PLAIN));
        }
        if (isset($_POST['dt_link_id_edit'])) {
          $dt_link_id = (int)$_POST['dt_link_id_edit'];
        }
        if (isset($_POST['dt_link_title_edit'])) {
          $dt_link_title = $post->readString('dt_link_title_edit', Input::FILTER_RIGHT_TITLE);
        }
        $editTopDownloadData = array(
          'dt_id' => $row['DTID'],
          'dt_download_edit' => parseOutput($dt_download),
          'dt_download_type_edit' => parseOutput($dt_download_type),
          'dt_download_id_edit' => $dt_download_id,
          'dt_download_title_edit' => parseOutput($dt_download_title),
          'dt_link_edit' => parseOutput($dt_link),
          'dt_link_id_edit' => $dt_link_id,
          'dt_link_title_edit' => parseOutput($dt_link_title),
        );
      }
    }
    $this->db->free_result($result);
    if (!$topDownloads) {
      $this->setMessage(Message::createFailure($_LANG['dt_message_no_topdownload']));
    } else if ($invalidLinks) {
      $this->setMessage(Message::createFailure(sprintf($_LANG['dt_message_invalid_links'], $invalidLinks)));
    }

    $maximumReached = count($topDownloads) >= ConfigHelper::get('dt_number_of_topdownloads');

    // parse list template
    $this->tpl->load_tpl('downloadticker', 'modules/ModuleDownloadTicker.tpl');

    $this->tpl->parse_if('downloadticker', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('dt'));
    $this->tpl->parse_if('downloadticker', 'entry_create', !$maximumReached, array(
      'dt_download' => parseOutput($post->readString('dt_download', Input::FILTER_PLAIN)),
      'dt_download_type' => parseOutput($post->readString('dt_download_type', Input::FILTER_PLAIN)),
      'dt_download_id' => $post->readInt('dt_download_id'),
      'dt_download_title' => parseOutput($post->readString('dt_download_title', Input::FILTER_RIGHT_TITLE)),
      'dt_link' => parseOutput($post->readString('dt_link', Input::FILTER_PLAIN)),
      'dt_link_id' => $post->readInt('dt_link_id'),
      'dt_link_title' => parseOutput($post->readString('dt_link_title', Input::FILTER_RIGHT_TITLE)),
    ));
    $this->tpl->parse_if('downloadticker', 'entries_maximum_reached', $maximumReached);
    $this->tpl->parse_if('downloadticker', 'entry_edit', $editTopDownloadData, $editTopDownloadData);
    $this->tpl->parse_loop('downloadticker', $topDownloads, 'entries');
    $dt_content = $this->tpl->parsereturn('downloadticker', array_merge(array(
      'dt_site' => $this->site_id,
      'dt_action' => 'index.php?action=mod_downloadticker',
      'dt_site_selection' => $this->_parseModuleSiteSelection('downloadticker', $_LANG['dt_site_label']),
      'dt_list_label' => $_LANG['dt_function_list_label'],
      'dt_list_label2' => $_LANG['dt_function_list_label2'],
      'dt_autocomplete_download_url' => "index.php?action=mod_response_downloadticker&site=$this->site_id&request=DownloadAutoComplete",
      'dt_autocomplete_contentitem_url' => "index.php?action=mod_response_downloadticker&site=$this->site_id&request=ContentItemAutoComplete",
      'dt_dragdrop_link_js' => "index.php?action=mod_downloadticker&site=$this->site_id&moveID=#moveID#&moveTo=#moveTo#",
    ), $_LANG2['dt']));

    return array(
      'content' => $dt_content,
    );
  }

  // Public functions
  ///////////////////
  public function show_innercontent () {
    // Perform create/update/move/delete of a download if necessary
    $this->_createTopDownload();
    $this->_updateTopDownload();
    $this->_moveTopDownload();
    $this->_deleteTopDownload();

    return $this->_showList();
  }
}

