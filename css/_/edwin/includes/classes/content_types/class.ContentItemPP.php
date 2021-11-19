<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2018-02-22 16:13:41 +0100 (Do, 22 Feb 2018) $
 * $LastChangedBy: ham $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */
class ContentItemPP extends ContentItem
{
  protected $_configPrefix = 'pp';
  protected $_contentPrefix = 'pp';
  protected $_columnPrefix = 'PP';
  protected $_contentElements = array(
    'Title' => 3,
    'Text' => 3,
    'Image' => 8,
  );
  protected $_templateSuffix = 'PP';

  /**
   * @var int
   */
  private $_numberOfAvailableOptions;

  /**
   * Updates the PP, setting its cheapest product
   *
   * @param Db $db
   * @param string $tablePrefix
   * @param int $pageId
   *        The page id of ContentItemPP to update data for
   *
   * @return void
   */
  public  static function updateCheapestProduct(Db $db, $tablePrefix, $pageId)
  {
    $pageId = (int)$pageId;
    if (!$pageId) {
      throw InvalidArgumentException("Invalid 'id' for ContentItemPP.");
    }

    $sql = " SELECT PPPID, PPPPrice, PPPrice "
         . " FROM {$tablePrefix}contentitem_pp_product pp "
         . " JOIN {$tablePrefix}contentitem_pp p "
         . " ON p.FK_CIID = pp.FK_CIID "
         . " WHERE p.FK_CIID = $pageId "
         . "  AND pp.PPPDisabled = 0 "
         . " ORDER BY PPPPosition ASC ";
    $result = $db->query($sql);

    // initialize
    $row = $db->fetch_row($result);
    $product = (int)$row['PPPID'];
    $price   = (float)$row['PPPPrice'] ? (float)$row['PPPPrice'] :
               (float)$row['PPPrice'];

    while ($row = $db->fetch_row($result)) {
      $tmpProduct = (int)$row['PPPID'];
      $tmpPrice = (float)$row['PPPPrice'] ? (float)$row['PPPPrice'] :
                  (float)$row['PPPrice'];

      if ($tmpPrice < $price) {
        $price = $tmpPrice;
        $product = $tmpProduct;
      }
    }

    $db->free_result($result);

    $sql = " UPDATE {$tablePrefix}contentitem_pp "
         . " SET FK_PPPID_Cheapest = '$product' "
         . " WHERE FK_CIID = $pageId ";
    $db->query($sql);
  }

  /**
   * Determines if content has changed and thus spidering is necessary.
   *
   * @return bool
   *         True if content was changed, false otherwise.
   */
  protected static function hasContentChanged()
  {
    // The main content has changed.
    if (parent::hasContentChanged())
      return true;

    // A product has changed.
    if (isset($_POST['process_pp_product']) || isset($_GET['deleteProductID']))
      return true;

    // Nothing has changed.
    return false;
  }

  //////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                           //
  //////////////////////////////////////////////////////////////////////////////
  public function delete_content()
  {
    // Delete all items
    $items = new ContentItemPP_Products($this->site_id, $this->page_id,
                        $this->tpl, $this->db, $this->table_prefix,
                        $this->_user, $this->session, $this->_navigation, $this);
    $items->delete_content();

    $sql = " DELETE FROM {$this->table_prefix}contentitem_pp_attribute_global "
         . " WHERE FK_CIID = $this->page_id ";
    $this->db->query($sql);

    $sql = " DELETE FROM {$this->table_prefix}contentitem_pp_option "
         . " WHERE FK_CIID = $this->page_id ";
    $this->db->query($sql);

    $sql = " DELETE FROM {$this->table_prefix}contentitem_pp_cartsetting "
         . " WHERE FK_CIID = $this->page_id ";
    $this->db->query($sql);

    // Call the default delete content method.
    return parent::delete_content();
  }

  public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
  {
    // Duplicate content item
    $parentId = parent::duplicateContent($pageId);

    // Duplicate attributes
    $sql = " INSERT INTO {$this->table_prefix}contentitem_pp_attribute_global (FK_CIID, FK_AID, PPAPosition) "
         . "  SELECT {$pageId}, cippag.FK_AID, cippag.PPAPosition "
         . "    FROM {$this->table_prefix}contentitem_pp_attribute_global AS cippag "
         . "   WHERE cippag.FK_CIID = {$this->page_id} ";
    $this->db->query($sql);

    return $parentId;
  }

  //////////////////////////////////////////////////////////////////////////////
  // Edit Content                                                             //
  //////////////////////////////////////////////////////////////////////////////
  public function edit_content()
  {
    global $_LANG;

    if ($this->_subelements->isProcessed()) {
      $this->_subelements->edit_content();
    }
    else {
      $this->_editContent();
    }

    self::updateCheapestProduct($this->db, $this->table_prefix, $this->page_id);
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2;

    $data = $this->_getData();

    $pp_products = $this->_getSubelementByClassName('ContentItemPP_Products');
    $pp_products_content = $pp_products->get_content();
    $pp_product_items = $pp_products_content['content'];
    $pp_product_count = $pp_products_content['count'];
    if ($pp_products_content['message'])
      $this->setMessage($pp_products_content['message']);

    $ppOptions = $this->_getSubelementByClassName('ContentItemPP_Options');
    $ppOptionsContent = $ppOptions->get_content();
    $ppOptionsItems = $ppOptionsContent['content'];
    if ($ppOptionsContent['message'])
      $this->setMessage($ppOptionsContent['message']);

    // create attribute type select boxes //////////////////////////////////////
    // attribute types are changeable, if there have not been stored any
    // products
    $changeable = $pp_product_count < 1;
    // products can not be created if there have not been attribute types
    // selected for ContentItemPP
    $productsAvailable = false;
    $attrSelects = array();

    $sql = " SELECT PPAPosition, FK_AID, ATitle, AText, APosition "
         . " FROM {$this->table_prefix}contentitem_pp_attribute_global "
         . " LEFT JOIN {$this->table_prefix}module_attribute_global "
         . "   ON FK_AID = AID "
         . " WHERE FK_CIID = $this->page_id "
         . " ORDER BY PPAPosition ASC ";
    $selected = $this->db->GetAssoc($sql);

    $sql = " SELECT AID, ATitle, AText, APosition "
         . " FROM {$this->table_prefix}module_attribute_global "
         . " WHERE FK_SID = $this->site_id "
         . "   AND FK_CTID = 42 "
         . " ORDER BY APosition ASC ";
    $types = $this->db->GetAssoc($sql);

    foreach ($selected as $pos => $val1) {
      // products can be created
      if ($val1['FK_AID'] != 0)
        $productsAvailable = true;

      $tmpSelect = '<div class="form-group"><select name="pp_attribute_global['.$pos.']" class="form-control">';

      if (!$changeable && $val1['FK_AID'] == 0)
        continue;
      // "no-attribute" only if changeable
      else if ($changeable) {
        $tmpSelect .= '<option value="0" '
                   . ( $val1['FK_AID'] == 0 ? 'selected="selected"' : '')
                   . '>' . $_LANG['pp_attribute_global_title_none'] . '</option>';
      }

      foreach ($types as $key => $val2) {
        // output all possible attribute types
        if ($changeable) {
          $tmpSelect .= '<option value="' . $key . '" '
                     . ( $key == $val1['FK_AID'] ? 'selected="selected"' : '')
                     . '>' . parseOutput($val2['ATitle']) . '</option>';
        }
        // output selected attribute type only ( not changeable any more )
        else if ($key == $val1['FK_AID'])
        {
          $tmpSelect .= '<option value="' . $key . '" selected="selected"'
                     . '>' . parseOutput($val2['ATitle']) . '</option>';
        }
      }
      $tmpSelect .= '</select></div>';
      $attrSelects[] = array(
        'pp_attr_global_select' => $tmpSelect
      );
    }
    ////////////////////////////////////////////////////////////////////////////

    // get cart settings ///////////////////////////////////////////////////////
    $sql = " SELECT CPCID, CPCTitle, CPCPrice, FK_CIID "
         . " FROM {$this->table_prefix}contentitem_cp_cartsetting "
         . " LEFT JOIN {$this->table_prefix}contentitem_pp_cartsetting "
         . "   ON FK_CPCID = CPCID AND FK_CIID = $this->page_id "
         . " WHERE FK_SID = $this->site_id "
         . " ORDER BY CPCPosition ASC ";
    $result = $this->db->query($sql);

    $settingItems = array();
    while ($row = $this->db->fetch_row($result)) {
      $settingItems[] = array(
        'pp_setting_checked' => $row['FK_CIID'] ? 'checked="checked"' : '',
        'pp_setting_id' => $row['CPCID'],
        'pp_setting_price' => parseOutput($row['CPCPrice'], 99),
        'pp_setting_title' => $row['CPCTitle'],
      );
    }
    $this->db->free_result($result);
    ////////////////////////////////////////////////////////////////////////////

    $taxRates = $this->getTaxRatesTemplateVars((int)$data['PPTaxRate']);

    // get CP shipping costs percentage configuration value ////////////////////
    $sql = " SELECT CPPValue "
         . "   FROM {$this->table_prefix}contentitem_cp_preferences "
         . " WHERE FK_SID = $this->site_id "
         . "   AND CPPNAme = 'cp_shipping_costs_percentage' ";
    $percentage = (int)$this->db->GetOne($sql);
    ////////////////////////////////////////////////////////////////////////////

    $pp_hidden_fields = '<input type="hidden" name="site" value="'.$this->site_id.'" />'
                      . '<input type="hidden" name="page" value="'.$this->page_id.'" />'
                      . '<input type="hidden" name="action" value="content" />'
                      . '<input type="hidden" name="action2" value="" />'
                      . '<input type="hidden" name="product" class="jq_product" value="0" />'
                      . '<input type="hidden" name="scrollToAnchor" class="jq_scrollToAnchor" value="" />'
                      . '<input type="hidden" name="p" value="' . $this->_getCurrentPaginationPage() . '" />';

    $pp_scroll_to_anchor = isset($_REQUEST['scrollToAnchor']) ? $_REQUEST['scrollToAnchor'] : '';

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_loop($tplName, $attrSelects, 'attribute_types');
    $this->tpl->parse_loop($tplName, $settingItems, 'setting_items');
    $this->tpl->parse_if($tplName, 'tax_rates', $taxRates);
    $this->tpl->parse_loop($tplName, $taxRates, 'tax_rate_items');
    $this->tpl->parse_if($tplName, 'products_available', $productsAvailable, array('pp_products' => $pp_product_items));
    $this->tpl->parse_if($tplName, 'options_available',
        $this->_getNumberOfAvailableOptions(), array('pp_options' => $ppOptionsItems));

    $this->tpl->parse_vars($tplName, array(
      'pp_shipping_costs_percentage' => $percentage,
      'pp_price'                   => $data['PPPrice'] ? parseOutput($data['PPPrice'],99) : '',
      'pp_currency'                => ConfigHelper::get('site_currencies', '', $this->site_id),
      'pp_case_packs'              => (int)$data['PPCasePacks'],
      'pp_shipping_costs'          => $data['PPShippingCosts'] ? parseOutput($data['PPShippingCosts'],99) : '',
      'pp_hidden_fields'           => $pp_hidden_fields,
      'pp_scroll_to_anchor'        => $pp_scroll_to_anchor,
      'pp_main_content_changed'    => parent::hasContentChanged(),
      'pp_autocomplete_option_url' => "index.php?action=response&site={$this->site_id}&page={$this->page_id}&request=OptionAutoComplete",
     ));

    return parent::get_content(array_merge($params, array(
      'row'      => $data,
      'settings' => array(
        'tpl' => $tplName,
        'no_preview' => true
      ),
    )));
  }

  public function getBrokenTextLinks($text = null)
  {
    if ($text) return parent::getBrokenTextLinks($text);

    $broken = parent::getBrokenTextLinks();

    $sql = " SELECT PPPID, PPPText, PPPPosition "
         . " FROM {$this->table_prefix}contentitem_pp_product "
         . " WHERE FK_CIID = $this->page_id ";
    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result))
    {
      $bls = parent::getBrokenTextLinks($row['PPPText']);
      // foreach broken link found modify the page link and add to broken links array
      foreach ($bls as $bl)
      {
        $bl['link'] .= "&amp;product={$row['PPPID']}&amp;scrollToAnchor=a_product{$row['PPPPosition']}";
        $broken[] = $bl;
      }
    }
    $this->db->free_result($result);
    return $broken;
  }

  /**
   * Return all image titles.
   *
   * @param bool $subcontent [optional] [default : true]
   *        If subcontent is false, image titles from subcontent will not be
   *        retrieved.
   *
   * @return array
   *         An array containing all image titles stored for this content
   *         item (and subcontent)
   */
  public function getImageTitles($subcontent = true)
  {
    // Get the image titles for the QS contentitem itself.
    $titles = parent::getImageTitles();

    // Ensure that this part is not executed for ContentItemPP_Products or other
    // subclasses in case the $subcontent parameter is true.
    if ($subcontent === true && $this instanceof ContentItemPP)
    {
      $stmts = new ContentItemPP_Products($this->site_id, $this->page_id,
                     $this->tpl, $this->db, $this->table_prefix,
                     $this->_user, $this->session, $this->_navigation, $this);
      $tmpTitles = $stmts->getImageTitles(false);

      $titles = array_merge($titles, $tmpTitles);
    }

    return $titles;
  }

  public function getTexts($subcontent = true)
  {
    $texts = parent::getTexts();

    if ($subcontent)
    {
      $sql = " SELECT PPPText "
           . " FROM {$this->table_prefix}contentitem_pp_product "
           . " WHERE FK_CIID = $this->page_id "
           . " AND (COALESCE(PPPText, '') != '') ";
      $texts = array_merge($texts, $this->db->GetCol($sql));
    }

    return $texts;
  }

  //////////////////////////////////////////////////////////////////////////////
  // Preview Content                                                          //
  //////////////////////////////////////////////////////////////////////////////
  public function preview() {}

  //////////////////////////////////////////////////////////////////////////////
  // Return content of all ContentItems                                       //
  //////////////////////////////////////////////////////////////////////////////
  public function return_class_content()
  {
    $class_content = array();
    $sql = " SELECT FK_CTID, CIID, CIIdentifier, CTitle, "
         . "        PPTitle1, PPTitle2, PPTitle3, PPText1, PPText2, PPText3, "
         . "        PPImageTitles "
         . " FROM {$this->table_prefix}contentitem_pp c_pp "
         . " LEFT JOIN {$this->table_prefix}contentitem c "
         . "        ON c_pp.FK_CIID = c.CIID "
         . " ORDER BY c_pp.FK_CIID ASC ";
    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result)) {
      $class_content[$row['CIID']]['path'] = $row['CIIdentifier'];
      $class_content[$row['CIID']]['path_title'] = $row['CTitle'];
      $class_content[$row['CIID']]['type'] = $row['FK_CTID'];
      $class_content[$row['CIID']]['c_title1'] = $row['PPTitle1'];
      $class_content[$row['CIID']]['c_title2'] = $row['PPTitle2'];
      $class_content[$row['CIID']]['c_title3'] = $row['PPTitle3'];
      $class_content[$row['CIID']]['c_text1'] = $row['PPText1'];
      $class_content[$row['CIID']]['c_text2'] = $row['PPText2'];
      $class_content[$row['CIID']]['c_text3'] = $row['PPText3'];
      $pp_image_titles = $this->explode_content_image_titles('pp', $row['PPImageTitles']);
      $class_content[$row['CIID']]['c_image_title1'] = $pp_image_titles['pp_image1_title'];
      $class_content[$row['CIID']]['c_image_title2'] = $pp_image_titles['pp_image2_title'];
      $class_content[$row['CIID']]['c_image_title3'] = $pp_image_titles['pp_image3_title'];
      $class_content[$row['CIID']]['c_sub'] = array();

      $sql_sub = " SELECT PPPTitle, PPPText, PPPImageTitles "
               . " FROM {$this->table_prefix}contentitem_pp_product "
               . " WHERE FK_CIID = {$row['CIID']} "
               . " ORDER BY PPPPosition ASC ";
      $result_sub = $this->db->query($sql_sub);
      while ($row_sub = $this->db->fetch_row($result_sub))
      {
        $pp_image_titles_sub = $this->explode_content_image_titles('pp', $row_sub['PPPImageTitles']);
        $class_content[$row['CIID']]['c_sub'][] = array(
          'cs_title' => $row_sub['PPPTitle'],
          'cs_text' => $row_sub['PPPText'],
          'c_image_title1' => $pp_image_titles_sub['pp_image1_title'],
        );
      }
      $this->db->free_result($result_sub);
    }
    $this->db->free_result($result);

    return $class_content;
  }

  public function sendResponse($request)
  {
    switch($request) {
      case 'OptionAutoComplete' : $this->_sendResponseOptionAutoComplete();
    }

    return parent::sendResponse($request);
  }

  /**
   * @param int $selectedTaxRate
   * @return array
   */
  public function getTaxRatesTemplateVars($selectedTaxRate)
  {
    global $_LANG;

    $rates = array();
    foreach (ConfigHelper::get('cp_tax_rates') as $id => $percentage) {
      $rates[] = array(
        'pp_tax_rate_selected'   => $selectedTaxRate == $id ? 'selected="selected"' : '',
        'pp_tax_rate_id'         => $id,
        'pp_tax_rate_percentage' => parseOutput($percentage),
        'pp_tax_rate_title'      => $_LANG['pp_tax_rate_shortname'][$id],
      );
    }
    return $rates;
  }

  /**
   * @return array
   */
  public function getTaxRates()
  {
    return ConfigHelper::get('cp_tax_rates');
  }

  protected function _checkDataBase()
  {
    parent::_checkDatabase();

    // check amount of global attributes defined for ContentItemPP
    $sql = " SELECT COUNT(FK_CIID) "
         . " FROM {$this->table_prefix}contentitem_pp_attribute_global "
         . " WHERE FK_CIID = $this->page_id ";
    $count = $this->db->GetOne($sql);

    // Add missing entries
    $numberOfAttributes = (int)$this->getConfig('number_of_attributes');
    if ($count < $numberOfAttributes) {
      $sql = " INSERT INTO {$this->table_prefix}contentitem_pp_attribute_global "
           . " (FK_CIID, FK_AID, PPAPosition) VALUES ";
      while ($count < $numberOfAttributes) {
        $count++;
        $sqlPart[] = "($this->page_id, 0, $count)";
      }
      $sql .= implode(',', $sqlPart);
      $this->db->query($sql);
    }
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
         . '        PPPrice, PPCasePacks, PPShippingCosts, PPTaxRate '
         .( $sqlArgs ? ', '.implode(',', $sqlArgs) : '' )
         .( $this->_contentImageTitles ? ', '.$this->_columnPrefix.'ImageTitles' : '' )
         . " FROM {$this->table_prefix}contentitem ci "
         . " JOIN {$this->table_prefix}contentitem_{$this->_contentPrefix} ci_sub "
         . '      ON CIID = ci_sub.FK_CIID '
         . " WHERE CIID = $this->page_id ";
    return $this->db->GetRow($sql);
  }

  protected function _hasContent()
  {
    global $_LANG;

    $sql = " SELECT COUNT(PPPID) "
         . " FROM {$this->table_prefix}contentitem_pp_product "
         . " WHERE FK_CIID = $this->page_id "
         . "   AND PPPDisabled = 0 ";
    if ((int)$this->db->GetOne($sql) > 0) {
      return true;
    }
    // Allow to put that content item online if content
    // is received from a linked content item.
// Enable this if structure link module also duplicates products...
//    else if ($this->_readStructureLinkContentItemId()) {
//      return true;
//    }
    else {
      $this->setMessage(Message::createFailure($_LANG['pp_message_no_product']));
      return false;
    }
  }

  /**
   * Returns all title elements within this content item (or subcontent)
   * @return array
   *          an array containing all titles stored for this content item (or subcontent)
   */
  protected function getTitles()
  {
    $titles = parent::getTitles();

    $sql = " SELECT PPPTitle "
         . " FROM {$this->table_prefix}contentitem_pp_product "
         . " WHERE FK_CIID = $this->page_id "
         . " AND (COALESCE(PPPTitle, '') != '') ";
    $titles = array_merge($titles, $this->db->GetCol($sql));

    return $titles;
  }

  protected function _readSubElements()
  {
    parent::_readSubElements();

    $this->_subelements[] = new ContentItemPP_Products($this->site_id,
        $this->page_id, $this->tpl, $this->db, $this->table_prefix,
        $this->_user, $this->session, $this->_navigation, $this);

    $this->_subelements[] = new ContentItemPP_Options($this->site_id,
        $this->page_id, $this->tpl, $this->db, $this->table_prefix,
        $this->_user, $this->session, $this->_navigation, $this);
  }


  private function _editContent()
  {
    global $_LANG;
    $post = new Input(Input::SOURCE_POST);
    $types = $post->readArrayIntToInt('pp_attribute_global');

    // eliminate zero values ( no attribute type selected ) in order to look for
    // duplicate values ( except from zero )
    $tmpTypes = $types;
    foreach ($tmpTypes as $key => $val) {
      if ($tmpTypes[$key] == 0)
        unset($tmpTypes[$key]);
    }
    // at least one type has been selected twice by user
    $invalidAttributeTypes = count($tmpTypes) != count(array_unique($tmpTypes));
    if ($invalidAttributeTypes) {
      $this->setMessage(Message::createFailure($_LANG['pp_message_failure']));
      return;
    }

    // Handle default content elements.
    parent::edit_content();

    $price = $post->readFloat('pp_price');
    $casePacks = max($post->readInt('pp_case_packs'), 1);
    $shippingCosts = $post->readFloat('pp_shipping_costs');
    $settings = array_unique(array_keys($post->readArrayIntToString('pp_setting')));

    $taxRates = $this->getTaxRates();
    $taxRate = $post->readInt('pp_tax_rate', 1);
    if (!array_key_exists($taxRate, $taxRates)) { // invalid tax rate, so we reset it here
      $taxRate = 1;
    }

    $sql = " UPDATE {$this->table_prefix}contentitem_pp "
         . " SET PPPrice = $price, "
         . "     PPCasePacks = $casePacks, "
         . "     PPShippingCosts = $shippingCosts, "
         . "     PPTaxRate = $taxRate "
         . " WHERE FK_CIID = $this->page_id ";
    $result = $this->db->query($sql);

    if (!empty($types)) {
      foreach ($types as $pos => $id) {
        $sql = " UPDATE {$this->table_prefix}contentitem_pp_attribute_global "
             . " SET FK_AID = $id "
             . " WHERE FK_CIID = $this->page_id "
             . "   AND PPAPosition = $pos ";
        $this->db->query($sql);
      }
    }

    $sql = " DELETE FROM {$this->table_prefix}contentitem_pp_cartsetting "
         . " WHERE FK_CIID = $this->page_id ";
    $this->db->query($sql);

    if (!empty($settings)) {
      $sqlArgs = array();
      foreach ($settings as $id) {
        $sqlArgs[] = " ( $this->page_id, $id ) ";
      }

      $sql = " INSERT INTO {$this->table_prefix}contentitem_pp_cartsetting "
           . " ( FK_CIID, FK_CPCID ) VALUES " . implode(',', $sqlArgs);
      $this->db->query($sql);
    }
  }

  /**
   * Gets the subelement by ContentItem class
   *
   * @param Class $type
   *        use ContentItemPP_Products or ContentItemPP_Options
   *
   * @return ContentItem | null
   */
  private function _getSubelementByClassName($type)
  {
    $contentItem = null;
    foreach ($this->_subelements as $item) {
      if ((get_class($item) === $type)) {
        $contentItem = $item;
      }
    }
    return $contentItem;
  }

  /**
   * Output a JSON options result for autocomplete plugin.
   */
  private function _sendResponseOptionAutoComplete()
  {
    $get = new Input(Input::SOURCE_GET);

    $searchString = $get->readString('q');
    if (!$searchString) {
      echo Json::Encode(array());
      return;
    }

    $sql = " SELECT OPID, OPCode, OPName, OPText, OPPrice, OPProduct "
         . " FROM {$this->table_prefix}contentitem_pp_option_global "
         . " WHERE FK_SID = $this->site_id "
         . "   AND OPName LIKE '%$searchString%'"
         . " ORDER BY LENGTH(OPName) ASC ";
    $result = $this->db->query($sql);

    $searchResult = array();
    while ($row = $this->db->fetch_row($result)) {
      $searchResult[] = array(
        'code'  => parseOutput($row['OPCode']),
        'id'    => (int)$row['OPID'],
        'price' => parseOutput($row['OPPrice'], 99),
        'text'  => parseOutput($row['OPText']),
        'title' => $row['OPName'],
      );
    }
    $this->db->free_result($result);

    header('Content-Type: application/json');

    echo Json::Encode($searchResult);
  }

  /**
   * @return int
   */
  private function _getNumberOfAvailableOptions()
  {
    if ($this->_numberOfAvailableOptions === null) {
      $sql = " SELECT COUNT(*) "
           . " FROM {$this->table_prefix}contentitem_pp_option_global "
           . " WHERE FK_SID = {$this->site_id} ";
      $this->_numberOfAvailableOptions = (int)$this->db->GetOne($sql);
    }

    return $this->_numberOfAvailableOptions;
  }

  /**
   * @return int
   */
  private function _getCurrentPaginationPage()
  {
    return isset($_POST['p']) ? (int)$_POST['p'] : (isset($_GET['p']) ? (int)$_GET['p'] : 1 );
  }
}