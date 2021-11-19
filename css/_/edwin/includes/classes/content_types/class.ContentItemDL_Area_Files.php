<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2017-10-16 07:32:10 +0200 (Mo, 16 Okt 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Stefan Podskubka
 * @copyright (c) 2009 Q2E GmbH
 */
class ContentItemDL_Area_Files extends ContentItem
{
  protected $_configPrefix = 'dl'; // "_area_file" is added in $this->__construct()
  protected $_contentPrefix = 'dl_area_file';
  protected $_columnPrefix = 'DF';
  protected $_templateSuffix = 'DL'; // "_Area_File" is added in $this->__construct()

  /**
   * The area id.
   *
   * @var integer
   */
  private $_areaID;
  /**
   * The area position (starting with 1).
   *
   * @var integer
   */
  private $_areaPosition;

  /**
   * The parent contentitem ( ContentItemDL )
   *
   * @var ContentItemDL
   */
  private $_parent = null;

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

    $editId = ($editId) ? 'AND DFID != '.$editId : '';
    $sql = 'SELECT DFID '
         . "FROM {$this->table_prefix}contentitem_dl_area_file "
         . "WHERE FK_CFID  = {$fileId} "
         . "  AND FK_DAID  = {$this->_areaID} "
         . " $editId ";

    if ($this->db->GetOne($sql)) {
      $this->setMessage(Message::createFailure($_LANG['dl_message_multiple_link_failure']));
      return true;
    }

    return false;
  }

  /**
   * Converts a download file to a central file
   */
  private function _convertFile()
  {
    global $_LANG;

    $request = new Input(Input::SOURCE_REQUEST);

    if (!$request->exists('area') || $request->readInt('area') != $this->_areaID) {
      return;
    }
    if (!$request->exists('convertFileID')) {
      return;
    }

    $ID = $request->readInt('convertFileID');

    if ($this->_convertContentItemDLAreaFile2Central($ID)) {
      $this->setMessage(Message::createSuccess($_LANG['dl_message_area_file_convert_success']));
    } else {
      //TODO print error message
    }
  }

  /**
   * Creates a file if the POST parameter process_dl_area_file_create is set.
   */
  private function _createFile()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!isset($_POST['area']) || (int)$_POST['area'] != $this->_areaID) {
      return;
    }
    if (!isset($_POST['process_dl_area_file_create'])) {
      return;
    }

    $daid = (int)key($_POST['process_dl_area_file_create']);

    $title = '';
    if (isset($_POST["dl_area{$daid}_file_title"])) {
      $title = $post->readString("dl_area{$daid}_file_title", Input::FILTER_RIGHT_TITLE);
    }

    // by default we expect an upload (if the ModuleMediaManagement is disabled)
    $kind = 'newupload';
    if (isset($_POST["dl_area{$daid}_file_kind"])) {
      $kind = $post->readString("dl_area{$daid}_file_kind", Input::FILTER_PLAIN);
    }
    $created = false;
    switch ($kind) {
      case 'newupload':
        $created = $this->_createFileNewUpload($daid, $title);
        break;
      case 'centralfile':
        $created = $this->_createFileCentralFile($daid, $title);
        break;
      default:
        // an invalid kind should be handled like no kind, but additionally
        // an error should be triggered
        trigger_error("Unknown file kind '$kind'.", E_USER_WARNING);
      case '':
        $this->setMessage(Message::createFailure($_LANG['dl_message_area_file_insufficient_input']));
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
   * @param int $daid
   *        The ID of the area in which the file is created.
   * @param string $title
   *        The file title that was entered by the user (read by the caller).
   * @return bool
   *        true, if the file was created, otherwise false.
   *        This is used to tell the caller if spidering is necessary.
   */
  private function _createFileNewUpload($daid, $title)
  {
    global $_LANG;

    if (!$title) {
      $this->setMessage(Message::createFailure($_LANG['dl_message_area_file_insufficient_input']));
      return false;
    }

    $fileArray = $_FILES["dl_area{$daid}_file_upload"];
    $upload = $this->_storeFile($fileArray,
                                $this->_parent->getConfig('file_size'),
                                $this->_parent->getConfig('file_types'));

    if (!$upload) {
      $this->setMessage(Message::createFailure($_LANG['dl_message_area_file_insufficient_input']));
      return;
    }

    $sql = 'SELECT COUNT(DFID) + 1 '
         . "FROM {$this->table_prefix}contentitem_dl_area_file "
         . "WHERE FK_DAID = $daid";
    $position = $this->db->GetOne($sql);
    $now = date('Y-m-d H:i:s');
    $fileSize = (int)$fileArray['size'];
    $sql = "INSERT INTO {$this->table_prefix}contentitem_dl_area_file "
         . '(DFTitle, DFPosition, DFCreated, DFSize, DFFile, FK_DAID) '
         . "VALUES('{$this->db->escape($title)}', $position, '$now', $fileSize,'$upload', $daid) ";
    $result = $this->db->query($sql);

    if ($this->getConfig('insert_download_at_first_position')) {
      $id = $this->db->insert_id();
      $this->_moveToFirstPosition($id);
    }

    $this->setMessage(Message::createSuccess($_LANG['dl_message_area_file_create_success']));

    unset($_POST["dl_area{$daid}_file_title"]);
    unset($_POST["dl_area{$daid}_file_kind"]);
    unset($_POST["dl_area{$daid}_file_file_upload"]);
    unset($_POST["dl_area{$daid}_file_file"]);
    unset($_POST["dl_area{$daid}_file_file_type"]);
    unset($_POST["dl_area{$daid}_file_file_id"]);

    return true;
  }

  /**
   * Creates a file from a specified central file.
   *
   * @param int $daid
   *        The ID of the area in which the file is created.
   * @param string &$title
   *        The file title that was entered by the user (read by the caller).
   *        This is passed as a reference here, because if it is empty the
   *        caller needs to know the title of the central file for spidering.
   * @return bool
   *        true, if the file was created, otherwise false.
   *        This is used to tell the caller if spidering is necessary.
   */
  private function _createFileCentralFile($daid, &$title)
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    $file = '';
    $fileType = '';
    $fileID = 0;
    if (isset($_POST["dl_area{$daid}_file_file"])) {
      $file = trim($post->readString("dl_area{$daid}_file_file", Input::FILTER_PLAIN));
    }
    if (isset($_POST["dl_area{$daid}_file_file_type"])) {
      $fileType = $post->readString("dl_area{$daid}_file_file_type", Input::FILTER_PLAIN);
    }
    if (isset($_POST["dl_area{$daid}_file_file_id"])) {
      $fileID = (int)$_POST["dl_area{$daid}_file_file_id"];
    }

    // Workaround for jQuery AutoComplete (for description see ContentItemCB_Boxes::updateBox())
    if (!$file) {
      $fileType = '';
      $fileID = 0;
    }

    if (!$fileType || !$fileID || $fileType != 'centralfile') {
      $this->setMessage(Message::createFailure($_LANG['dl_message_area_file_insufficient_input']));
      return false;
    }

    // A central download must not be linked more than once
    if ($this->_checkMultipleLinks($fileID))
      return false;

    $sql = 'SELECT COUNT(DFID) + 1 '
         . "FROM {$this->table_prefix}contentitem_dl_area_file "
         . "WHERE FK_DAID = $daid";
    $position = $this->db->GetOne($sql);
    $now = date('Y-m-d H:i:s');
    $sql = "INSERT INTO {$this->table_prefix}contentitem_dl_area_file "
         . '(DFTitle, DFPosition, DFCreated, FK_CFID, FK_DAID) '
         . "VALUES('{$this->db->escape($title)}', $position, '$now', $fileID, $daid) ";
    $result = $this->db->query($sql);

    if ($this->getConfig('insert_download_at_first_position')) {
      $id = $this->db->insert_id();
      $this->_moveToFirstPosition($id);
    }

    // if the file has no custom title we pass back the title of the
    // central file for spidering
    if (!$title) {
      $sql = 'SELECT CFTitle '
           . "FROM {$this->table_prefix}centralfile "
           . "WHERE CFID = $fileID ";
      $title = $this->db->GetOne($sql);
    }

    $this->setMessage(Message::createSuccess($_LANG['dl_message_area_file_create_success']));

    unset($_POST["dl_area{$daid}_file_title"]);
    unset($_POST["dl_area{$daid}_file_kind"]);
    unset($_POST["dl_area{$daid}_file_file_upload"]);
    unset($_POST["dl_area{$daid}_file_file"]);
    unset($_POST["dl_area{$daid}_file_file_type"]);
    unset($_POST["dl_area{$daid}_file_file_id"]);

    return true;
  }

  /**
   * Updates a file if the POST parameter process_dl_area_file_edit is set.
   */
  private function _updateFile()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);

    if (!isset($_POST['area']) || (int)$_POST['area'] != $this->_areaID) {
      return;
    }
    if (!isset($_POST['process_dl_area_file_edit'])) {
      return;
    }

    $ID = $post->readKey('process_dl_area_file_edit');

    // read the old title for de-spidering after the update
    $sql = 'SELECT DFTitle, CFTitle '
         . "FROM {$this->table_prefix}contentitem_dl_area_file "
         . "LEFT JOIN {$this->table_prefix}centralfile ON FK_CFID = CFID "
         . "WHERE DFID = $ID ";
    $row = $this->db->GetRow($sql);
    $oldTitle = coalesce($row['DFTitle'], $row['CFTitle']);

    $title = $post->readString("dl_area_file{$ID}_title", Input::FILTER_RIGHT_TITLE);

    // by default we expect an upload (if the ModuleMediaManagement is disabled)
    $kind = $post->readString("dl_area_file{$ID}_kind", Input::FILTER_PLAIN, 'newupload');
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
        $this->setMessage(Message::createFailure($_LANG['dl_message_area_file_insufficient_input']));
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
      $this->setMessage(Message::createFailure($_LANG['dl_message_area_file_insufficient_input']));
      $_GET['editFileID'] = $ID;
      return false;
    }

    $sql = "UPDATE {$this->table_prefix}contentitem_dl_area_file "
         . "SET DFTitle = '{$this->db->escape($title)}' "
         . "WHERE DFID = $ID ";
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['dl_message_area_file_update_success']));

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
      $this->setMessage(Message::createFailure($_LANG['dl_message_area_file_insufficient_input']));
      $_GET['editFileID'] = $ID;
      return false;
    }

    $sql = 'SELECT DFFile '
         . "FROM {$this->table_prefix}contentitem_dl_area_file "
         . "WHERE DFID = $ID ";
    $existingUpload = $this->db->GetOne($sql);

    $allowedFilesize = $this->_parent->getConfig('file_size');
    $allowedFiletypes = $this->_parent->getConfig('file_types');
    $fileArray = $_FILES["dl_area_file{$ID}_upload"];
    $newUpload = $this->_storeFile($fileArray, $allowedFilesize,
                                   $allowedFiletypes, $existingUpload);

    if (!$newUpload) {
      $this->setMessage(Message::createFailure($_LANG['dl_message_area_file_insufficient_input']));
      $_GET['editFileID'] = $ID;
      return false;
    }

    $now = date('Y-m-d H:i:s');
    $fileSize = (int)$fileArray['size'];
    $sql = "UPDATE {$this->table_prefix}contentitem_dl_area_file "
         . "SET DFTitle = '{$this->db->escape($title)}', "
         . "    DFModified = '$now', "
         . "    DFFile = '$newUpload', "
         . "    DFSize = $fileSize, "
         . '    FK_CFID = NULL '
         . "WHERE DFID = $ID ";
    $result = $this->db->query($sql);

    if ($existingUpload && $existingUpload != $newUpload) {
      unlinkIfExists("../$existingUpload");
    }

    $this->setMessage(Message::createSuccess($_LANG['dl_message_area_file_update_success']));

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

    $sql = 'SELECT DFFile '
         . "FROM {$this->table_prefix}contentitem_dl_area_file "
         . "WHERE DFID = $ID ";
    $existingUpload = $this->db->GetOne($sql);

    $post = new Input(Input::SOURCE_POST);

    list($file, $fileType, $fileID) = $post->readDownloadLink("dl_area_file{$ID}_file");

    if (!$fileType || !$fileID || $fileType != 'centralfile') {
      $this->setMessage(Message::createFailure($_LANG['dl_message_area_file_insufficient_input']));
      $_GET['editFileID'] = $ID;
      return false;
    }

    // A central download must not be linked more than once
    if ($this->_checkMultipleLinks($fileID, $ID))
      return false;

    $now = date('Y-m-d H:i:s');
    $sql = "UPDATE {$this->table_prefix}contentitem_dl_area_file "
         . "SET DFTitle = '{$this->db->escape($title)}', "
         . "    DFModified = NULL, "
         . '    DFFile = NULL, '
         . '    DFSize = 0, '
         . "    FK_CFID = $fileID "
         . "WHERE DFID = $ID ";
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

    $this->setMessage(Message::createSuccess($_LANG['dl_message_area_file_update_success']));

    return true;
  }

  /**
   * Moves a file if the GET parameters moveFileID and moveFileTo are set.
   */
  private function _moveFile()
  {
    global $_LANG;

    if (!isset($_GET['area']) || (int)$_GET['area'] != $this->_areaID) {
      return;
    }
    if (!isset($_GET['moveFileID'], $_GET['moveFileTo'])) {
      return;
    }

    $moveID = (int)$_GET['moveFileID'];
    $moveTo = (int)$_GET['moveFileTo'];

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_dl_area_file",
                                         'DFID', 'DFPosition',
                                         'FK_DAID', $this->_areaID);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved) {
      $this->setMessage(Message::createSuccess($_LANG['dl_message_area_file_move_success']));
    }
  }

  /**
   * Deletes a file if the GET parameter deleteFileID is set.
   */
  private function _deleteFile()
  {
    global $_LANG;

    if (!isset($_GET['area']) || (int)$_GET['area'] != $this->_areaID) {
      return;
    }
    if (!isset($_GET['deleteFileID'])) {
      return;
    }

    $id = (int)$_GET['deleteFileID'];

    $this->_deleteContentItemDLAreaFile($id, $this->_areaID, $this->page_id);

    $this->setMessage(Message::createSuccess($_LANG['dl_message_area_file_delete_success']));
  }

  protected function _processedValues()
  {
    return array( 'convertFileID',
                  'deleteFileID',
                  'moveFileID',
                  'process_dl_area_file_create',
                  'process_dl_area_file_edit',);
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Constructor                                                                           //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function __construct($tpl, $db, $table_prefix, $site_id, $page_id,
                              $user, $area_id, $area_position, $session,
                              Navigation $navigation, ContentItemDL $parent)
  {
    parent::__construct($site_id,$page_id,$tpl,$db,$table_prefix,'','',$user,$session,$navigation);
    $this->_areaID = $area_id;
    $this->_areaPosition = $area_position;
    $this->_configPrefix .= '_area_file';
    $this->_templateSuffix .= '_Area_File';
    $this->_parent = $parent;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Edit Content                                                                        //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function edit_content()
  {
    $this->_createFile();
    $this->_updateFile();
    $this->_moveFile();
    $this->_deleteFile();
    $this->_convertFile();
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                                        //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function delete_content()
  {
    // Determine title, filename and position of all files within the area.
    $sql = ' SELECT DFID, DFFile, DFTitle, CFTitle '
         . " FROM {$this->table_prefix}contentitem_dl_area "
         . " JOIN {$this->table_prefix}contentitem_dl_area_file "
         . '      ON FK_DAID = DAID '
         . " LEFT JOIN {$this->table_prefix}centralfile "
         . '      ON FK_CFID = CFID '
         . " WHERE DAID = $this->_areaID ";
    $result = $this->db->query($sql);

    // Delete all area files stored within the download area.
    while ($file = $this->db->fetch_row($result))
    {
      // delete file
      $sql = " DELETE FROM {$this->table_prefix}contentitem_dl_area_file "
           . ' WHERE DFID = ' . $file['DFID'];
      $this->db->query($sql);

      if ($file['DFFile']) {
        unlinkIfExists('../' . $file['DFFile']);
      }

      // remove the file title from the search index of the content item
      $this->_spiderDownload(coalesce($file['DFTitle'], $file['CFTitle']), $this->page_id, false);
    }

  }

  public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
  {
    $sql = " SELECT DFTitle, DFFile, DFPosition, FK_CFID, FK_DAID "
         . " FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
         . " WHERE FK_DAID = {$id} ";
    $result = $this->db->query($sql);
    if (!$result) {
      return 0;
    }
    $now = date('Y-m-d H:i:s');
    while ($row = $this->db->fetch_row($result)) {
      // Ignore decentral files.
      if ($row['DFFile']) {
        continue;
      }
      $sql = " INSERT INTO {$this->table_prefix}contentitem_{$this->_contentPrefix} "
           . " (DFTitle, DFCreated, DFPosition, FK_CFID, FK_DAID) "
           . " VALUES ('{$this->db->escape($row['DFTitle'])}', '{$now}', "
           . "         '{$row['DFPosition']}', '{$row['FK_CFID']}', {$newParentId})";
      $this->db->query($sql);
    }
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2, $_MODULES;

    $editFileID = isset($_GET['editFileID']) ? (int)$_GET['editFileID'] : 0;
    $editFileData = array();
    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_dl_area_file",
                                         'DFID', 'DFPosition',
                                         'FK_DAID', $this->_areaID);

    // Check if the user has permission to the ModuleMediaManagementNode. Users
    // with permission may convert dl downloads into central downloads.
    $mediaMgmt = $this->_user->AvailableModule('mediamanagement', $this->site_id) &&
                 $this->_user->AvailableSubmodule('mediamanagement', 'node');
    $urlPart = "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;area={$this->_areaID}";

    // read area files
    $fileItems = array();
    $sql = 'SELECT DFID, DFTitle, DFFile, DFCreated, DFModified, DFPosition, '
         . '       CFID, CFTitle, CFFile, CFCreated, CFModified, FK_SID '
         . "FROM {$this->table_prefix}contentitem_dl_area_file cidlaf "
         . "LEFT JOIN {$this->table_prefix}centralfile cf ON FK_CFID = CFID "
         . "WHERE cidlaf.FK_DAID = $this->_areaID "
         . 'ORDER BY DFPosition ASC ';
    $result = $this->db->query($sql);
    $fileCount = $this->db->num_rows($result);
    $invalidLinks = 0;
    while ($row = $this->db->fetch_row($result)) {
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['DFPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['DFPosition']);

      $class = 'normal';
      if (!$row['DFFile'] && !$row['CFID']) {
        $class = 'invalid';
        $invalidLinks++;
      }
      // the 'edit' class overrides the others as it is the most important in the UI
      if ($row['DFID'] == $editFileID) {
        $class = 'edit';
      }

      $fileTitle = coalesce($row['DFTitle'], $row['CFTitle']);
      $fileLink = '../' . coalesce($row['DFFile'], $row['CFFile']);
      // insert into file link user's current session id, we need this to
      // identify the backend user on the frontend
      $filePath = $this->_fileUrlForBackendUser(coalesce($row['DFFile'], $row['CFFile']));
      $filename = mb_substr(mb_strrchr('../' . coalesce($row['DFFile'], $row['CFFile']), "/"), 1);
      $file = $row['CFID'] ? $filename : '';
      $fileType = $row['DFFile'] ? 'dlfile' : 'centralfile';
      $fileConvert = $row['DFFile'] && $mediaMgmt ?
                     "$urlPart&amp;convertFileID={$row['DFID']}&amp;scrollToAnchor=a_area{$this->_areaPosition}_files" : '';
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
      $fileScopeLabel = sprintf($_LANG["dl_area_file_scope_{$fileScope}_label"], $linkedSiteTitle);

      $fileItems[$row['DFID']] = array(
        'dl_area_file_title' => parseOutput($fileTitle),
        'dl_area_file_file_link' => $filePath,
        'dl_area_file_filename' => parseOutput($filename),
        'dl_area_file_file' => parseOutput($file),
        'dl_area_file_file_convert' => $fileConvert,
        'dl_area_file_file_type' => $fileType,
        'dl_area_file_file_id' => $fileID,
        'dl_area_file_file_scope' => $fileScope,
        'dl_area_file_file_scope_label' => $fileScopeLabel,
        'dl_area_file_date' => date($this->_configHelper->getDateFormat($this->_user->getLanguage(), $this->getConfigPrefix()), strtotime(coalesce($row['CFModified'], $row['CFCreated'], $row['DFModified'], $row['DFCreated']))),
        'dl_area_file_id' => $row['DFID'],
        'dl_area_file_position' => $row['DFPosition'],
        'dl_area_file_size' => formatFileSize(filesize($fileLink)),
        'dl_area_file_class' => $class,
        'dl_area_file_edit_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;area={$this->_areaID}&amp;editFileID={$row['DFID']}&amp;scrollToAnchor=a_area{$this->_areaPosition}_files",
        'dl_area_file_move_up_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;area={$this->_areaID}&amp;moveFileID={$row['DFID']}&amp;moveFileTo=$moveUpPosition&amp;scrollToAnchor=a_area{$this->_areaPosition}_files",
        'dl_area_file_move_down_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;area={$this->_areaID}&amp;moveFileID={$row['DFID']}&amp;moveFileTo=$moveDownPosition&amp;scrollToAnchor=a_area{$this->_areaPosition}_files",
        'dl_area_file_delete_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;area={$this->_areaID}&amp;deleteFileID={$row['DFID']}&amp;scrollToAnchor=a_area{$this->_areaPosition}_files",
      );

      // this row has to be edited
      if ($row['DFID'] == $editFileID) {
        $post = new Input(Input::SOURCE_POST);
        $fileKind = $row['CFID'] ? 'centralfile' : 'existingupload';
        if (isset($_POST["dl_area_file{$editFileID}_kind"])) {
          $fileKind = $_POST["dl_area_file{$editFileID}_kind"];
        }
        if (isset($_POST["dl_area_file{$editFileID}_title"])) {
          $fileTitle = $post->readString("dl_area_file{$editFileID}_title", Input::FILTER_RIGHT_TITLE);
        }
        if (isset($_POST["dl_area_file{$editFileID}_file"])) {
          $file = trim($post->readString("dl_area_file{$editFileID}_file", Input::FILTER_PLAIN));
        }
        if (isset($_POST["dl_area_file{$editFileID}_file_type"])) {
          $fileType = $_POST["dl_area_file{$editFileID}_file_type"];
        }
        if (isset($_POST["dl_area_file{$editFileID}_file_id"])) {
          $fileID = (int)$_POST["dl_area_file{$editFileID}_file_id"];
        }
        $editFileData = array(
          'dl_area_file_id_edit' => $row['DFID'],
          'dl_area_file_title_edit' => parseOutput($fileTitle),
          'dl_area_file_kind_edit' => $fileKind,
          'dl_area_file_file_link_edit' => $filePath,
          'dl_area_file_filename_edit' => parseOutput($filename),
          'dl_area_file_file_edit' => parseOutput($file),
          'dl_area_file_file_type_edit' => $fileType,
          'dl_area_file_file_id_edit' => $fileID,
          'dl_area_file_size_edit' => formatFileSize(filesize($fileLink)),
        );
      }
    }
    $this->db->free_result($result);

    $maxItems = (int)$this->_parent->getConfig('number_of_files');
    $maximumReached = count($fileItems) >= $maxItems;
    $centralFilesAvailable = (in_array('mediamanagement', $_MODULES)) ? true : false;

    $this->tpl->load_tpl('content_site_dl_area_file', 'content_types/ContentItemDL_Area_File.tpl');
    // fill new entry form with previously input data (in case of an error)
    $this->tpl->parse_if('content_site_dl_area_file', 'entry_create', !$maximumReached, array(
      'dl_area_file_title' => isset($_POST["dl_area{$this->_areaID}_file_title"]) ? $_POST["dl_area{$this->_areaID}_file_title"] : '',
      'dl_area_file_file' => isset($_POST["dl_area{$this->_areaID}_file_file"]) ? $_POST["dl_area{$this->_areaID}_file_file"] : '',
      'dl_area_file_file_type' => isset($_POST["dl_area{$this->_areaID}_file_file_type"]) ? $_POST["dl_area{$this->_areaID}_file_file_type"] : '',
      'dl_area_file_file_id' => isset($_POST["dl_area{$this->_areaID}_file_file_id"]) ? $_POST["dl_area{$this->_areaID}_file_file_id"] : '',
    ));
    if (!$maximumReached) {
      $kind = '';
      if (isset($_POST["dl_area{$this->_areaID}_file_kind"])) {
        $kind = $_POST["dl_area{$this->_areaID}_file_kind"];
      }
      $this->tpl->parse_if('content_site_dl_area_file', 'entry_create_kind_newupload', $kind == 'newupload');
      $this->tpl->parse_if('content_site_dl_area_file', 'entry_create_kind_centralfile', $kind == 'centralfile');
    }
    // END new entry form
    $this->tpl->parse_if('content_site_dl_area_file', 'entries_maximum_reached', $maximumReached);
    $this->tpl->parse_if('content_site_dl_area_file', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('dl_area_file'));
    // fill edit entry form with existing data
    $this->tpl->parse_if('content_site_dl_area_file', 'entry_edit', $editFileData, $editFileData);
    if ($editFileData) {
      $this->tpl->parse_if('content_site_dl_area_file', 'entry_edit_existingupload', !$editFileData['dl_area_file_file_id_edit']);
      $this->tpl->parse_if('content_site_dl_area_file', 'entry_edit_kind_existingupload', $editFileData['dl_area_file_kind_edit'] == 'existingupload');
      $this->tpl->parse_if('content_site_dl_area_file', 'entry_edit_kind_newupload', $editFileData['dl_area_file_kind_edit'] == 'newupload');
      $this->tpl->parse_if('content_site_dl_area_file', 'entry_edit_kind_centralfile', $editFileData['dl_area_file_kind_edit'] == 'centralfile');
    }
    // END edit entry form
    $this->tpl->parse_if('content_site_dl_area_file', 'entry_create_newupload_radio_available', $centralFilesAvailable);
    $this->tpl->parse_if('content_site_dl_area_file', 'entry_create_centralfile_available', $centralFilesAvailable);
    $this->tpl->parse_if('content_site_dl_area_file', 'entry_edit_centralfile_available', $centralFilesAvailable);
    $this->tpl->parse_loop('content_site_dl_area_file', $fileItems, 'entries');

    foreach ($fileItems as $key => $val)
    {
      $this->tpl->parse_if('content_site_dl_area_file', 'dl_file_convert_' . $key, $val['dl_area_file_file_convert']);
      $this->tpl->parse_if('content_site_dl_area_file', 'dl_file_no_convert_' . $key, !$val['dl_area_file_file_convert']);
    }
    $dl_area_file_items_output = $this->tpl->parsereturn('content_site_dl_area_file', array(
      'dl_area_file_count'            => $fileCount,
      'dl_area_file_dragdrop_link_js' => "index.php?action=content&site=$this->site_id&page=$this->page_id&area=$this->_areaID&moveFileID=#moveID#&moveFileTo=#moveTo#&scrollToAnchor=a_area{$this->_areaPosition}_files",
      'dl_area_file_max_file_size'    =>  $this->_parent->getConfig('file_size'),
    ));

    return array(
      'message' => $this->_getMessage(),
      'content' => $dl_area_file_items_output,
      'count' => count($fileItems),
      'invalidLinks' => $invalidLinks,
    );
  }

  /**
   * Returns id of download area this object belongs to
   *
   * @return int
   *         The id of the download area
   */
  public function getAreaId()
  {
    return $this->_areaID;
  }

  /**
   * Moves the given download to the first position
   *
   * @param int $downloadId
   */
  private function _moveToFirstPosition($downloadId)
  {
    $positionHelper = new PositionHelper(
        $this->db,
        "{$this->table_prefix}contentitem_dl_area_file",
        'DFID',
        'DFPosition',
        'FK_DAID',
        $this->_areaID
    );

    $positionHelper->move($downloadId, 1);
  }
}

