<?php

  /**
   * Content Class
   *
   * $LastChangedDate: 2017-08-21 07:58:57 +0200 (Mo, 21 Aug 2017) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */
  class ContentItemNL extends ContentItem
  {
    protected $_configPrefix = 'nl';
    protected $_contentPrefix = 'nl';
    protected $_columnPrefix = 'NL';
    protected $_contentElements = array(
      'Title' => 3,
      'Text' => 3,
      'Image' => 1,
    );
    protected $_contentImageTitles = false;
    protected $_templateSuffix = 'NL';

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Preview Content                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function preview() {
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);

      $nl_images = $this->_createPreviewImages(array(
        'NLImage' => 'nl_image',
      ));
      $nl_image_src = $nl_images['nl_image'];
      $nl_image_large = $this->_hasLargeImage($nl_image_src);

      $nl_form_input = array();
      for ($i=1;$i<=25;$i++)
        $nl_form_input['form_field'.$i] = "";

      $this->tpl->set_tpl_dir("../templates");
      $this->tpl->load_tpl('content_site_nl', 'content_types/ContentItemNL.tpl');
      $this->tpl->parse_if('content_site_nl', 'inside_archive', $this->_isInsideArchive(), array(
        'm_metainfo_part' => $this->_getMetainfoPart('nl')
      ));
      $this->tpl->parse_if('content_site_nl', 'zoom', $nl_image_large, array(
        'c_nl_zoom_link' => '#',
      ));
      $this->tpl->parse_if('content_site_nl', 'message', false);
      $this->tpl->parse_vars('content_site_nl', array_merge($_LANG["form_labels"], $nl_form_input, array (
        'c_nl_title1' => parseOutput($post->readString('nl_title1', Input::FILTER_CONTENT_TITLE),2),
        'c_nl_title2' => parseOutput($post->readString('nl_title2', Input::FILTER_CONTENT_TITLE),2),
        'c_nl_title3' => parseOutput($post->readString('nl_title3', Input::FILTER_CONTENT_TITLE),2),
        'c_nl_text1' => parseOutput($post->readString('nl_text1', Input::FILTER_CONTENT_TEXT), 1),
        'c_nl_text2' => parseOutput($post->readString('nl_text2', Input::FILTER_CONTENT_TEXT), 1),
        'c_nl_text3' => parseOutput($post->readString('nl_text3', Input::FILTER_CONTENT_TEXT), 1),
        'c_nl_image_src' => $nl_image_src,
        'c_nl_action' => "",
        'form_send_label' => $_LANG["c_form_send_label"],
        'c_surl' => "../"
      )));
      $nl_content = $this->tpl->parsereturn('content_site_nl', $this->_getFrontentLang());
      $this->tpl->set_tpl_dir("./templates");
      return $nl_content;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Return Content of all ContentItems                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function return_class_content()
    {
      $class_content = array();
      $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,NLTitle1,NLTitle2,NLTitle3,NLText1,NLText2,NLText3 FROM ".$this->table_prefix."contentitem_nl cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
      while ($row = $this->db->fetch_row($result)){
        $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
        $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
        $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
        $class_content[$row["CIID"]]["c_title1"] = $row["NLTitle1"];
        $class_content[$row["CIID"]]["c_title2"] = $row["NLTitle2"];
        $class_content[$row["CIID"]]["c_title3"] = $row["NLTitle3"];
        $class_content[$row["CIID"]]["c_text1"] = $row["NLText1"];
        $class_content[$row["CIID"]]["c_text2"] = $row["NLText2"];
        $class_content[$row["CIID"]]["c_text3"] = $row["NLText3"];
        $class_content[$row["CIID"]]["c_image_title1"] = "";
        $class_content[$row["CIID"]]["c_image_title2"] = "";
        $class_content[$row["CIID"]]["c_image_title3"] = "";
        $class_content[$row["CIID"]]["c_sub"] = array();
      }
      $this->db->free_result($result);

      return $class_content;
    }
  }

