<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2017-08-18 11:37:43 +0200 (Fr, 18 Aug 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */

class ContentItemBI extends ContentItem
{
  protected $_configPrefix = 'bi';
  protected $_contentPrefix = 'bi';
  protected $_columnPrefix = 'B';
  protected $_contentElements = array(
    'Title' => 1,
    'Text' => 1,
    'Image' => 2,
  );
  protected $_contentImageTitles = false;
  protected $_templateSuffix = 'BI';

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Edit Content                                                                          //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function edit_content()
  {
    // Handle default content elements.
    parent::edit_content();

    $post = new Input(Input::SOURCE_POST);

    $number = $post->readInt('bi_number');

    // Update the database.
    $sql = "UPDATE {$this->table_prefix}contentitem_bi "
         . "SET BNumber = '$number' "
         . "WHERE FK_CIID = $this->page_id ";
    $this->db->query($sql);
  }

  public function get_content($params = array())
  {
    $row = $this->_getData();
    $number = $row['BNumber'] ?: '';

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_vars($tplName, array (
      'bi_number' => $number,
    ));

    return parent::get_content(array_merge($params, array(
      'row'      => $row,
      'settings' => array( 'tpl' => $tplName ),
    )));
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Preview Content                                                                       //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function preview(){

    $post = new Input(Input::SOURCE_POST);

    $bi_images = $this->_createPreviewImages(array(
      'BImage1' => 'bi_image1',
      'BImage2' => 'bi_image2',
    ));
    $bi_image_src1 = $bi_images['bi_image1'];
    $bi_image_src2 = $bi_images['bi_image2'];
    $bi_image1_large = $this->_hasLargeImage($bi_image_src1);
    $bi_image2_large = $this->_hasLargeImage($bi_image_src2);

    $this->tpl->set_tpl_dir("../templates");
    $this->tpl->load_tpl('content_site_bi', 'content_types/ContentItemBI.tpl');
    $this->tpl->parse_if('content_site_bi', 'inside_archive', $this->_isInsideArchive(), array(
      'm_metainfo_part' => $this->_getMetainfoPart('bi')
    ));
    $this->tpl->parse_if('content_site_bi', 'zoom1', $bi_image1_large, array(
      'c_bi_zoom1_link' => '#',
    ));
    $this->tpl->parse_if('content_site_bi', 'zoom2', $bi_image1_large, array(
      'c_bi_zoom2_link' => '#',
    ));
    $this->tpl->parse_vars('content_site_bi', array_merge(array (
      'c_bi_title' => parseOutput($post->readString('bi_title', Input::FILTER_CONTENT_TITLE),2),
      'c_bi_text' => parseOutput($post->readString('bi_text', Input::FILTER_CONTENT_TEXT), 1),
      'c_bi_image_src1' => $bi_image_src1,
      'c_bi_image_src2' => $bi_image_src2,
      'c_surl' => "../",
      'm_print_part' => $this->get_print_part(),
    )));
    $bi_content = $this->tpl->parsereturn('content_site_bi', $this->_getFrontentLang());
    $this->tpl->set_tpl_dir("./templates");
    return $bi_content;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Return Content of all ContentItems                                                    //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function return_class_content(){

    $class_content = array();
    $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,BTitle,BText FROM ".$this->table_prefix."contentitem_bi cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
    while ($row = $this->db->fetch_row($result)){
      $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
      $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
      $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
      $class_content[$row["CIID"]]["c_title1"] = $row["BTitle"];
      $class_content[$row["CIID"]]["c_title2"] = "";
      $class_content[$row["CIID"]]["c_title3"] = "";
      $class_content[$row["CIID"]]["c_text1"] = $row["BText"];
      $class_content[$row["CIID"]]["c_text2"] = "";
      $class_content[$row["CIID"]]["c_text3"] = "";
      $class_content[$row["CIID"]]["c_image_title1"] = "";
      $class_content[$row["CIID"]]["c_image_title2"] = "";
      $class_content[$row["CIID"]]["c_image_title3"] = "";
      $class_content[$row["CIID"]]["c_sub"] = array();
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
      . '        BNumber '
      .( $sqlArgs ? ', '.implode(',', $sqlArgs) : '' )
      .( $this->_contentImageTitles ? ', '.$this->_columnPrefix.'ImageTitles' : '' )
      . " FROM {$this->table_prefix}contentitem ci "
      . " JOIN {$this->table_prefix}contentitem_{$this->_contentPrefix} ci_sub "
      . '      ON CIID = ci_sub.FK_CIID '
      . " WHERE CIID = $this->page_id ";
    return $this->db->GetRow($sql);

  }
}

