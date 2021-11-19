<?php

/**
 * Content Class for ContentItemVC (VideoContent)
 *
 * $LastChangedDate: 2017-11-10 15:41:06 +0100 (Fr, 10 Nov 2017) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Stefan Podskubka
 * @copyright (c) 2009 Q2E GmbH
 */
class ContentItemVC extends ContentItem
{
  protected $_configPrefix = 'vc';
  protected $_contentPrefix = 'vc';
  protected $_columnPrefix = 'V';
  protected $_contentElements = array(
    'Title' => 3,
    'Text' => 3,
    'Image' => 4,
  );
  protected $_contentBoxImage = 4;
  protected $_templateSuffix = 'VC';

  /**
   * contains the parameter that was passed with the save button (with name="process_save[parameter]")
   * @var string
   */
  private $vc_save_parameter = '';

  /**
   * Processes the video data according to the video type and returns the VideoID as an array
   * @param string $videoType the name of the video type (i.e. "youtube")
   * @param string $videoData the video data that was entered by the user
   * @return array contains the VideoID or an empty array if the data couldn't be processed
   */
  private function parse_video_data($videoType, $videoData) {
    global $_LANG;

    $videoID = array();

    if ($videoType && $videoData && $videoType != "none") {
      $types = $this->getConfig('video_types');
      $regex = $types[$videoType]["data_regex"];

      preg_match($regex, $videoData, $videoID);

      // remove the first element in $videoID because it contains the text that matched the full pattern,
      // but we only want the texts that matched the sub-patterns
      array_shift($videoID);

      if (!$videoID) {
        $this->setMessage(Message::createFailure($_LANG["vc_message_invalid_video_data"]));
      }
    }

    return $videoID;
  }

  /**
   * returns a css string that shows or hides an element
   * @param bool $visible true if the element should be shown, false otherwise
   * @return string either "visibility: visible; display: block;" or "visibility: hidden; display: none;"
   */
  private static function get_css_visibility($visible) {
    return $visible ? "visibility: visible; display: block;" : "visibility: hidden; display: none;";
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Edit Content                                                                          //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function edit_content()
  {
    $post = new Input(Input::SOURCE_POST);

    // Tead out the parameter that was passed with the save button.
    $this->vc_save_parameter = $post->readKey('process_save');

    // Handle default content elements.
    parent::edit_content();

    // Video 1
    $videoType1 = $post->readString('vc_video_type1', Input::FILTER_NONE);
    $videoData1 = $post->readString('vc_video_data1', Input::FILTER_NONE);
    $video1 = self::parse_video_data($videoType1, $videoData1);
    if ($video1) {
      $video1 = serialize($video1);
    } else {
      $videoType1 = $video1 = '';
    }

    // Video 2
    $videoType2 = $post->readString('vc_video_type2', Input::FILTER_NONE);
    $videoData2 = $post->readString('vc_video_data2', Input::FILTER_NONE);
    $video2 = self::parse_video_data($videoType2, $videoData2);
    if ($video2) {
      $video2 = serialize($video2);
    } else {
      $videoType2 = $video2 = '';
    }

    // Video 3
    $videoType3 = $post->readString('vc_video_type3', Input::FILTER_NONE);
    $videoData3 = $post->readString('vc_video_data3', Input::FILTER_NONE);
    $video3 = self::parse_video_data($videoType3, $videoData3);
    if ($video3) {
      $video3 = serialize($video3);
    } else {
      $videoType3 = $video3 = '';
    }

    // Update the database.
    $sql = "UPDATE {$this->table_prefix}contentitem_vc "
         . "SET VVideoType1 = '{$this->db->escape($videoType1)}', "
         . "    VVideo1 = '{$this->db->escape($video1)}', "
         . "    VVideoType2 = '{$this->db->escape($videoType2)}', "
         . "    VVideo2 = '{$this->db->escape($video2)}', "
         . "    VVideoType3 = '{$this->db->escape($videoType3)}', "
         . "    VVideo3 = '{$this->db->escape($video3)}' "
         . "WHERE FK_CIID = $this->page_id ";
    $result = $this->db->query($sql);
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2;

    // delete video
    $dvid = isset($_GET["dvid"]) ? intval($_GET["dvid"]) : 0;
    if ($dvid) {
      $result = $this->db->query(<<<SQL
UPDATE {$this->table_prefix}contentitem_vc
SET VVideoType$dvid = '', VVideo$dvid = ''
WHERE FK_CIID = $this->page_id
SQL
      );
      header("Location: index.php?action=content&site=".$this->site_id."&page=".$this->page_id);
      exit();
    }

    $row = $this->_getData();
    $videoTypes = $this->getConfig('video_types');
    $videoTypesAvailable = $this->getConfig('video_types_available');

    $vc_video_type1 = $vc_video_type2 = $vc_video_type3 = "";
    $vc_video1 = $vc_video2 = $vc_video3 = array();
    $vc_video_data1 = $vc_video_data2 = $vc_video_data3 = "";
    $vc_video_url1 = $vc_video_url2 = $vc_video_url3 = "";

    // Video 1
    if ($row["VVideoType1"] && $row["VVideo1"] && isset($videoTypes[$row["VVideoType1"]])) {
      $vc_video_type1 = $row["VVideoType1"];
      $vc_video1 = unserialize($row["VVideo1"]);
      $vc_video_data1 = vsprintf($videoTypes[$vc_video_type1]["data_format"], $vc_video1);
      $vc_video_url1 = vsprintf($videoTypes[$vc_video_type1]["url"], $vc_video1);
    }

    // Video 2
    if ($row["VVideoType2"] && $row["VVideo2"] && isset($videoTypes[$row["VVideoType2"]])) {
      $vc_video_type2 = $row["VVideoType2"];
      $vc_video2 = unserialize($row["VVideo2"]);
      $vc_video_data2 = vsprintf($videoTypes[$vc_video_type2]["data_format"], $vc_video2);
      $vc_video_url2 = vsprintf($videoTypes[$vc_video_type2]["url"], $vc_video2);
    }

    // Video 3
    if ($row["VVideoType3"] && $row["VVideo3"] && isset($videoTypes[$row["VVideoType3"]])) {
      $vc_video_type3 = $row["VVideoType3"];
      $vc_video3 = unserialize($row["VVideo3"]);
      $vc_video_data3 = vsprintf($videoTypes[$vc_video_type3]["data_format"], $vc_video3);
      $vc_video_url3 = vsprintf($videoTypes[$vc_video_type3]["url"], $vc_video3);
    }

    $vc_showvideo1 = $vc_video1 || !$row["VImage1"];
    $vc_showvideo2 = $vc_video2 || !$row["VImage2"];
    $vc_showvideo3 = $vc_video3 || !$row["VImage3"];

    // create the $video_types_js array which contains labels and descriptions for all available video types
    // also create the $video_typesX arrays which contain the names and labels for the individual selects
    $video_types_js = array();
    $video_types_js[] = array(
        "vc_video_type_name" => "none",
        "vc_video_type_data_label" => $_LANG["vc_video_type_none_data_label"],
        "vc_video_type_data_descr" => $_LANG["vc_video_type_none_data_descr"],
    );
    $video_types1 = array();
    $video_types1[] = array(
      "vc_video_type_name" => "none",
      "vc_video_type_selected" => !$vc_video_type1 ? ' selected="selected"' : "",
      "vc_video_type_label" => parseOutput($_LANG["vc_video_type_none_label"]),
    );
    $video_types2[] = array(
      "vc_video_type_name" => "none",
      "vc_video_type_selected" => !$vc_video_type2 ? ' selected="selected"' : "",
      "vc_video_type_label" => parseOutput($_LANG["vc_video_type_none_label"]),
    );
    $video_types3[] = array(
      "vc_video_type_name" => "none",
      "vc_video_type_selected" => !$vc_video_type3 ? ' selected="selected"' : "",
      "vc_video_type_label" => parseOutput($_LANG["vc_video_type_none_label"]),
    );
    foreach ($videoTypesAvailable as $video_type_name) {
      $video_types_js[] = array(
        "vc_video_type_name" => $video_type_name,
        "vc_video_type_data_label" => $_LANG["vc_video_type_{$video_type_name}_data_label"],
        "vc_video_type_data_descr" => $_LANG["vc_video_type_{$video_type_name}_data_descr"],
      );
      $video_types1[] = array(
        "vc_video_type_name" => $video_type_name,
        "vc_video_type_selected" => $vc_video_type1 == $video_type_name ? ' selected="selected"' : "",
        "vc_video_type_label" => parseOutput($_LANG["vc_video_type_{$video_type_name}_label"]),
      );
      $video_types2[] = array(
        "vc_video_type_name" => $video_type_name,
        "vc_video_type_selected" => $vc_video_type2 == $video_type_name ? ' selected="selected"' : "",
        "vc_video_type_label" => parseOutput($_LANG["vc_video_type_{$video_type_name}_label"]),
      );
      $video_types3[] = array(
        "vc_video_type_name" => $video_type_name,
        "vc_video_type_selected" => $vc_video_type3 == $video_type_name ? ' selected="selected"' : "",
        "vc_video_type_label" => parseOutput($_LANG["vc_video_type_{$video_type_name}_label"]),
      );
    }

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_loop($tplName, $video_types_js, "video_types_js");
    $this->tpl->parse_loop($tplName, $video_types1, "video_types1");
    $this->tpl->parse_loop($tplName, $video_types2, "video_types2");
    $this->tpl->parse_loop($tplName, $video_types3, "video_types3");
    $this->tpl->parse_if($tplName, 'video1', $vc_video1, array(
      "vc_video1" => parseOutput(implode(", ", $vc_video1)),
    ));
    $this->tpl->parse_if($tplName, "delete_video1", $vc_video1, array(
      "vc_delete_video1_link" => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;dvid=1",
    ));
    $this->tpl->parse_if($tplName, 'video2', $vc_video2, array(
      "vc_video2" => parseOutput(implode(", ", $vc_video2)),
    ));
    $this->tpl->parse_if($tplName, "delete_video2", $vc_video2, array(
      "vc_delete_video2_link" => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;dvid=2",
    ));
    $this->tpl->parse_if($tplName, 'video3', $vc_video3, array(
      "vc_video3" => parseOutput(implode(", ", $vc_video3)),
    ));
    $this->tpl->parse_if($tplName, "delete_video3", $vc_video3, array(
      "vc_delete_video3_link" => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;dvid=3",
    ));
    $vc_content = $this->tpl->parsereturn($tplName, array(
      "vc_video_class1" => $vc_showvideo1 ? "active" : "inactive",
      "vc_video_class2" => $vc_showvideo2 ? "active" : "inactive",
      "vc_video_class3" => $vc_showvideo3 ? "active" : "inactive",
      "vc_video_visibility1" => self::get_css_visibility($vc_showvideo1),
      "vc_video_visibility2" => self::get_css_visibility($vc_showvideo2),
      "vc_video_visibility3" => self::get_css_visibility($vc_showvideo3),
      "vc_video_type_data_label1" => $vc_video_type1 ? $_LANG["vc_video_type_{$vc_video_type1}_data_label"] : $_LANG["vc_video_type_none_data_label"],
      "vc_video_type_data_label2" => $vc_video_type2 ? $_LANG["vc_video_type_{$vc_video_type2}_data_label"] : $_LANG["vc_video_type_none_data_label"],
      "vc_video_type_data_label3" => $vc_video_type3 ? $_LANG["vc_video_type_{$vc_video_type3}_data_label"] : $_LANG["vc_video_type_none_data_label"],
      "vc_video_type_data_descr1" => $vc_video_type1 ? $_LANG["vc_video_type_{$vc_video_type1}_data_descr"] : $_LANG["vc_video_type_none_data_descr"],
      "vc_video_type_data_descr2" => $vc_video_type2 ? $_LANG["vc_video_type_{$vc_video_type2}_data_descr"] : $_LANG["vc_video_type_none_data_descr"],
      "vc_video_type_data_descr3" => $vc_video_type3 ? $_LANG["vc_video_type_{$vc_video_type3}_data_descr"] : $_LANG["vc_video_type_none_data_descr"],
      "vc_video_type1" => $vc_video_type1 ? $vc_video_type1 : "none",
      "vc_video_type2" => $vc_video_type2 ? $vc_video_type2 : "none",
      "vc_video_type3" => $vc_video_type3 ? $vc_video_type3 : "none",
      "vc_video_data1" => $vc_video_data1,
      "vc_video_data2" => $vc_video_data2,
      "vc_video_data3" => $vc_video_data3,
      "vc_video_url1" => $vc_video_url1,
      "vc_video_url2" => $vc_video_url2,
      "vc_video_url3" => $vc_video_url3,
      "vc_image_class1" => !$vc_showvideo1 ? "active" : "inactive",
      "vc_image_class2" => !$vc_showvideo2 ? "active" : "inactive",
      "vc_image_class3" => !$vc_showvideo3 ? "active" : "inactive",
      "vc_image_visibility1" => self::get_css_visibility(!$vc_showvideo1),
      "vc_image_visibility2" => self::get_css_visibility(!$vc_showvideo2),
      "vc_image_visibility3" => self::get_css_visibility(!$vc_showvideo3),
      "vc_save_parameter" => $this->vc_save_parameter,
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

    $vc_video_view1 = $vc_video_view2 = $vc_video_view3 = "";
    $this->tpl->set_tpl_dir("../templates");

    // Video 1
    $vc_video_type1 = isset($_POST["vc_video_type1"]) ? $_POST["vc_video_type1"] : "";
    $vc_video_data1 = isset($_POST["vc_video_data1"]) ? $_POST["vc_video_data1"] : "";
    $vc_video1 = $this->parse_video_data($vc_video_type1, $vc_video_data1);
    $vc_video1_tpl = 'content_types/ContentItem' . $this->_templateSuffix
                   . '_view_' . $vc_video_type1 . '.tpl';
    if ($vc_video1 && $this->tpl->load_tpl("content_site_vc_video1", $vc_video1_tpl)) {
      $vc_video_view1 = vsprintf($this->tpl->return_file("content_site_vc_video1"), $vc_video1);
    }

    // Video 2
    $vc_video_type2 = isset($_POST["vc_video_type2"]) ? $_POST["vc_video_type2"] : "";
    $vc_video_data2 = isset($_POST["vc_video_data2"]) ? $_POST["vc_video_data2"] : "";
    $vc_video2 = $this->parse_video_data($vc_video_type2, $vc_video_data2);
    $vc_video2_tpl = 'content_types/ContentItem' . $this->_templateSuffix
                   . '_view_' . $vc_video_type2 . '.tpl';
    if ($vc_video2 && $this->tpl->load_tpl("content_site_vc_video2", $vc_video2_tpl)) {
      $vc_video_view2 = vsprintf($this->tpl->return_file("content_site_vc_video2"), $vc_video2);
    }

    // Video 3
    $vc_video_type3 = isset($_POST["vc_video_type3"]) ? $_POST["vc_video_type3"] : "";
    $vc_video_data3 = isset($_POST["vc_video_data3"]) ? $_POST["vc_video_data3"] : "";
    $vc_video3 = $this->parse_video_data($vc_video_type3, $vc_video_data3);
    $vc_video3_tpl = 'content_types/ContentItem' . $this->_templateSuffix
                   . '_view_' . $vc_video_type3 . '.tpl';
    if ($vc_video3 && $this->tpl->load_tpl("content_site_vc_video3", $vc_video3_tpl)) {
      $vc_video_view3 = vsprintf($this->tpl->return_file("content_site_vc_video3"), $vc_video3);
    }

    $vc_image_titles = $post->readImageTitles('vc_image_title');
    $vc_image_titles = $this->explode_content_image_titles('c_vc', $vc_image_titles);

    $vc_images = $this->_createPreviewImages(array(
      'VImage1' => 'vc_image1',
      'VImage2' => 'vc_image2',
      'VImage3' => 'vc_image3',
    ));
    $vc_image_src1 = $vc_video_view1 ? '' : $vc_images['vc_image1'];
    $vc_image_src2 = $vc_video_view2 ? '' : $vc_images['vc_image2'];
    $vc_image_src3 = $vc_video_view3 ? '' : $vc_images['vc_image3'];
    $vc_image1_large = $vc_video_view1 ? "" : $this->_hasLargeImage($vc_image_src1);
    $vc_image2_large = $vc_video_view2 ? "" : $this->_hasLargeImage($vc_image_src2);
    $vc_image3_large = $vc_video_view3 ? "" : $this->_hasLargeImage($vc_image_src3);

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
      'm_metainfo_part' => $this->_getMetainfoPart('vc')
    ));
    $this->tpl->parse_if($tplName, 'video1', $vc_video_view1, array("c_vc_video_view1" => $vc_video_view1));
    $this->tpl->parse_if($tplName, 'video2', $vc_video_view2, array("c_vc_video_view2" => $vc_video_view2));
    $this->tpl->parse_if($tplName, 'video3', $vc_video_view3, array("c_vc_video_view3" => $vc_video_view3));
    $this->tpl->parse_if($tplName, 'zoom1', $vc_image1_large, array(
      'c_vc_zoom1_link' => '#',
    ));
    $this->tpl->parse_if($tplName, 'zoom2', $vc_image2_large, array(
      'c_vc_zoom2_link' => '#',
    ));
    $this->tpl->parse_if($tplName, 'zoom3', $vc_image3_large, array(
      'c_vc_zoom3_link' => '#',
    ));
    $this->tpl->parse_if($tplName, 'image1', $vc_image_src1, array( 'c_vc_image_src1' => $vc_image_src1 ));
    $this->tpl->parse_if($tplName, 'image2', $vc_image_src2, array( 'c_vc_image_src2' => $vc_image_src2 ));
    $this->tpl->parse_if($tplName, 'image3', $vc_image_src3, array( 'c_vc_image_src3' => $vc_image_src3 ));
    $this->tpl->parse_vars($tplName, array_merge($vc_image_titles, array(
      'c_vc_title1' => parseOutput($post->readString('vc_title1', Input::FILTER_CONTENT_TITLE), 2),
      'c_vc_title2' => parseOutput($post->readString('vc_title2', Input::FILTER_CONTENT_TITLE), 2),
      'c_vc_title3' => parseOutput($post->readString('vc_title3', Input::FILTER_CONTENT_TITLE), 2),
      'c_vc_text1' => parseOutput($post->readString('vc_text1', Input::FILTER_CONTENT_TEXT), 1),
      'c_vc_text2' => parseOutput($post->readString('vc_text2', Input::FILTER_CONTENT_TEXT), 1),
      'c_vc_text3' => parseOutput($post->readString('vc_text3', Input::FILTER_CONTENT_TEXT), 1),
      'c_surl' => "../",
      'm_print_part' => $this->get_print_part(),
    )));
    $vc_content = $this->tpl->parsereturn($tplName, $this->_getFrontentLang());
    $this->tpl->set_tpl_dir("./templates");
    return $vc_content;
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Return Content of all ContentItems                                                    //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function return_class_content()
  {
    $class_content = array();
    $result = $this->db->query(<<<SQL
SELECT FK_CTID, CIID, CIIdentifier, CTitle, VTitle1, VTitle2, VTitle3, VText1, VText2, VText3, VImageTitles
FROM {$this->table_prefix}contentitem_vc cic
LEFT JOIN {$this->table_prefix}contentitem ci ON ci.CIID = cic.FK_CIID
ORDER BY cic.FK_CIID ASC
SQL
    );
    while ($row = $this->db->fetch_row($result)) {
      $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
      $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
      $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
      $class_content[$row["CIID"]]["c_title1"] = $row["VTitle1"];
      $class_content[$row["CIID"]]["c_title2"] = $row["VTitle2"];
      $class_content[$row["CIID"]]["c_title3"] = $row["VTitle3"];
      $class_content[$row["CIID"]]["c_text1"] = $row["VText1"];
      $class_content[$row["CIID"]]["c_text2"] = $row["VText2"];
      $class_content[$row["CIID"]]["c_text3"] = $row["VText3"];
      $vc_image_titles = $this->explode_content_image_titles("vc", $row["VImageTitles"]);
      $class_content[$row["CIID"]]["c_image_title1"] = $vc_image_titles["vc_image1_title"];
      $class_content[$row["CIID"]]["c_image_title2"] = $vc_image_titles["vc_image2_title"];
      $class_content[$row["CIID"]]["c_image_title3"] = $vc_image_titles["vc_image3_title"];
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
         . '        VVideoType1, VVideo1, VVideoType2, VVideo2, VVideoType3, VVideo3 '
         .( $sqlArgs ? ', '.implode(',', $sqlArgs) : '' )
         .( $this->_contentImageTitles ? ', '.$this->_columnPrefix.'ImageTitles' : '' )
         . " FROM {$this->table_prefix}contentitem ci "
         . " JOIN {$this->table_prefix}contentitem_{$this->_contentPrefix} ci_sub "
         . '      ON CIID = ci_sub.FK_CIID '
         . " WHERE CIID = $this->page_id ";
    return $this->db->GetRow($sql);
  }
}

