<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2016-03-18 09:23:59 +0100 (Fr, 18 Mrz 2016) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2010 Q2E GmbH
 */
class ContentItemLogin extends ContentItem
{
  protected $_configPrefix = 'login';
  protected $_contentPrefix = 'login';
  protected $_columnPrefix = 'L';
  protected $_contentElements = array(
    'Title' => 9,
    'Text' => 9,
    'Image' => 9,
  );
  protected $_contentImageTitles = false;
  protected $_templateSuffix = 'Login';

  public function get_content($params = array())
  {
    return parent::get_content(array_merge($params, array(
      'settings' => array('no_preview' => true),
    )));
  }

  /**
   * Preview content (not available)
   */
  public function preview() {}

  /**
   * Return content of all ContentItems
   */
  public function return_class_content()
  {
    $class_content = array();
    $sql = " SELECT FK_CTID ,CIID, CIIdentifier, CTitle, "
         . "        LTitle1, LTitle2, LTitle3, LTitle4, LTitle5, LTitle6, LTitle7, LTitle8, LTitle9,"
         . "        LText1, LText2, LText3, LText4, LText5, LText6, LText7, LText8, LText9, "
         . "        LImage1, LImage2, LImage3, LImage4, LImage5, LImage6, LImage7, LImage8, LImage9 "
         . " FROM {$this->table_prefix}contentitem_login login "
         . " LEFT JOIN {$this->table_prefix}contentitem ci "
         . " ON ci.CIID = login.FK_CIID "
         . " ORDER BY login.FK_CIID ASC";
    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result)){
      $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
      $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
      $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
      $class_content[$row["CIID"]]["c_title1"] = $row["LTitle1"];
      $class_content[$row["CIID"]]["c_title2"] = $row["LTitle2"];
      $class_content[$row["CIID"]]["c_title3"] = $row["LTitle3"];
      $class_content[$row["CIID"]]["c_title4"] = $row["LTitle4"];
      $class_content[$row["CIID"]]["c_title5"] = $row["LTitle5"];
      $class_content[$row["CIID"]]["c_title6"] = $row["LTitle6"];
      $class_content[$row["CIID"]]["c_title7"] = $row["LTitle7"];
      $class_content[$row["CIID"]]["c_title8"] = $row["LTitle8"];
      $class_content[$row["CIID"]]["c_title9"] = $row["LTitle9"];
      $class_content[$row["CIID"]]["c_text1"] = $row["LText1"];
      $class_content[$row["CIID"]]["c_text2"] = $row["LText2"];
      $class_content[$row["CIID"]]["c_text3"] = $row["LText3"];
      $class_content[$row["CIID"]]["c_text4"] = $row["LText4"];
      $class_content[$row["CIID"]]["c_text5"] = $row["LText5"];
      $class_content[$row["CIID"]]["c_text6"] = $row["LText6"];
      $class_content[$row["CIID"]]["c_text7"] = $row["LText7"];
      $class_content[$row["CIID"]]["c_text8"] = $row["LText8"];
      $class_content[$row["CIID"]]["c_text9"] = $row["LText9"];
      $class_content[$row["CIID"]]["c_image_title1"] = "";
      $class_content[$row["CIID"]]["c_image_title2"] = "";
      $class_content[$row["CIID"]]["c_image_title3"] = "";
      $class_content[$row["CIID"]]["c_image_title4"] = "";
      $class_content[$row["CIID"]]["c_image_title5"] = "";
      $class_content[$row["CIID"]]["c_image_title6"] = "";
      $class_content[$row["CIID"]]["c_image_title7"] = "";
      $class_content[$row["CIID"]]["c_image_title8"] = "";
      $class_content[$row["CIID"]]["c_image_title9"] = "";
      $class_content[$row["CIID"]]["c_sub"] = array();
    }
    $this->db->free_result($result);

    return $class_content;
  }
}
