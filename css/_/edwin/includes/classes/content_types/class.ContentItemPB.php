<?php

/**
 * ContentItem class for level displaying ContentItemPP data within its overview
 * page.
 *
 * $LastChangedDate: 2012-09-12 13:56:03 +0200 (Mi, 12 Sep 2012) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2012 Q2E GmbH
 */
class ContentItemPB extends ContentItemLogical
{
  protected $_configPrefix = 'pb';
  protected $_contentPrefix = 'pb';
  protected $_columnPrefix = 'PB';
  protected $_contentElements = array(
    'Title' => 1,
    'Text'  => 1,
    'Image' => 1,
  );
  protected $_templateSuffix = 'PB';

  public function return_class_content()
  {
    $class_content = array();
    $sql = "SELECT FK_CTID, CIID, CIIdentifier, CTitle, PBTitle, PBText "
         . " FROM {$this->table_prefix}contentitem_pb cic "
         . " LEFT JOIN {$this->table_prefix}contentitem ci "
         . " ON ci.CIID = cic.FK_CIID "
         . " ORDER BY cic.FK_CIID ASC ";
    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result)){
      if ($row['CIID']){
        $class_content[$row['CIID']]['path'] = $row['CIIdentifier'];
        $class_content[$row['CIID']]['path_title'] = $row['CTitle'];
        $class_content[$row['CIID']]['type'] = $row['FK_CTID'];
        $class_content[$row['CIID']]['c_title1'] = $row['PBTitle'];
        $class_content[$row['CIID']]['c_title2'] = '';
        $class_content[$row['CIID']]['c_title3'] = '';
        $class_content[$row['CIID']]['c_text1'] = $row['PBText'];
        $class_content[$row['CIID']]['c_text2'] = '';
        $class_content[$row['CIID']]['c_text3'] = '';
        $class_content[$row['CIID']]['c_image_title1'] = '';
        $class_content[$row['CIID']]['c_image_title2'] = '';
        $class_content[$row['CIID']]['c_image_title3'] = '';
        $class_content[$row['CIID']]['c_sub'] = array();
      }
    }
    $this->db->free_result($result);

    return $class_content;
  }
}
