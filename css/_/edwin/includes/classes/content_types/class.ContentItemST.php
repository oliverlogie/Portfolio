<?php

/**
 * Content Class
 *
 * Display the sitemap of the main navigation tree.
 *
 * $LastChangedDate: 2017-10-12 14:15:08 +0200 (Do, 12 Okt 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2010 Q2E GmbH
 */
class ContentItemST extends ContentItem
{
  protected $_configPrefix = 'st';
  protected $_contentPrefix = 'st';
  protected $_columnPrefix = 'ST';
  protected $_contentElements = array(
    'Title' => 3,
    'Text' => 3,
    'Image' => 3,
  );
  protected $_templateSuffix = 'ST';

  /**
   * Preview
   */
  public function preview()
  {
    $post = new Input(Input::SOURCE_POST);

    $image_titles = $post->readImageTitles('st_image_title');
    $image_titles = $this->explode_content_image_titles("c_st",$image_titles);

    $images = $this->_createPreviewImages(array(
      'STImage1' => 'st_image1',
      'STImage2' => 'st_image2',
      'STImage3' => 'st_image3',
    ));
    $image_src1 = $images['st_image1'];
    $image_src2 = $images['st_image2'];
    $image_src3 = $images['st_image3'];
    $image_src_large1 = $this->_hasLargeImage($image_src1);
    $image_src_large2 = $this->_hasLargeImage($image_src2);
    $image_src_large3 = $this->_hasLargeImage($image_src3);

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->set_tpl_dir("../templates");
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
      'm_metainfo_part' => $this->_getMetainfoPart('st')
    ));
    $this->tpl->parse_if($tplName, 'zoom1', $image_src_large1, array(
      'c_st_zoom1_link' => '#',
    ));
    $this->tpl->parse_if($tplName, 'zoom2', $image_src_large2, array(
      'c_st_zoom2_link' => '#',
    ));
    $this->tpl->parse_if($tplName, 'zoom3', $image_src_large3, array(
      'c_st_zoom3_link' => '#',
    ));
    $this->tpl->parse_if($tplName, 'image1', $image_src1, array( 'c_st_image_src1' => $image_src1 ));
    $this->tpl->parse_if($tplName, 'image2', $image_src2, array( 'c_st_image_src2' => $image_src2 ));
    $this->tpl->parse_if($tplName, 'image3', $image_src3, array( 'c_st_image_src3' => $image_src3 ));
    $this->tpl->parse_vars($tplName, array_merge( $image_titles, array (
      'c_st_title1' => parseOutput($post->readString('st_title1', Input::FILTER_CONTENT_TITLE),2),
      'c_st_title2' => parseOutput($post->readString('st_title2', Input::FILTER_CONTENT_TITLE),2),
      'c_st_title3' => parseOutput($post->readString('st_title3', Input::FILTER_CONTENT_TITLE),2),
      'c_st_text1' => parseOutput($post->readString('st_text1', Input::FILTER_CONTENT_TEXT), 1),
      'c_st_text2' => parseOutput($post->readString('st_text2', Input::FILTER_CONTENT_TEXT), 1),
      'c_st_text3' => parseOutput($post->readString('st_text3', Input::FILTER_CONTENT_TEXT), 1),
      'c_st_image_src1' => $image_src1,
      'c_st_image_src2' => $image_src2,
      'c_st_image_src3' => $image_src3,
      'c_st_sitemap' => '',
      'c_surl' => "../",
      'm_print_part' => $this->get_print_part(),
    )));
    $content = $this->tpl->parsereturn($tplName, $this->_getFrontentLang());
    $this->tpl->set_tpl_dir("./templates");
    return $content;
  }

  /**
   * Return content of all content items.
   */
  public function return_class_content()
  {
    $class_content = array();
    $sql = ' SELECT FK_CTID, CIID, CIIdentifier, CTitle, '
         . '        STTitle1, STTitle2, STTitle3, STText1, STText2, STText3, '
         . '        STImageTitles '
         . " FROM {$this->table_prefix}contentitem_st st "
         . " JOIN {$this->table_prefix}contentitem ci "
         . '      ON ci.CIID = st.FK_CIID '
         . ' ORDER BY st.FK_CIID ASC';
    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result))
    {
      $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
      $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
      $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
      $class_content[$row["CIID"]]["c_title1"] = $row["STTitle1"];
      $class_content[$row["CIID"]]["c_title2"] = $row["STTitle2"];
      $class_content[$row["CIID"]]["c_title3"] = $row["STTitle3"];
      $class_content[$row["CIID"]]["c_text1"] = $row["STText1"];
      $class_content[$row["CIID"]]["c_text2"] = $row["STText2"];
      $class_content[$row["CIID"]]["c_text3"] = $row["STText3"];
      $image_titles = $this->explode_content_image_titles("st",$row["STImageTitles"]);
      $class_content[$row["CIID"]]["c_image_title1"] = $image_titles["st_image1_title"];
      $class_content[$row["CIID"]]["c_image_title2"] = $image_titles["st_image2_title"];
      $class_content[$row["CIID"]]["c_image_title3"] = $image_titles["st_image3_title"];
      $class_content[$row["CIID"]]["c_sub"] = array();
    }
    $this->db->free_result($result);

    return $class_content;
  }
}

