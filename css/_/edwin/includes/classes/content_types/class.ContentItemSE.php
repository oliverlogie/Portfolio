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
  class ContentItemSE extends ContentItem
  {
    protected $_configPrefix = 'se';
    protected $_contentPrefix = 'se';
    protected $_columnPrefix = 'S';
    protected $_contentElements = array(
      'Title' => 1,
      'Text' => 1,
      'Image' => 1,
    );
    protected $_contentImageTitles = false;
    protected $_templateSuffix = 'SE';

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Preview Content                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function preview(){
      global $_LANG;

      $post = new Input(Input::SOURCE_POST);

      $se_images = $this->_createPreviewImages(array(
        'SImage' => 'se_image',
      ));
      $se_image_src = $se_images['se_image'];
      $se_image_src_large = self::_hasLargeImage($se_image_src);

      $this->tpl->set_tpl_dir("../templates");
      $this->tpl->load_tpl('content_site_se', $this->_getTemplatePath());
      $this->tpl->parse_if('content_site_se', 'inside_archive', $this->_isInsideArchive(), array(
        'm_metainfo_part' => $this->_getMetainfoPart('se')
      ));
      $this->tpl->parse_if('content_site_se', 'zoom', $se_image_src_large, array(
        'c_se_zoom_link' => '#',
      ));
      $this->tpl->parse_if('content_site_se', 'message', false);
      $this->tpl->parse_if('content_site_se', 'more_pages', false);
      $this->tpl->parse_loop('content_site_se', 0, 'searchresult_rows');
      $this->tpl->parse_loop('content_site_se', 0, 'tag_global_items');
      $this->tpl->parse_if('content_site_se', 'login_search_available', false);
      $this->tpl->parse_if('content_site_se', 'images_search_available', false);
      $this->tpl->parse_if('content_site_se', 'files_search_available', false);
      $this->tpl->parse_if('content_site_se', 'employee_search_available', false);
      $this->tpl->parse_if('content_site_se', 'tag_search_available', false);
      $this->tpl->parse_if('content_site_se', 'tag_search_available1', false);
      $this->tpl->parse_vars('content_site_se', array (
        'c_se_content_searchresult' => '',
        'c_se_search_default_link_label' => '',
        'c_se_searchstring_value' => '',
        'c_se_title' => parseOutput($post->readString('se_title', Input::FILTER_CONTENT_TITLE),2),
        'c_se_text' => parseOutput($post->readString('se_text', Input::FILTER_CONTENT_TEXT), 1),
        'c_se_image_src' => $se_image_src,
        'c_se_search_label' => $_LANG["c_se_search_label"],
        'c_se_searchtype_exact_label' => $_LANG["c_se_searchtype_exact_label"],
        'c_se_button_send_label' => $_LANG["c_se_button_send_label"],
        'c_se_action' => "",
        'c_surl' => "../"
      ));
      $content = $this->tpl->parsereturn('content_site_se', $this->_getFrontentLang());
      $this->tpl->set_tpl_dir("./templates");
      return $content;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Return Content of all ContentItems                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function return_class_content()
    {
      $class_content = array();
      $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,STitle,SText FROM ".$this->table_prefix."contentitem_se cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
      while ($row = $this->db->fetch_row($result)){
        $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
        $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
        $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
        $class_content[$row["CIID"]]["c_title1"] = $row["STitle"];
        $class_content[$row["CIID"]]["c_title2"] = "";
        $class_content[$row["CIID"]]["c_title3"] = "";
        $class_content[$row["CIID"]]["c_text1"] = $row["SText"];
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
  }

