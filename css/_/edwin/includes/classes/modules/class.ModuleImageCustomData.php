<?php
/**
 * ModuleImageCustomData Module Class
 *
 * $LastChangedDate: 2013-07-04 08:25:15 +0200 (Do, 04 Jul 2013) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2010 Q2E GmbH
 */

class ModuleImageCustomData extends Module
{
  /**
   * Sends data to the client when the action is "response", handles special requests for ContentItemBG
   *
   * @param string $request
   *        The content of the "request" variable inside GET or POST data.
   */
  public function sendResponse($request)
  {
    switch ($request) {
      case 'ImageCustomData':
        $this->_sendResponseImageCustomData();
        break;
      default:
        // Call the sendResponse() method of the parent Module class.
        parent::sendResponse($request);
        break;
    }
  }

  /**
   * Send the images customdata to the client (json)
   */
  private function _sendResponseImageCustomData()
  {
    header('Content-Type: application/json');

    $get = new Input(Input::SOURCE_GET);

    $imageID = $get->readInt('imageCustomDataID');

    $sql = "SELECT BIID, BITitle, BIText, BIImageTitle "
         . "FROM {$this->table_prefix}contentitem_bg_image "
         . "WHERE BIID = $imageID ";
    $row = $this->db->GetRow($sql);

    echo Json::Encode(array(
      'id'       => $imageID,
      'title'    => $row['BITitle'],
      'text'     => $row['BIText'],
      'subtitle' => $row['BIImageTitle'],
    ));
  }
}
