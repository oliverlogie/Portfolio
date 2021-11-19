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

  class ContentItemTI extends ContentItem
  {
    protected $_configPrefix = 'ti';
    protected $_contentPrefix = 'ti';
    protected $_columnPrefix = 'T';
    protected $_contentElements = array(
      'Title' => 3,
      'Text' => 3,
      'Image' => 3,
    );
    protected $_templateSuffix = 'TI';

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Preview Content                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function preview(){

      $post = new Input(Input::SOURCE_POST);

      $ti_image_titles = $post->readImageTitles('ti_image_title');
      $ti_image_titles = $this->explode_content_image_titles("c_ti",$ti_image_titles);

      $ti_images = $this->_createPreviewImages(array(
        'TImage1' => 'ti_image1',
        'TImage2' => 'ti_image2',
        'TImage3' => 'ti_image3',
      ));
      $ti_image_src1 = $ti_images['ti_image1'];
      $ti_image_src2 = $ti_images['ti_image2'];
      $ti_image_src3 = $ti_images['ti_image3'];
      $ti_image_src_large1 = $this->_hasLargeImage($ti_image_src1);
      $ti_image_src_large2 = $this->_hasLargeImage($ti_image_src2);
      $ti_image_src_large3 = $this->_hasLargeImage($ti_image_src3);

      $tplName = $this->_getStandardTemplateName();
      $this->tpl->set_tpl_dir("../templates");
      $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
      $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
        'm_metainfo_part' => $this->_getMetainfoPart('ti')
      ));
      $this->tpl->parse_if($tplName, 'zoom1', $ti_image_src_large1, array(
        'c_ti_zoom1_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'zoom2', $ti_image_src_large2, array(
        'c_ti_zoom2_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'zoom3', $ti_image_src_large3, array(
        'c_ti_zoom3_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'image1', $ti_image_src1, array( 'c_ti_image_src1' => $ti_image_src1 ));
      $this->tpl->parse_if($tplName, 'image2', $ti_image_src2, array( 'c_ti_image_src2' => $ti_image_src2 ));
      $this->tpl->parse_if($tplName, 'image3', $ti_image_src3, array( 'c_ti_image_src3' => $ti_image_src3 ));
      $this->tpl->parse_vars($tplName, array_merge( $ti_image_titles, array (
        'c_ti_title1' => parseOutput($post->readString('ti_title1', Input::FILTER_CONTENT_TITLE),2),
        'c_ti_title2' => parseOutput($post->readString('ti_title2', Input::FILTER_CONTENT_TITLE),2),
        'c_ti_title3' => parseOutput($post->readString('ti_title3', Input::FILTER_CONTENT_TITLE),2),
        'c_ti_text1' => parseOutput($post->readString('ti_text1', Input::FILTER_CONTENT_TEXT), 1),
        'c_ti_text2' => parseOutput($post->readString('ti_text2', Input::FILTER_CONTENT_TEXT), 1),
        'c_ti_text3' => parseOutput($post->readString('ti_text3', Input::FILTER_CONTENT_TEXT), 1),
        'c_ti_image_src1' => $ti_image_src1,
        'c_ti_image_src2' => $ti_image_src2,
        'c_ti_image_src3' => $ti_image_src3,
        'c_surl' => "../",
        'm_print_part' => $this->get_print_part(),
      )));
      $ti_content = $this->tpl->parsereturn($tplName, $this->_getFrontentLang());
      $this->tpl->set_tpl_dir("./templates");
      return $ti_content;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Return Content of all ContentItems                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function return_class_content()
    {
      $class_content = array();
      $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,TTitle1,TTitle2,TTitle3,TText1,TText2,TText3,TImageTitles FROM ".$this->table_prefix."contentitem_ti cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
      while ($row = $this->db->fetch_row($result)){
        $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
        $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
        $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
        $class_content[$row["CIID"]]["c_title1"] = $row["TTitle1"];
        $class_content[$row["CIID"]]["c_title2"] = $row["TTitle2"];
        $class_content[$row["CIID"]]["c_title3"] = $row["TTitle3"];
        $class_content[$row["CIID"]]["c_text1"] = $row["TText1"];
        $class_content[$row["CIID"]]["c_text2"] = $row["TText2"];
        $class_content[$row["CIID"]]["c_text3"] = $row["TText3"];
        $ti_image_titles = $this->explode_content_image_titles("ti",$row["TImageTitles"]);
        $class_content[$row["CIID"]]["c_image_title1"] = $ti_image_titles["ti_image1_title"];
        $class_content[$row["CIID"]]["c_image_title2"] = $ti_image_titles["ti_image2_title"];
        $class_content[$row["CIID"]]["c_image_title3"] = $ti_image_titles["ti_image3_title"];
        $class_content[$row["CIID"]]["c_sub"] = array();
      }
      $this->db->free_result($result);

      return $class_content;
    }
  }

