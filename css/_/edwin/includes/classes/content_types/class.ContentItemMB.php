<?php

  /**
   * Content Class
   *
   * $LastChangedDate: 2017-10-12 14:15:08 +0200 (Do, 12 Okt 2017) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Jungwirth
   * @copyright (c) 2011 Q2E GmbH
   */
  class ContentItemMB extends ContentItem
  {
    /**
     * The glue between multiple integer ids, which
     * are stored in one database field.
     *
     * @var string
     */
    const DB_ID_DELIMITER = '$';

    protected $_configPrefix = 'mb';
    protected $_contentPrefix = 'mb';
    protected $_columnPrefix = 'MB';
    protected $_contentElements = array(
      'Title' => 3,
      'Text' => 3,
      'Image' => 3,
    );
    protected $_templateSuffix = 'MB';

    /**
     * Edit content.
     *
     * @see ContentItem::edit_content()
     */
    public function edit_content()
    {
      // Handle default content elements.
      parent::edit_content();

      $post = new Input(Input::SOURCE_POST);

      $mbCategory = $post->readArrayIntToInt('mb_category');
      $categories = '';
      if ($post->exists("mb_show_all_categories")) {
        $categories = 0;
      }
      else if ($mbCategory) {
        $categories = implode(self::DB_ID_DELIMITER, $mbCategory);
      }

      // Update the database.
      $sql = "UPDATE {$this->table_prefix}contentitem_mb "
           . "SET MBCategories = '{$this->db->escape($categories)}' "
           . "WHERE FK_CIID = $this->page_id ";
      $result = $this->db->query($sql);
    }

    public function get_content($params = array())
    {
      global $_LANG, $_LANG2;

      $row = $this->_getData();
      $showAll = ($row['MBCategories'] == '0') ? true : false;
      $categories = explode(self::DB_ID_DELIMITER, $row['MBCategories']);

      $mlCategory = new MedialibraryCategory($this->db, $this->table_prefix);
      $mlCategory->siteId = $this->site_id;
      if ($this->getConfig('categories_of_all_sites', $this->site_id)) {
        $condition = array(
          'order' => 'FK_SID ASC, MCPosition ASC',
        );
      }
      else {
        $condition = array(
          'where' => 'FK_SID = '.$this->site_id,
          'order' => 'MCPosition ASC',
        );
      }
      $mlCategories = $mlCategory->readMedialibraryCategories($condition);
      $categoryItems = array();
      foreach ($mlCategories as $category) {
        $categoryItems[] = array(
         'mb_category_checked'  => (in_array($category->id, $categories)) ? 'checked="checked"' : '',
         'mb_category_disabled' => ($showAll) ? 'disabled="disabled"' : '',
         'mb_category_id'       => $category->id,
         'mb_category_title'    => parseOutput($category->title),
         'mb_category_site_title' => self::getLanguageSiteLabel($this->_navigation->getSiteByID($category->siteId)),
        );
      }

      $tplName = $this->_getStandardTemplateName();
      $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
      $this->tpl->parse_loop($tplName, $categoryItems, 'mb_categories');
      $this->tpl->parse_vars($tplName, array (
        'mb_show_all_categories_checked' => ($showAll) ? 'checked="checked"' : '',
      ));

      return parent::get_content(array_merge($params, array(
        'row'      => $row,
        'settings' => array( 'tpl' => $tplName ),
      )));
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Preview Content                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function preview()
    {
      $post = new Input(Input::SOURCE_POST);

      $image_titles = $post->readImageTitles('mb_image_title');
      $image_titles = $this->explode_content_image_titles("c_mb",$image_titles);

      $mb_images = $this->_createPreviewImages(array(
        'MBImage1' => 'mb_image1',
        'MBImage2' => 'mb_image2',
        'MBImage3' => 'mb_image3',
      ));
      $image_src1 = $mb_images['mb_image1'];
      $image_src2 = $mb_images['mb_image2'];
      $image_src3 = $mb_images['mb_image3'];
      $image_src_large1 = $this->_hasLargeImage($image_src1);
      $image_src_large2 = $this->_hasLargeImage($image_src2);
      $image_src_large3 = $this->_hasLargeImage($image_src3);

      $tplName = $this->_getStandardTemplateName();
      $this->tpl->set_tpl_dir("../templates");
      $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
      $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
        'm_metainfo_part' => $this->_getMetainfoPart('mb')
      ));
      $this->tpl->parse_if($tplName, 'zoom1', $image_src_large1, array(
        'c_mb_zoom1_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'zoom2', $image_src_large2, array(
        'c_mb_zoom2_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'zoom3', $image_src_large3, array(
        'c_mb_zoom3_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'image1', $image_src1, array( 'c_mb_image_src1' => $image_src1 ));
      $this->tpl->parse_if($tplName, 'image2', $image_src2, array( 'c_mb_image_src2' => $image_src2 ));
      $this->tpl->parse_if($tplName, 'image3', $image_src3, array( 'c_mb_image_src3' => $image_src3 ));
      $this->tpl->parse_vars($tplName, array_merge( $image_titles, array (
        'c_mb_title1' => parseOutput($post->readString('mb_title1', Input::FILTER_CONTENT_TITLE),2),
        'c_mb_title2' => parseOutput($post->readString('mb_title2', Input::FILTER_CONTENT_TITLE),2),
        'c_mb_title3' => parseOutput($post->readString('mb_title3', Input::FILTER_CONTENT_TITLE),2),
        'c_mb_text1' => parseOutput($post->readString('mb_text1', Input::FILTER_CONTENT_TEXT), 1),
        'c_mb_text2' => parseOutput($post->readString('mb_text2', Input::FILTER_CONTENT_TEXT), 1),
        'c_mb_text3' => parseOutput($post->readString('mb_text3', Input::FILTER_CONTENT_TEXT), 1),
        'c_mb_image_src1' => $image_src1,
        'c_mb_image_src2' => $image_src2,
        'c_mb_image_src3' => $image_src3,
        'c_mb_filter_title' => '',
        'c_mb_library_items' => '',
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
      $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,MBTitle1,MBTitle2,MBTitle3,MBText1,MBText2,MBText3,MBImageTitles FROM ".$this->table_prefix."contentitem_mb cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
      while ($row = $this->db->fetch_row($result)){
        $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
        $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
        $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
        $class_content[$row["CIID"]]["c_title1"] = $row["MBTitle1"];
        $class_content[$row["CIID"]]["c_title2"] = $row["MBTitle2"];
        $class_content[$row["CIID"]]["c_title3"] = $row["MBTitle3"];
        $class_content[$row["CIID"]]["c_text1"] = $row["MBText1"];
        $class_content[$row["CIID"]]["c_text2"] = $row["MBText2"];
        $class_content[$row["CIID"]]["c_text3"] = $row["MBText3"];
        $image_titles = $this->explode_content_image_titles("mb",$row["MBImageTitles"]);
        $class_content[$row["CIID"]]["c_image_title1"] = $image_titles["mb_image1_title"];
        $class_content[$row["CIID"]]["c_image_title2"] = $image_titles["mb_image2_title"];
        $class_content[$row["CIID"]]["c_image_title3"] = $image_titles["mb_image3_title"];
        $class_content[$row["CIID"]]["c_sub"] = array();
      }
      $this->db->free_result($result);

      return $class_content;
    }

    /**
     * Gets content item's data.
     *
     * @see ContentItem::_getData()
     */
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
           . '        MBCategories '
           .( $sqlArgs ? ', '.implode(',', $sqlArgs) : '' )
           .( $this->_contentImageTitles ? ', '.$this->_columnPrefix.'ImageTitles' : '' )
           . " FROM {$this->table_prefix}contentitem ci "
           . " JOIN {$this->table_prefix}contentitem_{$this->_contentPrefix} ci_sub "
           . '      ON CIID = ci_sub.FK_CIID '
           . " WHERE CIID = $this->page_id ";
      return $this->db->GetRow($sql);
    }
  }

