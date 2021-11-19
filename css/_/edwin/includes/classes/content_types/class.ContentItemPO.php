<?php

  /**
   * Content Class
   *
   * $LastChangedDate: 2017-10-12 14:15:08 +0200 (Do, 12 Okt 2017) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */
  class ContentItemPO extends ContentItem
  {
    protected $_configPrefix = 'po';
    protected $_contentPrefix = 'po';
    protected $_columnPrefix = 'P';
    protected $_contentElements = array(
      'Title' => 1,
      'Text' => 3,
      'Image' => 3,
    );
    protected $_templateSuffix = 'PO';

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Edit Content                                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function edit_content()
    {
      // Handle default content elements.
      parent::edit_content();

      $post = new Input(Input::SOURCE_POST);

      $price = $post->readFloat('po_price');
      $number = $post->readString('po_number', Input::FILTER_PLAIN);

      // Update the database.
      $sql = "UPDATE {$this->table_prefix}contentitem_po "
           . "SET PPrice = $price, "
           . "    PNumber = '{$this->db->escape($number)}' "
           . "WHERE FK_CIID = $this->page_id ";
      $result = $this->db->query($sql);
    }

    public function get_content($params = array())
    {
      global $_LANG, $_LANG2;

      $currency = ConfigHelper::get('site_currencies', '', $this->site_id);
      $row = $this->_getData();

      $po_price = $row["PPrice"];
      $po_number = $row["PNumber"];

      $this->tpl->load_tpl('content_site_po', $this->_getTemplatePath());
      $this->tpl->parse_vars('content_site_po', array (
        'po_price' => parseOutput($po_price,99),
        'po_number' => $po_number,
        'po_currency' => $currency,
        'po_price_label' => $_LANG["po_price_label"],
        'po_number_label' => $_LANG["po_number_label"],
      ));

      return parent::get_content(array_merge($params, array(
        'row'      => $row,
        'settings' => array( 'tpl' => 'content_site_po' ),
      )));
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Preview Content                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function preview(){
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);
      $currency = ConfigHelper::get('site_currencies', '', $this->site_id);

      $po_image_titles = $post->readImageTitles('po_image_title');
      $po_image_titles = $this->explode_content_image_titles("c_po",$po_image_titles);

      $po_images = $this->_createPreviewImages(array(
        'PImage1' => 'po_image1',
        'PImage2' => 'po_image2',
        'PImage3' => 'po_image3',
      ));
      $po_image_src1 = $po_images['po_image1'];
      $po_image_src2 = $po_images['po_image2'];
      $po_image_src3 = $po_images['po_image3'];
      $po_image1_large = $this->_hasLargeImage($po_image_src1);
      $po_image2_large = $this->_hasLargeImage($po_image_src2);
      $po_image3_large = $this->_hasLargeImage($po_image_src3);

      $this->tpl->set_tpl_dir("../templates");
      $this->tpl->load_tpl('content_site_po', $this->_getTemplatePath());
      $this->tpl->parse_if('content_site_po', 'inside_archive', $this->_isInsideArchive(), array(
        'm_metainfo_part' => $this->_getMetainfoPart('po')
      ));
      $this->tpl->parse_if('content_site_po', 'zoom1', $po_image1_large, array(
        'c_po_zoom1_link' => '#',
      ));
      $this->tpl->parse_if('content_site_po', 'zoom2', $po_image2_large, array(
        'c_po_zoom2_link' => '#',
      ));
      $this->tpl->parse_if('content_site_po', 'zoom3', $po_image3_large, array(
        'c_po_zoom3_link' => '#',
      ));
      $this->tpl->parse_if('content_site_po', 'image1', $po_image_src1, array( 'c_po_image_src1' => $po_image_src1 ));
      $this->tpl->parse_if('content_site_po', 'image2', $po_image_src2, array( 'c_po_image_src2' => $po_image_src2 ));
      $this->tpl->parse_if('content_site_po', 'image3', $po_image_src3, array( 'c_po_image_src3' => $po_image_src3 ));
      $this->tpl->parse_vars('content_site_po', array_merge( $po_image_titles, array (
        'c_po_title' => parseOutput($post->readString('po_title', Input::FILTER_CONTENT_TITLE),2),
        'c_po_text1' => parseOutput($post->readString('po_text1', Input::FILTER_CONTENT_TEXT), 1),
        'c_po_text2' => parseOutput($post->readString('po_text2', Input::FILTER_CONTENT_TEXT), 1),
        'c_po_text3' => parseOutput($post->readString('po_text3', Input::FILTER_CONTENT_TEXT), 1),
        'c_po_image_src1' => $po_image_src1,
        'c_po_image_src2' => $po_image_src2,
        'c_po_image_src3' => $po_image_src3,
        'c_po_price' => parseOutput($post->readString('po_price', Input::FILTER_CONTENT_TITLE),99),
        'c_po_number' => parseOutput($post->readString('po_number', Input::FILTER_CONTENT_TITLE),2),
        'c_po_price_label' => $_LANG["c_po_price_label"],
        'c_po_number_label' => $_LANG["c_po_number_label"],
        'c_po_currency' => $currency,
        'c_po_action' => "",
        'c_po_hidden_fields' => "",
        'c_po_addproduct_label' => $_LANG["c_po_addproduct_label"],
        'c_po_addproduct_button_label' => $_LANG["c_po_addproduct_button_label"],
        'c_surl' => "../",
        'm_print_part' => $this->get_print_part(),
      )));
      $po_content = $this->tpl->parsereturn('content_site_po', $this->_getFrontentLang());
      $this->tpl->set_tpl_dir("./templates");
      return $po_content;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Return Content of all ContentItems                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function return_class_content()
    {
      $class_content = array();
      $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,PTitle,PText1,PText2,PText3,PPrice,PNumber,PImageTitles FROM ".$this->table_prefix."contentitem_po cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
      while ($row = $this->db->fetch_row($result)){
        $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
        $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
        $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
        $class_content[$row["CIID"]]["c_title1"] = $row["PTitle"];
        $class_content[$row["CIID"]]["c_title2"] = "";
        $class_content[$row["CIID"]]["c_title3"] = "";
        $class_content[$row["CIID"]]["c_text1"] = $row["PText1"];
        $class_content[$row["CIID"]]["c_text2"] = $row["PText2"];
        $class_content[$row["CIID"]]["c_text3"] = $row["PText3"];
        $po_image_titles = $this->explode_content_image_titles("po",$row["PImageTitles"]);
        $class_content[$row["CIID"]]["c_image_title1"] = $po_image_titles["po_image1_title"];
        $class_content[$row["CIID"]]["c_image_title2"] = $po_image_titles["po_image2_title"];
        $class_content[$row["CIID"]]["c_image_title3"] = $po_image_titles["po_image3_title"];
        $class_content[$row["CIID"]]["c_sub"] = array();
        $class_content[$row["CIID"]]["c_sub"][0]["po_price"] = $row["PPrice"];
        $class_content[$row["CIID"]]["c_sub"][0]["po_number"] = $row["PNumber"];
      }
      $this->db->free_result($result);

      return $class_content;
    }

    protected function _getData()
    {
      // Create database entries.
      $this->_checkDataBase();

      foreach ($this->_contentElements as $type => $count) {
        for ($i = 1; $i <= $count; $i++) {
          $sqlArgs[] = $this->_getContentElementColumnName($type, $count, $i);
        }
      }

      $sql = ' SELECT ' . implode(', ', $this->_dataFields) . ', '
           . '        PPrice, PNumber '
           .( $sqlArgs ? ', '.implode(',', $sqlArgs) : '' )
           .( $this->_contentImageTitles ? ', '.$this->_columnPrefix.'ImageTitles' : '' )
           . " FROM {$this->table_prefix}contentitem ci "
           . " JOIN {$this->table_prefix}contentitem_{$this->_contentPrefix} ci_sub "
           . '      ON CIID = ci_sub.FK_CIID '
           . " WHERE CIID = $this->page_id ";
      return $this->db->GetRow($sql);
    }
  }

