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
  class ContentItemES extends ContentItem
  {
    protected $_configPrefix = 'es';
    protected $_contentPrefix = 'es';
    protected $_columnPrefix = 'E';
    protected $_contentElements = array(
      'Title' => 3,
      'Text' => 3,
      'Image' => 3,
    );
    protected $_templateSuffix = 'ES';

    protected $properties;
    protected $ext_id = 0;

    /**
     * The external content type used by this ES ContentItem (00, 01, 02, ...)
     *
     * @var int
     */
    private $_esExt = 0;

    /**
     * Parses the properties from the Form Input
     * @return array contains the properties values
     */
    protected function _parse_properties_input()
    {
      $post = new Input(Input::SOURCE_POST);

      // Read the properties from the request.
      $properties = $post->readArrayIntToString('es_properties', Input::FILTER_PLAIN);
      $es_property_quantity = $this->_getPropertiesQuantity();

      // Add missing properties.
      for ($i = 1; $i <= $es_property_quantity; $i++) {
        if (!isset($properties[$i])) {
          $properties[$i] = '';
        }
      }


      return $properties;
    }

    /**
     * Loads the properties from the DB
     * @param int $ext_id id of the external source (optional)
     * @return array contains the template vars with the labels and values of all properties
     */
    protected function _load_properties_db($ext_id = 0) {
      global $_LANG2;

      if (!$this->ext_id) $this->ext_id = $ext_id;

      // get property quantity
      $es_property_quantity = $this->_getPropertiesQuantity();

      // initialize all properties
      $lang_vars = array();
      for ($i=1; $i <= $es_property_quantity; $i++) {
        $lang_vars["es_property{$i}"] = "";
        $lang_vars["es_property{$i}_label"] = !empty($_LANG2["es_ext{$this->ext_id}"]["es_property_labels"][$i]) ? $_LANG2["es_ext{$this->ext_id}"]["es_property_labels"][$i] : "";
      }

      // load properties from db
      $es_properties = array();
      $result = $this->db->query("SELECT EProperties FROM ".$this->table_prefix."contentitem_es WHERE FK_CIID=".$this->page_id);
      $row = $this->db->fetch_row($result);
      $es_properties = unserialize($row["EProperties"]);
      if ($es_properties) {
        foreach ($es_properties as $id => $value) {
          $lang_vars["es_property{$id}"] = $value;
        }
      }
      $this->db->free_result($result);

      return $lang_vars;
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
           . '        EExt, EFrameHeight '
           .( $sqlArgs ? ', '.implode(',', $sqlArgs) : '' )
           .( $this->_contentImageTitles ? ', '.$this->_columnPrefix.'ImageTitles' : '' )
           . " FROM {$this->table_prefix}contentitem ci "
           . " JOIN {$this->table_prefix}contentitem_{$this->_contentPrefix} ci_sub "
           . '      ON CIID = ci_sub.FK_CIID '
           . " WHERE CIID = $this->page_id ";
      return $this->db->GetRow($sql);
    }

    protected function _getPropertiesQuantity()
    {
      $prefix = array_unique(array(
        $this->_configPrefix . '_ext' . $this->ext_id,
        $this->_contentPrefix . '_ext' . $this->ext_id,
        $this->_configPrefix,
        $this->_contentPrefix,
      ));

      return (int)ConfigHelper::get('properties_quantity', $prefix);
    }

    protected function _getTemplatePath($string = '')
    {
      $tplPath = '/content_types/ContentItem' . $this->_templateSuffix . '_EXT'
               . $this->_esExt . '.tpl';

      if (!is_file($this->tpl->get_root() . $tplPath)) {
        $tplPath = parent::_getTemplatePath();
      }

      return $tplPath;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Edit Content                                                                          //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function edit_content()
    {
      // Handle default content elements.
      parent::edit_content();

      $post = new Input(Input::SOURCE_POST);

      if (isset($_POST['es_ext'])){
        $es_ext = (int)$_POST['es_ext'];
        $this->_esExt = sprintf('%02d', $es_ext);
      }
      else{
        $es_ext = $this->db->GetOne("SELECT EExt FROM {$this->table_prefix}contentitem_es WHERE FK_CIID = $this->page_id");
        $this->_esExt = sprintf('%02d', $es_ext);
      }

      $es_frame_height = '';
      if (isset($_POST['es_frame_height'])) {
        $es_frame_height = ' ,EFrameHeight = '.$this->db->escape((int)$_POST['es_frame_height']);
      }

      $es_subclass = "ContentItemES_EXT".$this->_esExt;
      if (class_exists($es_subclass, true)) {
        $es_detail = new $es_subclass($this->site_id, $this->page_id, $this->tpl, $this->db, $this->table_prefix, $this->action, $this->page_path, $this->_user, $this->session, $this->_navigation);
        $this->properties = $es_detail->_parse_properties_input();
      }
      else {
        $this->properties = $this->_parse_properties_input();
      }

      $sqlProperties = $this->properties ? ", EProperties = '{$this->db->escape(serialize($this->properties))}'" : "";

      // Update the database.
      $sql = "UPDATE {$this->table_prefix}contentitem_es "
           . "SET EExt = '{$this->db->escape($es_ext)}' "
           . "    $es_frame_height "
           . "    $sqlProperties "
           . "WHERE FK_CIID = $this->page_id ";
      $result = $this->db->query($sql);
    }

    public function get_content($params = array())
    {
      global $_LANG,$_LANG2;

      $post = new Input(Input::SOURCE_POST);

      $row = $this->_getData();

      // change of subtype only available, when not already stored
      $es_first_request = 0;
      if (!$row["EExt"]) $es_first_request = 1;

      $this->_esExt = sprintf("%02d",intval($row["EExt"]));
      if (isset($_POST["es_ext"]))
        if ($post->readInt('es_ext') && $post->readInt('es_ext') != $this->_esExt)
          $this->_esExt = sprintf("%02d",$post->readInt('es_ext'));

      // create external modules dropdown
      if ($es_first_request){
        $availableSources = $this->getConfig('available_ext_sources');
        $tmp_ext_module = '<select name="es_ext" class="form-control" onChange="if (this.options[this.selectedIndex].value > 0){ document.forms.inputform.submit(); }">';
        foreach ($_LANG["es_ext_modules"] as $eid => $evalue){
          if (in_array($eid, $availableSources)){
            if (!(int)$this->_esExt) $this->_esExt = sprintf("%02d",$eid);
            $tmp_ext_module .= '<option value="'.$eid.'"';
            if ($this->_esExt == $eid) $tmp_ext_module .= ' selected="selected"';
            $tmp_ext_module .= '>'.$evalue.'</option>';
          }
        }
        $es_ext_select = $tmp_ext_module."</select>";
      }
      else
        $es_ext_select = $_LANG["es_ext_modules"][(int)$this->_esExt];

      // load properties for external source
      $es_subclass = "ContentItemES_EXT".$this->_esExt;
      if (class_exists($es_subclass, true)){
        $es_detail = new $es_subclass($this->site_id, $this->page_id, $this->tpl, $this->db, $this->table_prefix, $this->action, $this->page_path, $this->_user, $this->session, $this->_navigation);
        $es_ext_properties = $es_detail->_load_properties_db();
      }
      else {
        $es_ext_properties = $this->_load_properties_db();
      }

      $tplName = $this->_getStandardTemplateName();
      $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
      $this->tpl->parse_vars($tplName, array_merge(array (
        'es_ext' => $es_ext_select,
        'es_ext_selected' => ($es_first_request ? "" : "active"),
        'es_ext_label' => $_LANG["es_ext_label"],
        'es_properties_label' => $_LANG["es_properties_label"],
        'es_frame_height_value' => $row["EFrameHeight"],
      ), $es_ext_properties, (isset($_LANG2["es_ext".$this->_esExt]) ? $_LANG2["es_ext".$this->_esExt] : array()) ));

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

      $es_image_titles = $post->readImageTitles('es_image_title');
      $es_image_titles = $this->explode_content_image_titles("c_es",$es_image_titles);

      $es_images = $this->_createPreviewImages(array(
        'EImage1' => 'es_image1',
        'EImage2' => 'es_image2',
        'EImage3' => 'es_image3',
      ));
      $es_image_src1 = $es_images['es_image1'];
      $es_image_src2 = $es_images['es_image2'];
      $es_image_src3 = $es_images['es_image3'];
      $es_image1_large = $this->_hasLargeImage($es_image_src1);
      $es_image2_large = $this->_hasLargeImage($es_image_src2);
      $es_image3_large = $this->_hasLargeImage($es_image_src3);

      $tplName = $this->_getStandardTemplateName();
      $this->tpl->set_tpl_dir("../templates");
      $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
      $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
        'm_metainfo_part' => $this->_getMetainfoPart('es')
      ));
      $this->tpl->parse_if($tplName, 'zoom1', $es_image1_large, array(
        'c_es_zoom1_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'zoom2', $es_image2_large, array(
        'c_es_zoom2_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'zoom3', $es_image3_large, array(
        'c_es_zoom3_link' => '#',
      ));
      $this->tpl->parse_if($tplName, 'image1', $es_image_src1, array( 'c_es_image_src1' => $es_image_src1 ));
      $this->tpl->parse_if($tplName, 'image2', $es_image_src2, array( 'c_es_image_src2' => $es_image_src2 ));
      $this->tpl->parse_if($tplName, 'image3', $es_image_src3, array( 'c_es_image_src3' => $es_image_src3 ));
      $this->tpl->parse_vars($tplName, array_merge( $es_image_titles, array (
        'c_es_title1' => parseOutput($post->readString('es_title1', Input::FILTER_CONTENT_TITLE),2),
        'c_es_title2' => parseOutput($post->readString('es_title2', Input::FILTER_CONTENT_TITLE),2),
        'c_es_title3' => parseOutput($post->readString('es_title3', Input::FILTER_CONTENT_TITLE),2),
        'c_es_text1' => parseOutput($post->readString('es_text1', Input::FILTER_CONTENT_TEXT), 1),
        'c_es_text2' => parseOutput($post->readString('es_text2', Input::FILTER_CONTENT_TEXT), 1),
        'c_es_text3' => parseOutput($post->readString('es_text3', Input::FILTER_CONTENT_TEXT), 1),
        'c_es_image_src1' => $es_image_src1,
        'c_es_image_src2' => $es_image_src2,
        'c_es_image_src3' => $es_image_src3,
        'c_es_ext_content' => "",
        'c_surl' => "../",
        'm_print_part' => $this->get_print_part(),
      )));
      $es_content = $this->tpl->parsereturn($tplName, $this->_getFrontentLang());
      $this->tpl->set_tpl_dir("./templates");
      return $es_content;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Return Content of all ContentItems                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function return_class_content()
    {
      $class_content = array();
      $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,ETitle1,ETitle2,ETitle3,EText1,EText2,EText3,EImageTitles FROM ".$this->table_prefix."contentitem_es cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
      while ($row = $this->db->fetch_row($result)){
        $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
        $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
        $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
        $class_content[$row["CIID"]]["c_title1"] = $row["ETitle1"];
        $class_content[$row["CIID"]]["c_title2"] = $row["ETitle2"];
        $class_content[$row["CIID"]]["c_title3"] = $row["ETitle3"];
        $class_content[$row["CIID"]]["c_text1"] = $row["EText1"];
        $class_content[$row["CIID"]]["c_text2"] = $row["EText2"];
        $class_content[$row["CIID"]]["c_text3"] = $row["EText3"];
        $es_image_titles = $this->explode_content_image_titles("es",$row["EImageTitles"]);
        $class_content[$row["CIID"]]["c_image_title1"] = $es_image_titles["es_image1_title"];
        $class_content[$row["CIID"]]["c_image_title2"] = $es_image_titles["es_image2_title"];
        $class_content[$row["CIID"]]["c_image_title3"] = $es_image_titles["es_image3_title"];
        $class_content[$row["CIID"]]["c_sub"] = array();
      }
      $this->db->free_result($result);

      return $class_content;
    }
  }

