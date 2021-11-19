<?php

  /**
   * Content Class
   *
   * $LastChangedDate: 2017-10-16 07:32:10 +0200 (Mo, 16 Okt 2017) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */
class ContentItemFiles extends ContentItem
{

  /**
   * Checks if a central file link already exists
   * @param int $fileId
   *            File id of central file to check
   * @param int $editId
   *            Id of the link that should be edited
   * @return boolean true if file link already exists
   */
  private function _checkMultipleLinks($fileId, $editId = 0)
  {
    global $_LANG;

    $editId = ($editId) ? 'AND FID != '.$editId : '';
    $sql = 'SELECT FID '
         . "FROM {$this->table_prefix}file "
         . "WHERE FK_CFID  = {$fileId} "
         . "  AND FK_CIID  = {$this->page_id} "
         . " $editId ";

    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG['fi_message_multiple_link_failure']));
      return true;
    }

    return false;
  }

  /**
   * Creates a file if the POST parameter process_fi_create is set.
   */
  private function _createFile()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!isset($_POST['process_fi_create'])) {
      return;
    }

    $title = $post->readString('fi_title', Input::FILTER_RIGHT_TITLE);

    // by default we expect an upload (if the ModuleMediaManagement is disabled)
    $kind = $post->readString('fi_kind', Input::FILTER_PLAIN, 'newupload');
    $created = false;
    switch ($kind) {
      case 'newupload':
        $created = $this->createFileNewUpload($title, $_FILES['fi_upload']);
        break;
      case 'centralfile':
        $created = $this->_createFileCentralFile($title);
        break;
      default:
        // an invalid kind should be handled like no kind, but additionally
        // an error should be triggered
        trigger_error("Unknown file kind '$kind'.", E_USER_WARNING);
        $this->setMessage(Message::createFailure($_LANG['fi_message_insufficient_input']));
        return;
    }

    if ($created) {
      // add the title to the search index of the content item
      $this->_spiderDownload($title, $this->page_id, true);
    }
  }

  /**
   * Creates a file from an upload.
   *
   * @param string $title
   *        The file title that was entered by the user (read by the caller).
   * @param array $fileArray
   * @return bool
   *        true, if the file was created, otherwise false.
   *        This is used to tell the caller if spidering is necessary.
   */
  public function createFileNewUpload($title, $fileArray)
  {
    global $_LANG;

    if (!$title) {
      $this->setMessage(Message::createFailure($_LANG['fi_message_insufficient_input']));
      return false;
    }

    $upload = $this->_storeFile($fileArray,
                                ConfigHelper::get('fi_file_size'),
                                ConfigHelper::get('fi_file_types'));

    if (!$upload) {
      $this->setMessage(Message::createFailure($_LANG['fi_message_insufficient_input']));
      return;
    }

    $sql = 'SELECT COUNT(FID) + 1 '
         . "FROM {$this->table_prefix}file "
         . "WHERE FK_CIID = $this->page_id ";
    $position = $this->db->GetOne($sql);
    $now = date('Y-m-d H:i:s');
    $fileSize = (int)$fileArray['size'];
    $sql = "INSERT INTO {$this->table_prefix}file "
         . '(FTitle, FPosition, FCreated, FFile, FSize, FK_CIID) '
         . "VALUES('{$this->db->escape($title)}', $position, '$now', '$upload', $fileSize, $this->page_id) ";
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['fi_message_create_success']));

    unset($_POST['fi_title']);
    unset($_POST['fi_kind']);
    unset($_POST['fi_file_upload']);
    unset($_POST['fi_file']);
    unset($_POST['fi_file_type']);
    unset($_POST['fi_file_id']);

    return true;
  }

  /**
   * Creates a file from a specified central file.
   *
   * @param string &$title
   *        The file title that was entered by the user (read by the caller).
   *        This is passed as a reference here, because if it is empty the
   *        caller needs to know the title of the central file for spidering.
   * @return bool
   *        true, if the file was created, otherwise false.
   *        This is used to tell the caller if spidering is necessary.
   */
  private function _createFileCentralFile(&$title)
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    list($file, $fileType, $fileID) = $post->readDownloadLink('fi_file');

    if (!$fileType || !$fileID || $fileType != 'centralfile') {
      $this->setMessage(Message::createFailure($_LANG['fi_message_insufficient_input']));
      return false;
    }

    // A central download must not be linked more than once
    if ($this->_checkMultipleLinks($fileID))
      return false;

    $sql = 'SELECT COUNT(FID) + 1 '
         . "FROM {$this->table_prefix}file "
         . "WHERE FK_CIID = $this->page_id ";
    $position = $this->db->GetOne($sql);
    $now = date('Y-m-d H:i:s');
    $sql = "INSERT INTO {$this->table_prefix}file "
         . '(FTitle, FPosition, FCreated, FK_CFID, FK_CIID) '
         . "VALUES('{$this->db->escape($title)}', $position, '$now', $fileID, $this->page_id) ";
    $result = $this->db->query($sql);

    // if the file has no custom title we pass back the title of the
    // central file for spidering
    if (!$title) {
      $sql = 'SELECT CFTitle '
           . "FROM {$this->table_prefix}centralfile "
           . "WHERE CFID = $fileID ";
      $title = $this->db->GetOne($sql);
    }

    $this->setMessage(Message::createSuccess($_LANG['fi_message_create_success']));

    unset($_POST['fi_title']);
    unset($_POST['fi_kind']);
    unset($_POST['fi_file_upload']);
    unset($_POST['fi_file']);
    unset($_POST['fi_file_type']);
    unset($_POST['fi_file_id']);

    return true;
  }

  /**
   * Updates a file if the POST parameter process_fi_edit is set.
   */
  private function _updateFile()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!isset($_POST['process_fi_edit'])) {
      return;
    }

    $ID = $post->readKey('process_fi_edit');

    // read the old title for de-spidering after the update
    $sql = 'SELECT FTitle, CFTitle '
         . "FROM {$this->table_prefix}file "
         . "LEFT JOIN {$this->table_prefix}centralfile ON FK_CFID = CFID "
         . "WHERE FID = $ID ";
    $row = $this->db->GetRow($sql);
    $oldTitle = coalesce($row['FTitle'], $row['CFTitle']);

    $title = $post->readString("fi{$ID}_title", Input::FILTER_RIGHT_TITLE);

    // by default we expect an upload (if the ModuleMediaManagement is disabled)
    $kind = $post->readString("fi{$ID}_kind", Input::FILTER_PLAIN,'newupload');
    $updated = false;
    switch ($kind) {
      case 'existingupload':
        $updated = $this->_updateFileExistingUpload($ID, $title);
        break;
      case 'newupload':
        $updated = $this->_updateFileNewUpload($ID, $title);
        break;
      case 'centralfile':
        $updated = $this->_updateFileCentralFile($ID, $title);
        break;
      default:
        // an invalid kind should be handled like no kind, but additionally
        // an error should be triggered
        trigger_error("Unknown file kind '$kind'.", E_USER_WARNING);
        $this->setMessage(Message::createFailure($_LANG['fi_message_insufficient_input']));
        $_GET['editFileID'] = $ID;
        return;
    }

    if ($updated) {
      // if the title was changed we have to update the search index of the content item
      if ($title != $oldTitle) {
        $this->_spiderDownload($oldTitle, $this->page_id, false);
        $this->_spiderDownload($title, $this->page_id, true);
      }
    }
  }

  /**
   * Updates a file with an existing upload (title only).
   *
   * @param int $ID
   *        The ID of the edited file (read by the caller).
   * @param string $title
   *        The file title that was entered by the user (read by the caller).
   * @return bool
   *        true, if the file was updated, otherwise false.
   *        This is used to tell the caller if spidering is necessary.
   */
  private function _updateFileExistingUpload($ID, $title)
  {
    global $_LANG;

    if (!$title) {
      $this->setMessage(Message::createFailure($_LANG['fi_message_insufficient_input']));
      $_GET['editFileID'] = $ID;
      return false;
    }

    $sql = "UPDATE {$this->table_prefix}file "
         . "SET FTitle = '{$this->db->escape($title)}' "
         . "WHERE FID = $ID ";
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['fi_message_update_success']));

    return true;
  }

  /**
   * Updates a file from a new upload.
   *
   * @param int $ID
   *        The ID of the edited file (read by the caller).
   * @param string $title
   *        The file title that was entered by the user (read by the caller).
   * @return bool
   *        true, if the file was updated, otherwise false.
   *        This is used to tell the caller if spidering is necessary.
   */
  private function _updateFileNewUpload($ID, $title)
  {
    global $_LANG;

    if (!$title) {
      $this->setMessage(Message::createFailure($_LANG['fi_message_insufficient_input']));
      $_GET['editFileID'] = $ID;
      return false;
    }

    $sql = 'SELECT FFile '
         . "FROM {$this->table_prefix}file "
         . "WHERE FID = $ID ";
    $existingUpload = $this->db->GetOne($sql);

    $fileArray = $_FILES["fi{$ID}_upload"];
    $newUpload = $this->_storeFile($fileArray,
                                   ConfigHelper::get('fi_file_size'),
                                   ConfigHelper::get('fi_file_types'),
                                   $existingUpload);

    if (!$newUpload) {
      $this->setMessage(Message::createFailure($_LANG['fi_message_insufficient_input']));
      $_GET['editFileID'] = $ID;
      return false;
    }

    $now = date('Y-m-d H:i:s');
    $fileSize = (int)$fileArray['size'];
    $sql = "UPDATE {$this->table_prefix}file "
         . "SET FTitle = '{$this->db->escape($title)}', "
         . "    FModified = '$now', "
         . "    FFile = '$newUpload', "
         . "    FSize = $fileSize, "
         . '    FK_CFID = NULL '
         . "WHERE FID = $ID ";
    $result = $this->db->query($sql);

    if ($existingUpload && $existingUpload != $newUpload) {
      unlinkIfExists("../$existingUpload");
    }

    $this->setMessage(Message::createSuccess($_LANG['fi_message_update_success']));

    return true;
  }

  /**
   * Updates a file from a specified central file.
   *
   * @param int $ID
   *        The ID of the edited file (read by the caller).
   * @param string &$title
   *        The file title that was entered by the user (read by the caller).
   *        This is passed as a reference here, because if it is empty the
   *        caller needs to know the title of the central file for spidering.
   * @return bool
   *        true, if the file was updated, otherwise false.
   *        This is used to tell the caller if spidering is necessary.
   */
  private function _updateFileCentralFile($ID, &$title)
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $sql = 'SELECT FFile '
         . "FROM {$this->table_prefix}file "
         . "WHERE FID = $ID ";
    $existingUpload = $this->db->GetOne($sql);

    list($file, $fileType, $fileID) = $post->readDownloadLink("fi{$ID}_file");

    if (!$fileType || !$fileID || $fileType != 'centralfile') {
      $this->setMessage(Message::createFailure($_LANG['fi_message_insufficient_input']));
      $_GET['editFileID'] = $ID;
      return false;
    }

    // A central download must not be linked more than once
    if ($this->_checkMultipleLinks($fileID, $ID))
      return false;

    $now = date('Y-m-d H:i:s');
    $sql = "UPDATE {$this->table_prefix}file "
         . "SET FTitle = '{$this->db->escape($title)}', "
         . "    FModified = NULL, "
         . '    FFile = NULL, '
         . '    FSize = 0, '
         . "    FK_CFID = $fileID "
         . "WHERE FID = $ID ";
    $result = $this->db->query($sql);

    if ($existingUpload) {
      unlinkIfExists("../$existingUpload");
    }

    // if the file has no custom title we pass back the title of the
    // central file for spidering
    if (!$title) {
      $sql = 'SELECT CFTitle '
           . "FROM {$this->table_prefix}centralfile "
           . "WHERE CFID = $fileID ";
      $title = $this->db->GetOne($sql);
    }

    $this->setMessage(Message::createSuccess($_LANG['fi_message_update_success']));

    return true;
  }

  /**
   * Moves a file if the GET parameters moveFileID and moveFileTo are set.
   */
  private function _moveFile()
  {
    global $_LANG;

    if (!isset($_GET['moveFileID'], $_GET['moveFileTo'])) {
      return;
    }

    $moveID = (int)$_GET['moveFileID'];
    $moveTo = (int)$_GET['moveFileTo'];

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}file",
                                         'FID', 'FPosition',
                                         'FK_CIID', $this->page_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['fi_message_move_success']));
    }
  }

  /**
   * Deletes a file if the GET parameter deleteFileID is set.
   */
  private function _deleteFile()
  {
    global $_LANG;

    if (!isset($_GET['deleteFileID'])) {
      return;
    }

    $id = (int)$_GET['deleteFileID'];

    // determine title, filename and position of deleted file
    $sql = 'SELECT FTitle, FFile, FPosition, CFTitle '
         . "FROM {$this->table_prefix}file "
         . "LEFT JOIN {$this->table_prefix}centralfile ON FK_CFID = CFID "
         . "WHERE FID = $id ";
    $file = $this->db->GetRow($sql);

    // delete file
    $sql = "DELETE FROM {$this->table_prefix}file "
         . "WHERE FID = $id ";
    $result = $this->db->query($sql);

    // move following files one position up
    $sql = "UPDATE {$this->table_prefix}file "
         . 'SET FPosition = FPosition - 1 '
         . "WHERE FK_CIID = $this->page_id "
         . "AND FPosition > {$file['FPosition']} "
         . 'ORDER BY FPosition ASC ';
    $result = $this->db->query($sql);

    // delete from words file-links
    $sql = "DELETE FROM {$this->table_prefix}contentitem_words_filelink "
         . "WHERE WFFile = '".$file['FFile']."' ";
    $result = $this->db->query($sql);
    $this->db->free_result($result);

    if ($file['FFile']) {
      unlinkIfExists('../' . $file['FFile']);
    }

    // remove the file title from the search index of the content item
    $this->_spiderDownload(coalesce($file['FTitle'], $file['CFTitle']), $this->page_id, false);

    $this->setMessage(Message::createSuccess($_LANG['fi_message_delete_success']));
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Edit Content                                                                          //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function edit_content()
  {
    // not used, see method _updateFile()
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                                        //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function delete_content()
  {
    // not used, see method _deleteFile()
  }

  public function get_content($params = array())
  {
    global $_LANG;

    // Perform create/update/move/delete of a file if necessary
    $this->_createFile();
    $this->_updateFile();
    $this->_moveFile();
    $this->_deleteFile();

    $editFileID = isset($_GET['editFileID']) ? (int)$_GET['editFileID'] : 0;
    $editFileData = array();

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}file",
                                         'FID', 'FPosition',
                                         'FK_CIID', $this->page_id);

    // read files
    $fileItems = array();
    $sql = 'SELECT FID, FTitle, FFile, FCreated, FModified, FPosition, '
         . '       CFID, CFTitle, CFFile, CFCreated, CFModified, FK_SID '
         . "FROM {$this->table_prefix}file f "
         . "LEFT JOIN {$this->table_prefix}centralfile cf "
         . '          ON FK_CFID = CFID '
         . "WHERE f.FK_CIID = $this->page_id "
         . 'ORDER BY FPosition ASC ';
    $result = $this->db->query($sql);
    $fileCount = $this->db->num_rows($result);
    $invalidLinks = 0;
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['FPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['FPosition']);

      $class = 'normal';
      if ($row['FID'] == $editFileID) {
        $class = 'edit';
      }
      if (!$row['FFile'] && !$row['CFID']) {
        $class = 'invalid';
        $invalidLinks++;
      }

      $fileTitle = parseOutput(coalesce($row['FTitle'], $row['CFTitle']));
      $fileLink = '../' . coalesce($row['FFile'], $row['CFFile']);
      // insert into file link user's current session id, we need this to
      // identify the backend user on the frontend
      $filePath = $this->_fileUrlForBackendUser(coalesce($row['FFile'], $row['CFFile']));
      $filename = mb_substr(mb_strrchr('../' . coalesce($row['FFile'], $row['CFFile']), "/"), 1);
      $file = $row['CFID'] ? $filename : '';
      $fileType = $row['FFile'] ? 'file' : 'centralfile';
      $fileID = coalesce($row['CFID'], 0);
      $fileScope = 'local';
      if ((int)$row['CFID'] && (int)$row['FK_SID'] != $this->site_id) {
        $fileScope = 'global';
      }
      $linkedSiteTitle = '';
      if ((int)$row['CFID']) {
        $linkedSite = $this->_navigation->getSiteByID((int)$row['FK_SID']);
        $linkedSiteTitle = $linkedSite->getTitle();
      }
      $fileScopeLabel = sprintf($_LANG["fi_file_scope_{$fileScope}_label"], $linkedSiteTitle);

      $fileItems[$row['FID']] = array(
        'fi_title' => $fileTitle,
        'fi_file_link' => $filePath,
        'fi_filename' => parseOutput($filename),
        'fi_file' => parseOutput($file),
        'fi_file_type' => $fileType,
        'fi_file_id' => $fileID,
        'fi_file_scope' => $fileScope,
        'fi_file_scope_label' => $fileScopeLabel,
        'fi_date' => date($this->_configHelper->getDateFormat($this->_user->getLanguage(), 'fi'), strtotime(coalesce($row['CFModified'], $row['CFCreated'], $row['FModified'], $row['FCreated']))),
        'fi_id' => $row['FID'],
        'fi_position' => $row['FPosition'],
        'fi_size' => formatFileSize(filesize($fileLink)),
        'fi_class' => $class,
        'fi_edit_link' => "index.php?action=files&amp;site=$this->site_id&amp;page=$this->page_id&amp;editFileID={$row['FID']}",
        'fi_move_up_link' => "index.php?action=files&amp;site=$this->site_id&amp;page=$this->page_id&amp;moveFileID={$row['FID']}&amp;moveFileTo=$moveUpPosition",
        'fi_move_down_link' => "index.php?action=files&amp;site=$this->site_id&amp;page=$this->page_id&amp;moveFileID={$row['FID']}&amp;moveFileTo=$moveDownPosition",
        'fi_delete_link' => "index.php?action=files&amp;site=$this->site_id&amp;page=$this->page_id&amp;deleteFileID={$row['FID']}",
      );

      // this row has to be edited
      if ($row['FID'] == $editFileID) {
        $post = new Input(Input::SOURCE_POST);

        $fileKind = $row['CFID'] ? 'centralfile' : 'existingupload';
        if (isset($_POST["fi{$editFileID}_kind"])) {
          $fileKind = $_POST["fi{$editFileID}_kind"];
        }
        if (isset($_POST["fi{$editFileID}_title"])) {
          $fileTitle = $post->readString("fi{$editFileID}_title", Input::FILTER_PLAIN);
        }
        if (isset($_POST["fi{$editFileID}_file"])) {
          $file = trim($post->readString("fi{$editFileID}_file", Input::FILTER_PLAIN));
        }
        if (isset($_POST["fi{$editFileID}_file_type"])) {
          $fileType = $_POST["fi{$editFileID}_file_type"];
        }
        if (isset($_POST["fi{$editFileID}_file_id"])) {
          $fileID = (int)$_POST["fi{$editFileID}_file_id"];
        }
        $editFileData = array(
          'fi_id_edit' => $row['FID'],
          'fi_title_edit' => parseOutput($row['FTitle']),
          'fi_kind_edit' => $fileKind,
          'fi_file_link_edit' => $filePath,
          'fi_filename_edit' => parseOutput($filename),
          'fi_file_edit' => parseOutput($file),
          'fi_file_type_edit' => $fileType,
          'fi_file_id_edit' => $fileID,
          'fi_size_edit' => formatFileSize(filesize($fileLink)),
          'fi_button_cancel_label' => $_LANG['fi_button_cancel_label'],
          'fi_button_edit_label' => $_LANG['fi_button_edit_label'],
        );
      }
    }
    $this->db->free_result($result);

    $sql = 'SELECT MActive '
         . "FROM {$this->table_prefix}moduletype_backend "
         . "WHERE MShortname = 'mediamanagement'";
    $centralFilesAvailable = $this->db->GetOne($sql);

    if ($invalidLinks) {
      $this->setMessage(Message::createFailure(sprintf($_LANG['fi_message_invalid_links'], $invalidLinks)));
    }

    $contentLeft = '';
    $contentTop = $this->_getContentTop(self::ACTION_FILES);

    $action = "index.php";
    $hiddenFields = '<input type="hidden" name="site" value="' . $this->site_id . '" />'
                  . '<input type="hidden" name="page" value="' . $this->page_id . '" />'
                  . '<input type="hidden" name="action" value="' . $this->action . '" />';

    $this->tpl->load_tpl('content_files', 'content_files.tpl');
    $this->tpl->parse_if('content_files', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('fi'));
    $kind = '';
    if (isset($_POST['fi_kind'])) {
      $kind = $_POST['fi_kind'];
    }
    $this->tpl->parse_if('content_files', 'entry_create_kind_newupload', $kind == 'newupload');
    $this->tpl->parse_if('content_files', 'entry_create_kind_centralfile', $kind == 'centralfile');
    // END new entry form
    // fill edit entry form with existing data
    $this->tpl->parse_if('content_files', 'entry_edit', $editFileData, $editFileData);
    if ($editFileData) {
      $this->tpl->parse_if('content_files', 'entry_edit_existingupload', !$editFileData['fi_file_id_edit']);
      $this->tpl->parse_if('content_files', 'entry_edit_kind_existingupload', $editFileData['fi_kind_edit'] == 'existingupload');
      $this->tpl->parse_if('content_files', 'entry_edit_kind_newupload', $editFileData['fi_kind_edit'] == 'newupload');
      $this->tpl->parse_if('content_files', 'entry_edit_kind_centralfile', $editFileData['fi_kind_edit'] == 'centralfile');
    }
    // END edit entry form
    $this->tpl->parse_if('content_files', 'entry_create_newupload_radio_available', $centralFilesAvailable);
    $this->tpl->parse_if('content_files', 'entry_create_centralfile_available', $centralFilesAvailable);
    $this->tpl->parse_if('content_files', 'entry_edit_centralfile_available', $centralFilesAvailable);
    $this->tpl->parse_loop('content_files', $fileItems, 'entries');
    $content = $this->tpl->parsereturn('content_files', array(
      'fi_action' => $action,
      'fi_hidden_fields' => $hiddenFields,
      'fi_function_label' => $_LANG['fi_function_label'],
      'fi_create_label' => $_LANG['fi_create_label'],
      'fi_title' => isset($_POST['fi_title']) ? $_POST['fi_title'] : '',
      'fi_file' => isset($_POST['fi_file']) ? $_POST['fi_file'] : '',
      'fi_file_type' => isset($_POST['fi_file_type']) ? $_POST['fi_file_type'] : '',
      'fi_file_id' => isset($_POST['fi_file_id']) ? $_POST['fi_file_id'] : '',
      'fi_button_create_label' => $_LANG['fi_button_create_label'],
      'fi_existing_label' => $_LANG['fi_existing_label'],
      'fi_count' => $fileCount,
      'fi_title_label' => $_LANG['fi_title_label'],
      'fi_file_label' => $_LANG['fi_file_label'],
      'fi_filename_label' => $_LANG['fi_filename_label'],
      'fi_date_label' => $_LANG['fi_date_label'],
      'fi_kind_existingupload_label' => $_LANG['fi_kind_existingupload_label'],
      'fi_kind_newupload_label' => $_LANG['fi_kind_newupload_label'],
      'fi_kind_centralfile_label' => $_LANG['fi_kind_centralfile_label'],
      'fi_edit_label' => $_LANG['fi_edit_label'],
      'fi_move_up_label' => $_LANG['fi_move_up_label'],
      'fi_move_down_label' => $_LANG['fi_move_down_label'],
      'fi_move_label' => $_LANG['fi_move_label'],
      'fi_delete_label' => $_LANG['fi_delete_label'],
      'fi_site' => $this->site_id,
      'fi_delete_question_label' => $_LANG['fi_delete_question_label'],
      'fi_autocomplete_download_url' => "index.php?action=response&site=$this->site_id&page=$this->page_id&request=DownloadAutoComplete&downloadTypes=centralfile&scope=global",
      'fi_dragdrop_link_js' => "index.php?action=files&site=$this->site_id&page=$this->page_id&moveFileID=#moveID#&moveFileTo=#moveTo#",
      'fi_max_file_size' => ConfigHelper::get('fi_file_size'),
    ));

    return array('content' => $content,
                 'content_left' => $contentLeft,
                 'content_top' => $contentTop,
                 'content_contenttype' => 'ContentItemFiles',
    );
  }
}
