<?php

  /**
   * Content Class
   *
   * $LastChangedDate: 2012-06-26 15:54:47 +0200 (Di, 26 Jun 2012) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */

  class ContentItemTO extends ContentItem
  {
    protected $_configPrefix = 'to';
    protected $_contentPrefix = 'to';
    protected $_columnPrefix = 'T';
    protected $_contentElements = array(
      'Title' => 3,
      'Text' => 3,
    );
    protected $_contentImageTitles = false;
    protected $_templateSuffix = 'TO';

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Preview Content                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function preview(){

      $post = new Input(Input::SOURCE_POST);

      $tplName = $this->_getStandardTemplateName();
      $this->tpl->set_tpl_dir("../templates");
      $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
      $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
        'm_metainfo_part' => $this->_getMetainfoPart('to')
      ));
      $this->tpl->parse_vars($tplName, array (
        'c_to_title1' => parseOutput($post->readString('to_title1', Input::FILTER_CONTENT_TITLE),2),
        'c_to_title2' => parseOutput($post->readString('to_title2', Input::FILTER_CONTENT_TITLE),2),
        'c_to_title3' => parseOutput($post->readString('to_title3', Input::FILTER_CONTENT_TITLE),2),
        'c_to_text1' => parseOutput($post->readString('to_text1', Input::FILTER_CONTENT_TEXT), 1),
        'c_to_text2' => parseOutput($post->readString('to_text2', Input::FILTER_CONTENT_TEXT), 1),
        'c_to_text3' => parseOutput($post->readString('to_text3', Input::FILTER_CONTENT_TEXT), 1),
        'c_surl' => "../",
        'm_print_part' => $this->get_print_part(),
      ));
      $to_content = $this->tpl->parsereturn($tplName, $this->_getFrontentLang());
      $this->tpl->set_tpl_dir("./templates");
      return $to_content;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Return Content of all ContentItems                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function return_class_content()
    {
      $class_content = array();
      $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,TTitle1,TTitle2,TTitle3,TText1,TText2,TText3 FROM ".$this->table_prefix."contentitem_to cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
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
        $class_content[$row["CIID"]]["c_image_title1"] = "";
        $class_content[$row["CIID"]]["c_image_title2"] = "";
        $class_content[$row["CIID"]]["c_image_title3"] = "";
        $class_content[$row["CIID"]]["c_sub"] = array();
      }
      $this->db->free_result($result);

      return $class_content;
    }
  }

