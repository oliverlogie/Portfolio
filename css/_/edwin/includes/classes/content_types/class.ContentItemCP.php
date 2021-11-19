<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2016-03-18 09:23:59 +0100 (Fr, 18 Mrz 2016) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */
class ContentItemCP extends ContentItem
{
  protected $_configPrefix = 'cp';
  protected $_contentPrefix = 'cp';
  protected $_columnPrefix = 'CP';
  protected $_contentElements = array(
    'Title' => 3,
    'Text' => 3,
    'Image' => 3,
  );
  protected $_contentImageTitles = false;
  protected $_templateSuffix = 'CP';

  public function get_content($params = array())
  {
    return parent::get_content(array_merge($params, array(
      'settings' => array('no_preview' => true)
    )));
  }

  /**
   * Return content of all ContentItems
   */
  public function return_class_content()
  {
    $class_content = array();
    $sql = " SELECT FK_CTID, CIID, CIIdentifier, CTitle, "
         . "        CPTitle1, CPTitle2, CPTitle3, CPText1, CPText2, CPText3 "
         . " FROM {$this->table_prefix}contentitem_cp cic "
         . " LEFT JOIN {$this->table_prefix}contentitem ci "
         . "        ON ci.CIID = cic.FK_CIID "
         . " ORDER BY cic.FK_CIID ASC ";
    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result))
    {
      $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
      $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
      $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
      $class_content[$row["CIID"]]["c_title1"] = $row["CPTitle1"];
      $class_content[$row["CIID"]]["c_title2"] = $row["CPTitle2"];
      $class_content[$row["CIID"]]["c_title3"] = $row["CPTitle3"];
      $class_content[$row["CIID"]]["c_text1"] = $row["CPText1"];
      $class_content[$row["CIID"]]["c_text2"] = $row["CPText2"];
      $class_content[$row["CIID"]]["c_text3"] = $row["CPText3"];
      $class_content[$row["CIID"]]["c_image_title1"] = "";
      $class_content[$row["CIID"]]["c_image_title2"] = "";
      $class_content[$row["CIID"]]["c_image_title3"] = "";
      $class_content[$row["CIID"]]["c_sub"] = array();
    }
    $this->db->free_result($result);

    return $class_content;
  }
}