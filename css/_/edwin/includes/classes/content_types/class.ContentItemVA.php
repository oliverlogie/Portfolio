<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2012-03-13 09:20:59 +0100 (Di, 13 Mrz 2012) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2010 Q2E GmbH
 */

class ContentItemVA extends ContentItemLogical
{
  protected $_configPrefix = 'va';
  protected $_contentPrefix = 'va';
  protected $_columnPrefix = 'V';
  protected $_contentElements = array(
    'Title' => 1,
    'Text'  => 1,
    'Image' => 1,
  );
  protected $_templateSuffix = 'VA';

  public function return_class_content()
  {
    $class_content = array();
    $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,VTitle,VText FROM ".$this->table_prefix."contentitem_va cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
    while ($row = $this->db->fetch_row($result)){
      if ($row["CIID"]){
        $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
        $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
        $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
        $class_content[$row["CIID"]]["c_title1"] = $row["VTitle"];
        $class_content[$row["CIID"]]["c_title2"] = "";
        $class_content[$row["CIID"]]["c_title3"] = "";
        $class_content[$row["CIID"]]["c_text1"] = $row["VText"];
        $class_content[$row["CIID"]]["c_text2"] = "";
        $class_content[$row["CIID"]]["c_text3"] = "";
        $class_content[$row["CIID"]]["c_image_title1"] = "";
        $class_content[$row["CIID"]]["c_image_title2"] = "";
        $class_content[$row["CIID"]]["c_image_title3"] = "";
        $class_content[$row["CIID"]]["c_sub"] = array();
      }
    }
    $this->db->free_result($result);

    return $class_content;
  }
}
