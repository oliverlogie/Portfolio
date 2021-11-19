<?php

/**
 * Compendium areas contenttype class
 *
 * $LastChangedDate: 2019-05-10 11:58:32 +0200 (Fr, 10 Mai 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */
class ContentItemCA extends ContentItem
{
  protected $_configPrefix = 'ca';
  protected $_contentPrefix = 'ca';
  protected $_columnPrefix = 'CA';
  protected $_contentElements = array(
    'Title' => 1,
    'Text' => 3,
    'Image' => 3,
    'Link' => 1,
  );
  protected $_templateSuffix = 'CA';

  /**
   * Determines if content has changed and thus spidering is necessary.
   *
   * @return bool
   *         True if content was changed, false otherwise.
   */
  protected static function hasContentChanged()
  {
    // The main content has changed.
    if (parent::hasContentChanged()) {
      return true;
    }

    // An area has been changed
    if (   isset($_POST['process_ca_area'])
        || isset($_GET['deleteAreaID'])
    ) {
      return true;
    }

    // An area box has been changed
    if (   isset($_POST['process_ca_area_box'])
        || isset($_GET['deleteBoxID'])
    ) {
      return true;
    }

    // Nothing has changed.
    return false;
  }

  public function delete_content()
  {
    $this->_subelements->delete_content();
    return parent::delete_content();
  }

  public function edit_content()
  {
    global $_LANG, $_LANG2;

    if ($this->_subelements->isProcessed()) {
      $this->_subelements->edit_content();
    }
    else {
      parent::edit_content();

      // Read link for main content, as links are not read within the
      // ContentItem::edit_content() method.
      $input['Link'] = $this->_readContentElementsLinks();

      $sql = " UPDATE {$this->table_prefix}contentitem_ca "
           . " SET CALink = " . $input['Link']['CALink']
           . " WHERE FK_CIID = $this->page_id ";
      $result = $this->db->query($sql);
    }
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2;

    $row = $this->_getData();

    $invalidLinks = 0;
    $internalLink = $this->getInternalLinkHelper($row['CALink']);
    if ($internalLink->isInvalid()) {
      $invalidLinks++;
    }

    $areasContent = $this->_subelements[0]->get_content();
    $areasItems = $areasContent['content'];
    if ($areasContent['message']) {
      $this->setMessage($areasContent['message']);
    }
    $invalidLinks += $areasContent['invalidLinks'];

    if ($invalidLinks) {
      $this->setMessage(Message::createFailure(sprintf($_LANG['ca_message_invalid_links'], $invalidLinks)));
    }

    $ca_hidden_fields = '<input type="hidden" name="site" value="'.$this->site_id.'" />'
                      . '<input type="hidden" name="page" value="'.$this->page_id.'" />'
                      . '<input type="hidden" name="action" value="content" />'
                      . '<input type="hidden" name="action2" value="" />'
                      . '<input type="hidden" id="area" name="area" value="0" />'
                      . '<input type="hidden" id="box" name="box" value="0" />'
                      . '<input type="hidden" id="scrollToAnchor" name="scrollToAnchor" value="" />';

    $request = new Input(Input::SOURCE_REQUEST);
    $ca_scroll_to_anchor = $request->readString('scrollToAnchor');

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $ca_content = $this->tpl->parsereturn($tplName, array_merge(
      $internalLink->getTemplateVars('ca'), array(
      'ca_areas' => $areasItems,
      'ca_site' => $this->site_id,
      'ca_hidden_fields' => $ca_hidden_fields,
      'ca_autocomplete_contentitem_url' => "index.php?action=response&site=$this->site_id&page=$this->page_id&request=ContentItemAutoComplete&excludeContentItems=$this->page_id",
      'ca_scroll_to_anchor' => $ca_scroll_to_anchor,
    )));

    $settings = array(
      'no_preview' => true,
      'tpl' => $tplName,
    );

    return parent::get_content(array_merge($params, array(
      'row' => $row,
      'settings' => $settings,
    )));
  }

  public function return_class_content()
  {
    $classContent = array();
    $sql = " SELECT FK_CTID, CIID, CIIdentifier, CTitle, "
         . "        {$this->_columnPrefix}Title, "
         . "        {$this->_columnPrefix}Text1, {$this->_columnPrefix}Text2, {$this->_columnPrefix}Text3, "
         . "        {$this->_columnPrefix}ImageTitles "
         . " FROM {$this->table_prefix}contentitem_ca cica "
         . " LEFT JOIN {$this->table_prefix}contentitem ci "
         . '   ON cica.FK_CIID = ci.CIID '
         . ' ORDER BY cica.FK_CIID ASC ';

    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result))
    {
      $classContent[$row['CIID']]['path'] = $row['CIIdentifier'];
      $classContent[$row['CIID']]['path_title'] = $row['CTitle'];
      $classContent[$row['CIID']]['type'] = $row['FK_CTID'];
      $classContent[$row['CIID']]['c_title1'] = $row[$this->_columnPrefix.'Title'];
      $classContent[$row['CIID']]['c_title2'] = '';
      $classContent[$row['CIID']]['c_title3'] = '';
      $classContent[$row['CIID']]['c_text1'] = $row[$this->_columnPrefix.'Text1'];
      $classContent[$row['CIID']]['c_text2'] = $row[$this->_columnPrefix.'Text2'];
      $classContent[$row['CIID']]['c_text3'] = $row[$this->_columnPrefix.'Text3'];
      $imageTitles = $this->explode_content_image_titles('ca', $row[$this->_columnPrefix.'ImageTitles']);
      $classContent[$row['CIID']]['c_image_title1'] = $imageTitles['ca_image1_title'];
      $classContent[$row['CIID']]['c_image_title2'] = $imageTitles['ca_image2_title'];
      $classContent[$row['CIID']]['c_image_title3'] = $imageTitles['ca_image3_title'];
      $classContent[$row['CIID']]['c_sub'] = array();

      $sql = " SELECT CAATitle, CAAText, CAAID "
           . " FROM {$this->table_prefix}contentitem_ca_area "
           . " WHERE FK_CIID = {$row['CIID']} "
           . ' ORDER BY CAAPosition ASC ';
      $resultSub = $this->db->query($sql);
      while ($rowSub = $this->db->fetch_row($resultSub))
      {
        $classContent[$row['CIID']]['c_sub'][] = array(
          'cs_title' => $rowSub['CAATitle'],
          'cs_text'  => $rowSub['CAAText'],
        );

        $sql = " SELECT CAABTitle, CAABText "
           . " FROM {$this->table_prefix}contentitem_ca_area_box "
           . " WHERE FK_CAAID = {$rowSub['CAAID']} "
           . ' ORDER BY CAABPosition ASC ';
        $resultSub1 = $this->db->query($sql);

        while ($rowSub1 = $this->db->fetch_row($resultSub1))
        {
          $classContent[$row['CIID']]['c_sub'][] = array(
            'cs_title' => $rowSub1['CAABTitle'],
            'cs_text' => $rowSub1['CAABText'],
          );
        }
      }
      $this->db->free_result($resultSub);
    }
    $this->db->free_result($result);

    return $classContent;
  }

  public function _getData()
  {
    // Create database entries.
    $this->_checkDataBase();

    foreach ($this->_contentElements as $type => $count) {
      for ($i = 1; $i <= $count; $i++) {
        $sqlArgs[] = $this->_getContentElementColumnName($type, $count, $i);
      }
    }

    $sql = " SELECT c_link.FK_SID AS Link_FK_SID, c_link.CIID AS Link_CIID,"
         . "        c_link.CIIdentifier AS Link_CIIdentifier, "
         . "        " . implode(', ', $this->_dataFields)
         .( $sqlArgs ? ', '.implode(',', $sqlArgs) : '' )
         .( $this->_contentImageTitles ? ', '.$this->_columnPrefix.'ImageTitles' : '' )
         . " FROM {$this->table_prefix}contentitem ci "
         . " JOIN {$this->table_prefix}contentitem_{$this->_contentPrefix} ci_sub "
         . "      ON ci.CIID = ci_sub.FK_CIID "
         . " LEFT JOIN {$this->table_prefix}contentitem c_link "
         . '      ON CALink = c_link.CIID '
         . " WHERE ci_sub.FK_CIID = $this->page_id ";
    return $this->db->GetRow($sql);
  }

  public function getTexts($subcontent = true)
  {
    $texts = parent::getTexts();

    if ($subcontent)
    {
      $sql = 'SELECT CAAText AS Text '
           . "FROM {$this->table_prefix}contentitem_ca_area "
           . "WHERE FK_CIID = $this->page_id "
           . "AND (COALESCE(CAAText, '') != '') "
           . 'UNION ALL '
           . 'SELECT CAABText AS Text '
           . "FROM {$this->table_prefix}contentitem_ca_area_box "
           . "JOIN {$this->table_prefix}contentitem_ca_area ON FK_CAAID = CAAID "
           . "WHERE FK_CIID = $this->page_id "
           . "AND (COALESCE(CAABText, '') != '') ";
      $texts = array_merge($texts ,$this->db->GetCol($sql));
    }

    return $texts;
  }

  protected function _readSubElements()
  {
    parent::_readSubElements();

    $this->_subelements[0] = new ContentItemCA_Areas($this->site_id,
        $this->page_id, $this->tpl, $this->db, $this->table_prefix, '', '',
        $this->_user, $this->session, $this->_navigation, $this);
  }
}
