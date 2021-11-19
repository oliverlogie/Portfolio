<?php

  /**
   * Content Class
   *
   * $LastChangedDate: 2017-10-12 14:15:08 +0200 (Do, 12 Okt 2017) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Jungwirth
   * @copyright (c) 2012 Q2E GmbH
   */

  class ContentItemSD extends ContentItem
  {
    protected $_configPrefix = 'sd';
    protected $_contentPrefix = 'sd';
    protected $_columnPrefix = 'SD';
    protected $_contentElements = array(
      'Title' => 3,
      'Text' => 3,
      'Image' => 3,
    );
    protected $_templateSuffix = 'SD';

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Preview Content                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function preview(){

      $post = new Input(Input::SOURCE_POST);

      $pfx = $this->_contentPrefix;
      $image_titles = $post->readImageTitles($pfx . '_image_title');
      $image_titles = $this->explode_content_image_titles('c_' . $pfx, $image_titles);

      $images = $this->_createPreviewImages(array(
        $this->_columnPrefix . 'Image1' => $pfx . '_image1',
        $this->_columnPrefix . 'Image2' => $pfx . '_image2',
        $this->_columnPrefix . 'Image3' => $pfx . '_image3',
      ));
      $image_src1 = $images[$pfx . '_image1'];
      $image_src2 = $images[$pfx . '_image2'];
      $image_src3 = $images[$pfx . '_image3'];
      $image_src_large1 = $this->_hasLargeImage($image_src1);
      $image_src_large2 = $this->_hasLargeImage($image_src2);
      $image_src_large3 = $this->_hasLargeImage($image_src3);

      $tplName = $this->_getStandardTemplateName();
      $this->tpl->set_tpl_dir("../templates");
      $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
      $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
        'm_metainfo_part' => $this->_getMetainfoPart($pfx)
      ));
      $this->tpl->parse_if($tplName, 'zoom1', $image_src_large1, array(
        'c_' . $pfx . '_zoom1_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'zoom2', $image_src_large2, array(
        'c_' . $pfx . '_zoom2_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'zoom3', $image_src_large3, array(
        'c_' . $pfx . '_zoom3_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'image1', $image_src1, array( 'c_' . $pfx . '_image_src1' => $image_src1 ));
      $this->tpl->parse_if($tplName, 'image2', $image_src2, array( 'c_' . $pfx . '_image_src2' => $image_src2 ));
      $this->tpl->parse_if($tplName, 'image3', $image_src3, array( 'c_' . $pfx . '_image_src3' => $image_src3 ));
      $this->tpl->parse_loop($tplName, array(), 'sidebox_items');
      $this->tpl->parse_vars($tplName, array_merge( $image_titles, array (
        'c_' . $pfx . '_title1' => parseOutput($post->readString($pfx . '_title1', Input::FILTER_CONTENT_TITLE),2),
        'c_' . $pfx . '_title2' => parseOutput($post->readString($pfx . '_title2', Input::FILTER_CONTENT_TITLE),2),
        'c_' . $pfx . '_title3' => parseOutput($post->readString($pfx . '_title3', Input::FILTER_CONTENT_TITLE),2),
        'c_' . $pfx . '_text1' => parseOutput($post->readString($pfx . '_text1', Input::FILTER_CONTENT_TEXT), 1),
        'c_' . $pfx . '_text2' => parseOutput($post->readString($pfx . '_text2', Input::FILTER_CONTENT_TEXT), 1),
        'c_' . $pfx . '_text3' => parseOutput($post->readString($pfx . '_text3', Input::FILTER_CONTENT_TEXT), 1),
        'c_' . $pfx . '_image_src1' => $image_src1,
        'c_' . $pfx . '_image_src2' => $image_src2,
        'c_' . $pfx . '_image_src3' => $image_src3,
        'c_surl' => "../",
        'm_print_part' => $this->get_print_part(),
      )));
      $content = $this->tpl->parsereturn($tplName, $this->_getFrontentLang());
      $this->tpl->set_tpl_dir("./templates");
      return $content;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Return Content of all ContentItems                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function return_class_content()
    {
      $pfx = $this->_contentPrefix;
      $cPfx = $this->_columnPrefix;
      $sql = " SELECT FK_CTID, CIID, CIIdentifier, CTitle, "
           . "        {$cPfx}Title1, {$cPfx}Title2, {$cPfx}Title3, "
           . "        {$cPfx}Text1, {$cPfx}Text2, {$cPfx}Text3, "
           . "        {$cPfx}ImageTitles "
           . " FROM {$this->table_prefix}contentitem_{$pfx} ci{$pfx} "
           . " LEFT JOIN {$this->table_prefix}contentitem ci "
           . "   ON ci{$pfx}.FK_CIID = ci.CIID "
           . " ORDER BY ci{$pfx}.FK_CIID ASC ";

      $class_content = array();
      $result = $this->db->query($sql);
      while ($row = $this->db->fetch_row($result)){
        $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
        $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
        $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
        $class_content[$row["CIID"]]["c_title1"] = $row[$cPfx . "Title1"];
        $class_content[$row["CIID"]]["c_title2"] = $row[$cPfx . "Title2"];
        $class_content[$row["CIID"]]["c_title3"] = $row[$cPfx . "Title3"];
        $class_content[$row["CIID"]]["c_text1"] = $row[$cPfx . "Text1"];
        $class_content[$row["CIID"]]["c_text2"] = $row[$cPfx . "Text2"];
        $class_content[$row["CIID"]]["c_text3"] = $row[$cPfx . "Text3"];
        $image_titles = $this->explode_content_image_titles($pfx, $row[$cPfx . "ImageTitles"]);
        $class_content[$row["CIID"]]["c_image_title1"] = $image_titles[$pfx . "_image1_title"];
        $class_content[$row["CIID"]]["c_image_title2"] = $image_titles[$pfx . "_image2_title"];
        $class_content[$row["CIID"]]["c_image_title3"] = $image_titles[$pfx . "_image3_title"];
        $class_content[$row["CIID"]]["c_sub"] = array();
      }
      $this->db->free_result($result);

      return $class_content;
    }
  }

