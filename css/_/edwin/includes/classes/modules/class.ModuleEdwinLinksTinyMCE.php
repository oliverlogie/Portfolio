<?php
/**
 * EdwinLinksTinyMCE Module Class
 *
 * $LastChangedDate: 2019-05-10 11:58:32 +0200 (Fr, 10 Mai 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Stefan Podskubka
 * @copyright (c) 2009 Q2E GmbH
 */

class ModuleEdwinLinksTinyMCE extends Module
{
  /**
   * Sends data to the client when the action is "response", handles special requests for ModuleMediaManagement.
   *
   * @param string $request
   *        The content of the "request" variable inside GET or POST data.
   */
  public function sendResponse($request)
  {
    switch ($request) {
      case 'ContentItemDetails':
        $this->_sendResponseContentItemDetails();
        break;
      case 'DownloadDetails':
        $this->_sendResponseDownloadDetails();
        break;
      default:
        // Call the sendResponse() method of the parent Module class.
        parent::sendResponse($request);
        break;
    }
  }

  /**
   * Sends the details of a content item to the client (opposite of "ContentItemAutoComplete").
   */
  private function _sendResponseContentItemDetails()
  {
    global $_LANG;

    header('Content-Type: application/json');

    $get = new Input(Input::SOURCE_GET);

    $pageID = $get->readInt('pageID');

    $internalLink = $this->getInternalLinkHelper($pageID);
    $pageIdentifier = $internalLink->getIdentifier();
    $pageStatus = $internalLink->getClass();

    echo Json::Encode(array(
      'scheme'     => 'link',
      'ID'         => $pageID,
      'identifier' => $pageIdentifier,
      'status'     => $pageStatus,
    ));
  }

  /**
   * Sends the path of a download to the client (opposite of "DownloadAutoComplete").
   */
  private function _sendResponseDownloadDetails()
  {
    header('Content-Type: application/json');

    $get = new Input(Input::SOURCE_GET);

    $fileType = $get->readString('fileType');
    $fileID = $get->readInt('fileID');

    switch ($fileType) {
      case 'file':
        $sql = 'SELECT FFile '
             . "FROM {$this->table_prefix}file "
             . "WHERE FID = $fileID ";
        $filePath = $this->db->GetOne($sql);
        break;
      case 'dlfile':
        $sql = 'SELECT DFFile '
             . "FROM {$this->table_prefix}contentitem_dl_area_file "
             . "WHERE DFID = $fileID ";
        $filePath = $this->db->GetOne($sql);
        break;
      case 'centralfile':
        $sql = 'SELECT CFFile '
             . "FROM {$this->table_prefix}centralfile "
             . "WHERE CFID = $fileID ";
        $filePath = $this->db->GetOne($sql);
        break;
      default:
        $filePath = '';
        break;
    }

    if ($filePath) {
      $filePath = mb_substr(mb_strrchr($filePath, '/'), 1);
    }

    echo Json::Encode(array(
      'scheme' => 'file',
      'type'   => $fileType,
      'ID'     => $fileID,
      'name'   => $filePath,
      'status' => $filePath ? 'normal' : 'invalid',
    ));
  }
}
