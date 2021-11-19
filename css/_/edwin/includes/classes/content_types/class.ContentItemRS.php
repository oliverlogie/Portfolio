<?php

  /**
   * Content Class
   *
   * $LastChangedDate: 2017-10-12 14:15:08 +0200 (Do, 12 Okt 2017) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Jungwirth
   * @copyright (c) 2010 Q2E GmbH
   */
  class ContentItemRS extends ContentItem
  {
    protected $_configPrefix = 'rs';
    protected $_contentPrefix = 'rs';
    protected $_columnPrefix = 'RS';
    protected $_contentElements = array(
      'Title' => 3,
      'Text' => 3,
      'Image' => 3,
    );
    protected $_templateSuffix = 'RS';

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Preview Content                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function preview()
    {
      $post = new Input(Input::SOURCE_POST);

      $rs_image_titles = $post->readImageTitles('rs_image_title');
      $rs_image_titles = $this->explode_content_image_titles('c_rs', $rs_image_titles);

      $rs_images = $this->_createPreviewImages(array(
        'RSImage1' => 'rs_image1',
        'RSImage2' => 'rs_image2',
        'RSImage3' => 'rs_image3',
      ));
      $rs_image_src1 = $rs_images['rs_image1'];
      $rs_image_src2 = $rs_images['rs_image2'];
      $rs_image_src3 = $rs_images['rs_image3'];
      $rs_image_src_large1 = $this->_hasLargeImage($rs_image_src1);
      $rs_image_src_large2 = $this->_hasLargeImage($rs_image_src2);
      $rs_image_src_large3 = $this->_hasLargeImage($rs_image_src3);

      $tplName = $this->_getStandardTemplateName();
      $this->tpl->set_tpl_dir("../templates");
      $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
      $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
        'm_metainfo_part' => $this->_getMetainfoPart('rs')
      ));
      $this->tpl->parse_if($tplName, 'zoom1', $rs_image_src_large1, array(
        'c_rs_zoom1_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'zoom2', $rs_image_src_large2, array(
        'c_rs_zoom2_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'zoom3', $rs_image_src_large3, array(
        'c_rs_zoom3_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'image1', $rs_image_src1, array( 'c_rs_image_src1' => $rs_image_src1 ));
      $this->tpl->parse_if($tplName, 'image2', $rs_image_src2, array( 'c_rs_image_src2' => $rs_image_src2 ));
      $this->tpl->parse_if($tplName, 'image3', $rs_image_src3, array( 'c_rs_image_src3' => $rs_image_src3 ));
      $this->tpl->parse_vars($tplName, array_merge( $rs_image_titles, array (
        'c_rs_title1' => parseOutput($post->readString('rs_title1', Input::FILTER_CONTENT_TITLE),2),
        'c_rs_title2' => parseOutput($post->readString('rs_title2', Input::FILTER_CONTENT_TITLE),2),
        'c_rs_title3' => parseOutput($post->readString('rs_title3', Input::FILTER_CONTENT_TITLE),2),
        'c_rs_text1' => nl2br(parseOutput($post->readString('rs_text1', Input::FILTER_CONTENT_TEXT), 1)),
        'c_rs_text2' => nl2br(parseOutput($post->readString('rs_text2', Input::FILTER_CONTENT_TEXT), 1)),
        'c_rs_text3' => nl2br(parseOutput($post->readString('rs_text3', Input::FILTER_CONTENT_TEXT), 1)),
        'c_rs_image_src1' => $rs_image_src1,
        'c_rs_image_src2' => $rs_image_src2,
        'c_rs_image_src3' => $rs_image_src3,
        'c_rs_reseller_part' => '',
        'c_surl' => "../",
        'm_print_part' => $this->get_print_part(),
      )));
      $rs_content = $this->tpl->parsereturn($tplName, $this->_getFrontentLang());
      $this->tpl->set_tpl_dir("./templates");
      return $rs_content;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Return Content of all ContentItems                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function return_class_content()
    {
      $class_content = array();
      $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,RSTitle1,RSTitle2,RSTitle3,RSText1,RSText2,RSText3,RSImageTitles FROM ".$this->table_prefix."contentitem_rs cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
      while ($row = $this->db->fetch_row($result)){
        $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
        $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
        $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
        $class_content[$row["CIID"]]["c_title1"] = $row["RSTitle1"];
        $class_content[$row["CIID"]]["c_title2"] = $row["RSTitle2"];
        $class_content[$row["CIID"]]["c_title3"] = $row["RSTitle3"];
        $class_content[$row["CIID"]]["c_text1"] = $row["RSText1"];
        $class_content[$row["CIID"]]["c_text2"] = $row["RSText2"];
        $class_content[$row["CIID"]]["c_text3"] = $row["RSText3"];
        $rs_image_titles = $this->explode_content_image_titles("rs",$row["RSImageTitles"]);
        $class_content[$row["CIID"]]["c_image_title1"] = $rs_image_titles["rs_image1_title"];
        $class_content[$row["CIID"]]["c_image_title2"] = $rs_image_titles["rs_image2_title"];
        $class_content[$row["CIID"]]["c_image_title3"] = $rs_image_titles["rs_image3_title"];
        $class_content[$row["CIID"]]["c_sub"] = array();
      }
      $this->db->free_result($result);

      return $class_content;
    }
  }

