<?php

  /**
   * Content Class
   *
   * $LastChangedDate: 2012-03-13 09:20:59 +0100 (Di, 13 Mrz 2012) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */

  class ContentItemIB extends ContentItemLogical
  {
    protected $_configPrefix = 'ib';
    protected $_contentPrefix = 'ib';
    protected $_columnPrefix = 'I';
    protected $_contentElements = array(
      'Title' => 1,
      'Text' => 1,
    );
    protected $_templateSuffix = 'IB';

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Return Content of all ContentItems                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function return_class_content()
    {
      $class_content = array();
      $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,ITitle,IText FROM ".$this->table_prefix."contentitem_ib cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
      while ($row = $this->db->fetch_row($result)){
        if ($row["CIID"]){
          $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
          $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
          $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
          $class_content[$row["CIID"]]["c_title1"] = $row["ITitle"];
          $class_content[$row["CIID"]]["c_title2"] = "";
          $class_content[$row["CIID"]]["c_title3"] = "";
          $class_content[$row["CIID"]]["c_text1"] = $row["IText"];
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

