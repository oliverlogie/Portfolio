<?php

  /**
   * Content Class
   *
   * $LastChangedDate: 2016-03-18 09:23:59 +0100 (Fr, 18 Mrz 2016) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */
  class ContentItemSC extends ContentItem
  {
    protected $_configPrefix = 'sc';
    protected $_contentPrefix = 'sc';
    protected $_columnPrefix = 'S';
    protected $_contentElements = array(
      'Title' => 3,
      'Text' => 3,
      'Image' => 3,
    );
    protected $_contentImageTitles = false;
    protected $_templateSuffix = 'SC';


    public function get_content($params = array())
    {
      return parent::get_content(array_merge($params, array(
        'settings' => array('no_preview' => true),
      )));
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Preview Content                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function preview() {}

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Return Content of all ContentItems                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function return_class_content()
    {
      $class_content = array();
      $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,STitle1,STitle2,STitle3,SText1,SText2,SText3 FROM ".$this->table_prefix."contentitem_sc cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
      while ($row = $this->db->fetch_row($result)){
        $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
        $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
        $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
        $class_content[$row["CIID"]]["c_title1"] = $row["STitle1"];
        $class_content[$row["CIID"]]["c_title2"] = $row["STitle2"];
        $class_content[$row["CIID"]]["c_title3"] = $row["STitle3"];
        $class_content[$row["CIID"]]["c_text1"] = $row["SText1"];
        $class_content[$row["CIID"]]["c_text2"] = $row["SText2"];
        $class_content[$row["CIID"]]["c_text3"] = $row["SText3"];
        $class_content[$row["CIID"]]["c_image_title1"] = "";
        $class_content[$row["CIID"]]["c_image_title2"] = "";
        $class_content[$row["CIID"]]["c_image_title3"] = "";
        $class_content[$row["CIID"]]["c_sub"] = array();
      }
      $this->db->free_result($result);

      return $class_content;
    }
  }

