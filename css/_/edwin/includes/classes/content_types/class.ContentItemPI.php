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
  class ContentItemPI extends ContentItem
  {
    protected $_configPrefix = 'pi';
    protected $_contentPrefix = 'pi';
    protected $_columnPrefix = 'P';
    protected $_contentElements = array(
      'Title' => 3,
      'Text' => 3,
      'Image' => 3,
    );
    protected $_templateSuffix = 'PI';

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Preview Content                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function preview(){

      $cp = $this->_contentPrefix;
      $tp = 'c_' . $cp;
      $this->tpl->set_tpl_dir("../templates");
      $post = new Input(Input::SOURCE_POST);
      $className = 'ContentItem' . $this->_templateSuffix;

      $post = new Input(Input::SOURCE_POST);

      $pi_image_titles = $post->readImageTitles('pi_image_title');
      $pi_image_titles = $this->explode_content_image_titles("c_pi",$pi_image_titles);

      $pi_images = $this->_createPreviewImages(array(
        'PImage1' => 'pi_image1',
        'PImage2' => 'pi_image2',
        'PImage3' => 'pi_image3',
      ));
      $pi_image_src1 = $pi_images['pi_image1'];
      $pi_image_src2 = $pi_images['pi_image2'];
      $pi_image_src3 = $pi_images['pi_image3'];
      $pi_image1_large = $this->_hasLargeImage($pi_image_src1);
      $pi_image2_large = $this->_hasLargeImage($pi_image_src2);
      $pi_image3_large = $this->_hasLargeImage($pi_image_src3);

      $tplName = $this->_getStandardTemplateName();
      $tplPath = $this->_getTemplatePath();
      $this->tpl->set_tpl_dir("../templates");
      $this->tpl->load_tpl($tplName, $tplPath);
      $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
        'm_metainfo_part' => $this->_getMetainfoPart('pi')
      ));
      $this->tpl->parse_if($tplName, 'zoom1', $pi_image1_large, array(
        'c_pi_zoom1_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'zoom2', $pi_image2_large, array(
        'c_pi_zoom2_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'zoom3', $pi_image3_large, array(
        'c_pi_zoom3_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'project1', $post->readString('pi_text2', Input::FILTER_CONTENT_TEXT), array ( 'c_pi_title2' => parseOutput($post->readString('pi_title2', Input::FILTER_CONTENT_TITLE),2),
                                                                                                                   'c_pi_text2' => parseOutput($post->readString('pi_text2', Input::FILTER_CONTENT_TEXT), 1),
                                                                                                                   'c_pi_image_src2' => $pi_image_src2 ));
      $this->tpl->parse_if($tplName, 'project2', $post->readString('pi_text3', Input::FILTER_CONTENT_TEXT), array ( 'c_pi_title3' => parseOutput($post->readString('pi_title3', Input::FILTER_CONTENT_TITLE),2),
                                                                                                                   'c_pi_text3' => parseOutput($post->readString('pi_text3', Input::FILTER_CONTENT_TEXT), 1),
                                                                                                                   'c_pi_image_src3' => $pi_image_src3 ));
      $this->tpl->parse_vars($tplName, array_merge( $pi_image_titles, array (
        'c_pi_title1' => parseOutput($post->readString('pi_title1', Input::FILTER_CONTENT_TITLE),2),
        'c_pi_title2' => parseOutput($post->readString('pi_title2', Input::FILTER_CONTENT_TITLE),2),
        'c_pi_title3' => parseOutput($post->readString('pi_title3', Input::FILTER_CONTENT_TITLE),2),
        'c_pi_text1' => parseOutput($post->readString('pi_text1', Input::FILTER_CONTENT_TEXT), 1),
        'c_pi_text2' => parseOutput($post->readString('pi_text2', Input::FILTER_CONTENT_TEXT), 1),
        'c_pi_text3' => parseOutput($post->readString('pi_text3', Input::FILTER_CONTENT_TEXT), 1),
        'c_pi_image_src1' => $pi_image_src1,
        'c_pi_image_src2' => $pi_image_src2,
        'c_pi_image_src3' => $pi_image_src3,
        'c_surl' => "../",
        'm_print_part' => $this->get_print_part(),
      )));
      $pi_content = $this->tpl->parsereturn($tplName, $this->_getFrontentLang());
      $this->tpl->set_tpl_dir("./templates");
      return $pi_content;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Return Content of all ContentItems                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function return_class_content()
    {
      $class_content = array();
      $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,PTitle1,PTitle2,PTitle3,PText1,PText2,PText3,PImageTitles FROM ".$this->table_prefix."contentitem_pi cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
      while ($row = $this->db->fetch_row($result)){
        $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
        $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
        $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
        $class_content[$row["CIID"]]["c_title1"] = $row["PTitle1"];
        $class_content[$row["CIID"]]["c_title2"] = $row["PTitle2"];
        $class_content[$row["CIID"]]["c_title3"] = $row["PTitle3"];
        $class_content[$row["CIID"]]["c_text1"] = $row["PText1"];
        $class_content[$row["CIID"]]["c_text2"] = $row["PText2"];
        $class_content[$row["CIID"]]["c_text3"] = $row["PText3"];
        $pi_image_titles = $this->explode_content_image_titles("pi",$row["PImageTitles"]);
        $class_content[$row["CIID"]]["c_image_title1"] = $pi_image_titles["pi_image1_title"];
        $class_content[$row["CIID"]]["c_image_title2"] = $pi_image_titles["pi_image2_title"];
        $class_content[$row["CIID"]]["c_image_title3"] = $pi_image_titles["pi_image3_title"];
        $class_content[$row["CIID"]]["c_sub"] = array();
      }
      $this->db->free_result($result);

      return $class_content;
    }
  }

