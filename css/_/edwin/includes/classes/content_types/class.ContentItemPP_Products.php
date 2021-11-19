<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2018-03-08 14:17:07 +0100 (Do, 08 Mrz 2018) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */

class ContentItemPP_Products extends ContentItem
{
  protected $_configPrefix = 'pp_product'; // "_product" is added in $this->__construct()
  protected $_contentPrefix = 'pp_product';
  protected $_columnPrefix = 'PPP';
  protected $_contentElements = array(
    'Title' => 1,
    'Text' => 1,
    'Image' => 6,
  );
  protected $_contentBoxImage = 0;
  protected $_templateSuffix = 'PP'; // "_Product" is added in $this->__construct()


  /**
   * If a product should have been updated but the update failed then this
   * variable contains the id of this product, otherwise 0.
   *
   * @var int
   */
  private $_updateProductFailed = 0;

  /**
   * The parent contentitem ( ContentItemPP )
   *
   * @var ContentItemPP
   */
  private $_parent = null;

  /**
   * @var PageNavigation
   */
  private $_pageNavigation;

  /**
   * @see ContentItemPP_Products::_getNumberOfCurrentProducts
   * @var int
   */
  private $_numberOfCurrentProducts;

  /**
   * @see ContentItemPP_Products::_readProductAttributes
   * @var array
   */
  private $_productAttributes;

  /**
   * @var array
   */
  private $_compareFilters = array(
    "equals"        => "    (PPTitle1 = PPPTitle OR PPPTitle = '') AND ( PPCasePacks = PPPCasePacks OR PPPCasePacks = 0 ) AND ( PPPrice = PPPPrice OR PPPPrice = 0 ) AND ( PPShippingCosts = PPPShippingCosts OR PPPShippingCosts = 0 )",
    "different"     => "( ( PPPTitle != '' AND PPTitle1 != PPPTitle ) OR ( PPPCasePacks != 0 AND PPCasePacks != PPPCasePacks ) OR ( PPPPrice != 0 AND PPPrice != PPPPrice ) OR ( PPPShippingCosts != 0 AND PPShippingCosts != PPPShippingCosts ) )",
    "name"          => "PPPTitle != '' AND PPTitle1 != PPPTitle",
    "casePacks"     => "PPPCasePacks != 0 AND PPCasePacks != PPPCasePacks",
    "price"         => "PPPPrice != 0 AND PPPrice != PPPPrice",
    "shippingCosts" => "PPPShippingCosts != 0 AND PPShippingCosts != PPPShippingCosts",
// TODO: implement as soon as tax rates can be set foreach product
//    "taxRate"     => "PPTaxRate = ...",
  );

  public static function deleteItemById(Db $db, $tablePrefix, $id, $pageId)
  {

    $imageCols = array();
    for ($i = 1; $i <= 6; $i++) {
      $imageCols[] = "PPPImage$i";
    }
    $sql = " SELECT PPPID, " . implode(',', $imageCols)
         . " FROM {$tablePrefix}contentitem_pp_product "
         . " WHERE PPPID = $id ";
    $row = $db->GetRow($sql);

    if (!$row) return false;

    foreach ($imageCols as $colName) {
      self::_deleteImageFiles($row[$colName]);
    }

    $positionHelper = new PositionHelper($db, "{$tablePrefix}contentitem_pp_product ",
                                         'PPPID', 'PPPPosition',
                                         'FK_CIID', $pageId);
    // move element to highest position to resort all other elements
    $positionHelper->move($id, $positionHelper->getHighestPosition());

    // clear product database entry
    $sql = " DELETE FROM {$tablePrefix}contentitem_pp_product "
         . " WHERE PPPID = $id ";
    $db->query($sql);

    $sql = " DELETE FROM {$tablePrefix}contentitem_pp_product_attribute "
         . " WHERE FK_PPPID = $id ";
    $db->query($sql);

    // A ContentItemPP cannot be displayed, if there is not any product item
    // available.
    $sql = " SELECT COUNT(PPPID) "
         . " FROM {$tablePrefix}contentitem_pp_product "
         . " WHERE FK_CIID = $pageId ";
    if ((int)$db->GetOne($sql) < 1)
    {
      $sql = " UPDATE {$tablePrefix}contentitem "
           . " SET CDisabled = 1, "
           . "     CHasContent = 0 "
           . " WHERE CIID = $pageId ";
      $db->query($sql);
    }

    // Calculate new cheapest product from PP
    ContentItemPP::updateCheapestProduct($db, $tablePrefix, $pageId);

    return true;
  }

  public function __construct($site_id, $page_id, Template $tpl, Db $db,
                              $table_prefix, User $user, Session $session = null,
                              Navigation $navigation, ContentItemPP $parent)
  {
    parent::__construct($site_id, $page_id, $tpl, $db, $table_prefix, '', '',
                        $user, $session, $navigation);
    $this->_configPrefix .= '_product';
    $this->_templateSuffix .= '_Product';
    $this->_parent = $parent;
  }

  //////////////////////////////////////////////////////////////////////////////
  // Delete Content                                                           //
  //////////////////////////////////////////////////////////////////////////////
  public function delete_content()
  {
    global $_LANG;

    $sql = " SELECT PPPID "
         . " FROM {$this->table_prefix}contentitem_pp_product "
         . " WHERE FK_CIID = $this->page_id ";
    $items = $this->db->GetCol($sql);

    foreach ($items as $id) {
      self::deleteItemById($this->db, $this->table_prefix, $id, $this->page_id);
    }
  }

  public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
  {
    $sql = " SELECT {$this->_columnPrefix}ID "
         . " FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
         . " WHERE FK_CIID = {$this->page_id} ";
    $elements = $this->db->GetCol($sql);
    foreach ($elements as $id) {
      $pppId = parent::duplicateContent($pageId, $newParentId, "FK_CIID", $id, "{$this->_columnPrefix}ID");
      $sql = " INSERT INTO {$this->table_prefix}contentitem_pp_product_attribute (FK_PPPID, FK_AVID) "
           . "  SELECT {$pppId}, ppa.FK_AVID "
           . "    FROM {$this->table_prefix}contentitem_pp_product_attribute AS ppa "
           . "   WHERE ppa.FK_PPPID = {$id} ";
      $this->db->query($sql);
    }
  }

  public function edit_content()
  {
    $post = new Input(Input::SOURCE_POST);
    $this->_addElement($post);
    $this->_updateItem($post);
    $this->_moveItem();
    $this->_deleteItem();
    $this->_changeActivation();
    $this->_deleteProductImage();
    $this->_changeProductShowOnLevelIfRequested();
    $this->_updateProductFilter();
    $this->_resetProductFilter();
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2;

    $post = new Input(Input::SOURCE_POST);

    // get attribute data //////////////////////////////////////////////////////
    $attrTypes = array();
    $tmpAttrs = array();
    $pp_product_attr_selects = array();
    $sql = " SELECT AVID, AVTitle, PPAPosition, pa.FK_AID, ATitle "
         . " FROM {$this->table_prefix}module_attribute a "
         . " JOIN {$this->table_prefix}contentitem_pp_attribute_global pa "
         . "   ON a.FK_AID = pa.FK_AID "
         . " JOIN {$this->table_prefix}module_attribute_global "
         . "   ON pa.FK_AID = AID "
         . " WHERE FK_CIID = $this->page_id "
         . " ORDER BY PPAPosition, AVPosition ASC ";
    $result = $this->db->query($sql);

    while ($row = $this->db->fetch_row($result)) {
      $tmpPos = (int)$row['PPAPosition'];
      if (!isset($attrTypes[$tmpPos]) && $row['FK_AID'] != 0) {
        $attrTypes[$tmpPos] = array(
          'id'       => $row['FK_AID'],
          'title'    => parseOutput($row['ATitle']),
          'position' => $tmpPos,
        );
      }

      $tmpAttrs[$tmpPos][$row['AVID']] = array(
        'id'       => $row['AVID'],
        'title'    => parseOutput($row['AVTitle'], 1),
      );
    }
    ////////////////////////////////////////////////////////////////////////////

    $pagination = $this->_getPageNavigation()->getPagination();
    $paginationPage = $pagination->getCurrentPage();
    $attributeFilters = $this->_getAttributeComboBoxes($attrTypes, $tmpAttrs, $this->_getSelectedAttributeFilters());

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_pp_product",
                                         'PPPID', 'PPPPosition',
                                         'FK_CIID', $this->page_id);
    $pp_product_items = array();
    $sql = " SELECT PPPID, PPPTitle, PPPText, PPPImageTitles, PPPPrice, PPPCasePacks,"
         . "        PPPImage1, PPPImage2, PPPImage3, PPPImage4, PPPImage5, PPPImage6, "
         . "        PPPPosition, PPPDisabled, PPPNumber, PPPShippingCosts, PPPShowOnLevel, "
         . "        PPPAdditionalData, PPPTaxRate "
         . " FROM {$this->table_prefix}contentitem_pp pp "
         . " JOIN {$this->table_prefix}contentitem_pp_product p "
         . "   ON p.FK_CIID = pp.FK_CIID "
         . " JOIN {$this->table_prefix}contentitem_pp_product_attribute a "
         . "   ON FK_PPPID = PPPID "
         . " WHERE p.FK_CIID = $this->page_id "
         . $this->_getSqlConditionFromCurrentFilters()
         . " GROUP BY PPPID, PPPTitle, PPPText, PPPImageTitles, PPPPrice, PPPCasePacks,"
         . "        PPPImage1, PPPImage2, PPPImage3, PPPImage4, PPPImage5, PPPImage6, "
         . "        PPPPosition, PPPDisabled, PPPNumber, PPPShippingCosts, PPPShowOnLevel, "
         . "        PPPAdditionalData, PPPTaxRate "
         . $this->_getSqlHavingCountFromCurrentFilters()
         . " ORDER BY PPPPosition ASC "
         . " LIMIT {$pagination->getStartOffset()}, {$pagination->getResultsPerPage()}";
    $result = $this->db->query($sql);
    $pp_product_active_position = 0;
    $position = 0;
    while ($row = $this->db->fetch_row($result))
    {
      $position++;
      $tmpId = $row['PPPID'];
      $moveUpPosition = $positionHelper->getMoveUpPosition((int)$row['PPPPosition']);
      $moveDownPosition = $positionHelper->getMoveDownPosition((int)$row['PPPPosition']);

      $pp_product_image_titles = $this->explode_content_image_titles('pp_product', $row['PPPImageTitles']);

      // determine if current product is active
      if (isset($_REQUEST['product']) && $_REQUEST['product'] == $tmpId)
        $pp_product_active_position = $position;

      $productTitle = $row['PPPTitle'];
      $productText = $row['PPPText'];
      $productPrice = $row['PPPPrice'];
      $productCasePacks = (int)$row['PPPCasePacks'];
      $productAttrs = $this->_getProductAttributes($tmpId);
      $productNumber = $row['PPPNumber'];
      $productShippingCosts = $row['PPPShippingCosts'];
      $productTaxRate = (int)$row['PPPTaxRate'];
      // show input again after a failed update
      if ($this->_updateProductFailed == $tmpId)
      {
        $productTitle = $post->readString("pp_product{$tmpId}_title", Input::FILTER_PLAIN, $productTitle);
        $productText = $post->readString("pp_product{$tmpId}_text", Input::FILTER_CONTENT_TEXT, $productText);
        $productPrice = $post->readFloat("pp_product{$tmpId}_price", $productPrice);
        $productCasePacks = $post->readInt("pp_product{$tmpId}_case_packs", $productCasePacks);
        $productNumber = $post->readString("pp_product{$tmpId}_number", Input::FILTER_PLAIN, $productNumber);
        $productShippingCosts = $post->readFloat("pp_product{$tmpId}_shipping_costs", $productShippingCosts);
        $productTaxRate = $post->readInt("pp_product{$tmpId}_tax_rate", $productTaxRate);
      }
      $productTitle = parseOutput($productTitle);
      $productText = parseOutput($productText);
      $productPrice = parseOutput($productPrice, 99);
      $productNumber = parseOutput($productNumber);
      $productShippingCosts = parseOutput($productShippingCosts, 99);

      $selectedAttrs = array();
      // create attribute select boxes and set selected attributes for
      // displaying them
      foreach ($attrTypes as $key => $val) {
        $tmpSelect = '<select name="pp_product' . $tmpId . '_attribute[' . $key . ']" class="form-control">';
        $tmp = $tmpAttrs[$key];

        foreach ($tmp as $id => $val1) {
          $tmpSelect .= '<option value="' . $id . '" '
                     . ( in_array($id, $productAttrs) ? 'selected="selected"' : '')
                     . '>' . $val1['title'] . '</option>';

          if (in_array($id, $productAttrs))
            $selectedAttrs[] = $val1['title'];
        }
        $tmpSelect .= '</select>';
        $pp_product_attr_selects[$tmpId][] = array(
          'pp_product_attr_global_id' => $val['id'],
          'pp_product_attr_global_title' => $val['title'],
          'pp_product_attr_select' => $tmpSelect,
        );
      }
      $selectedAttrs = implode(' | ', $selectedAttrs);

      $tplImageUploadDetails = array();
      $count = $this->_contentElements['Image'];
      for ($i = 1; $i <= $count; $i++) {
        $image = $row['PPPImage' . $i];
        $tplImageUploadDetails = array_merge(
            $tplImageUploadDetails,
            $this->_getUploadedImageDetails($image, $this->_contentPrefix, $this->getConfigPrefix(), $i),
            array(
              'pp_product_image' . $i . '_label'          => $this->_getContentElementTemplateLabel('Image', $count, $i),
              'pp_product_image' .$i                      => $image,
              'pp_product_large_image_available' . $i     => $this->_getImageZoomLink($this->_contentPrefix, $image),
              'pp_product_required_resolution_label' . $i => $this->_getImageSizeInfo($this->getConfigPrefix(), $i),
            ));
      }

      $pp_product_items[$tmpId] = array_merge($pp_product_image_titles,
        $this->_getActivationData($row, array('urlParams' => 'p=' . rawurlencode($paginationPage))),
        $this->_getShowOnProductBoxLevelData($row), $tplImageUploadDetails, array(
        'pp_product_title'        => $productTitle,
        'pp_product_title_header' => $productTitle ? sprintf($_LANG['pp_product_title_header'], $productTitle) : '',
        'pp_product_text'         => $productText,
        'pp_product_id'              => $tmpId,
        'pp_product_position'        => $position,
        'pp_product_real_position'   => $row['PPPPosition'],
        'pp_product_move_up_link'    => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveProductID={$row['PPPID']}&amp;moveProductTo=$moveUpPosition&amp;p=$paginationPage",
        'pp_product_move_down_link'  => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;moveProductID={$row['PPPID']}&amp;moveProductTo=$moveDownPosition&amp;p=$paginationPage",
        'pp_product_delete_link'     => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;deleteProductID={$row['PPPID']}&amp;p=$paginationPage",
        'pp_product_image_alt_label' => $_LANG['m_image_alt_label'],
        'pp_product_price'           => $productPrice ? $productPrice : '',
        'pp_product_selected_attrs'  => $selectedAttrs,
        'pp_product_case_packs'      => $productCasePacks ? $productCasePacks : '',
        'pp_product_number'          => $productNumber ? $productNumber : '',
        'pp_product_shipping_costs'  => $productShippingCosts ? $productShippingCosts : '',
        'pp_product_tax_rate'        => $productTaxRate,
        'pp_product_additional_data' => $row['PPPAdditionalData'],
      ));
    }
    $this->db->free_result($result);
    $subMsg = null;
    if (empty($pp_product_items) && $this->_isProductFilterSet()) {
      $subMsg = $_LANG['pp_message_product_none_filter'];
      $subMsg = sprintf($subMsg, $this->_getPageUrl(array('resetFilter' => 1)));
      $subMsg = Message::createFailure($subMsg);
    }
    $numberOfElements = $this->_getMaximumNumberOfProducts();
    $totalNumberOfProducts = $this->_getNumberOfProducts();

    if ($totalNumberOfProducts >= $numberOfElements)
      $subMsg = Message::createFailure($_LANG['pp_message_product_max_elements']);

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_if($tplName, 'pp_add_subelement', ($totalNumberOfProducts < $numberOfElements), array());
    $this->tpl->parse_if($tplName, 'sub_message', $subMsg, ($subMsg) ? $subMsg->getTemplateArray('pp') : array());
    $this->tpl->parse_if($tplName, "pp_product_show_on_level", $this->_showPageOnLevelAvailable());
    $this->tpl->parse_if($tplName, 'pp_display_filters', ConfigHelper::get('pp_product_filterable'));
    $this->tpl->parse_if($tplName, 'pp_no_filters', !ConfigHelper::get('pp_product_filterable'));
    $this->tpl->parse_if($tplName, 'pp_tax_rates', $this->_parent->getTaxRates());
    if (ConfigHelper::get('pp_product_filterable')) {
      $compareFilter = $this->_getCompareComboBoxes($this->_getSelectedCompareFilter());
      $this->tpl->parse_if($tplName, 'pp_reset_filter_link', $this->_isProductFilterSet());
      $this->tpl->parse_vars($tplName, array(
        'pp_product_filter_changes_select' => $compareFilter,
        'pp_product_reset_filter_link'     => $this->_getPageUrl(array('resetFilter' => 1)),
      ));
      $this->tpl->parse_loop($tplName, $attributeFilters, 'product_filter_attributes');
    }

    $this->tpl->parse_loop($tplName, $pp_product_items, 'product_items');
    foreach ($pp_product_items as $pp_product_item)
    {
      $tmpProductId = $pp_product_item['pp_product_id'];
      $this->tpl->parse_if($tplName, "message{$pp_product_item['pp_product_position']}", $pp_product_item['pp_product_position'] == $pp_product_active_position && $this->_getMessage(), $this->_getMessageTemplateArray('pp_product'));

      // the delete_image link for first image is shown if there is an image
      // and if there is also either a title or a text
      $this->tpl->parse_if($tplName, "pp_product{$tmpProductId}_delete_image1", $pp_product_item['pp_product_image1'] && ($pp_product_item['pp_product_title'] || $pp_product_item['pp_product_text']), array(
        'pp_product_delete_image_link1' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;product={$tmpProductId}&amp;deleteProductImage={$tmpProductId}&amp;img=1&amp;p=$paginationPage",
      ));

      $count = $this->_contentElements['Image'];
      for ($i = 2; $i <= $count; $i++) {
        $this->tpl->parse_if($tplName, "pp_product{$tmpProductId}_delete_image$i", $pp_product_item['pp_product_image' . $i], array(
          'pp_product_delete_image_link' . $i => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;product={$tmpProductId}&amp;deleteProductImage={$tmpProductId}&amp;img=$i&amp;p=$paginationPage",
        ));
      }
      $taxRates = $this->_parent->getTaxRatesTemplateVars($pp_product_item['pp_product_tax_rate']);
      $this->_parseTemplateCommonParts($tplName, $tmpProductId);
      $this->tpl->parse_loop($tplName, $pp_product_attr_selects[$tmpProductId], 'product_' . $tmpProductId . '_attributes');
      $data = $this->_getAdditionalDataTemplateVariables($pp_product_item['pp_product_additional_data']);
      $this->tpl->parse_loop($tplName, $data, 'product_' . $tmpProductId . '_additional_data');
      $this->tpl->parse_loop($tplName, $taxRates, 'pp_product' . $tmpProductId . '_tax_rate_items');
    }
    $pageNavigationHtml = $this->_getPageNavigation()->html();
    $this->tpl->parse_if($tplName, 'page_navigation', $pageNavigationHtml, array(
      'pp_product_page_navigation' => $pageNavigationHtml,
    ));

    $pp_product_items_output = $this->tpl->parsereturn($tplName, array(
      'pp_actionform_action' => $this->_getPageUrl(array('p' => $paginationPage)),
      'pp_product_count' => $totalNumberOfProducts,
      'pp_product_active_position' => $pp_product_active_position,
      'pp_product_dragdrop_link_js' => "index.php?action=content&site=$this->site_id&page=$this->page_id&p=$paginationPage&moveProductID=#moveID#&moveProductTo=#moveTo#",
    ));

    return array(
      'message' => $this->_getMessage(),
      'content' => $pp_product_items_output,
      'count'   => $totalNumberOfProducts,
    );
  }

  protected function _changeActivation($settings = array())
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);

    $idParam = isset($settings['idParam']) ? $settings['idParam'] : 'changeActivationID';
    $toParam = isset($settings['toParam']) ? $settings['toParam'] : 'changeActivationTo';

    $id = $get->readInt($idParam);
    $type = $get->readString($toParam, Input::FILTER_NONE);

    if (!$id || !$type) {
      return;
    }

    switch ( $type ) {
      case ContentBase::ACTIVATION_ENABLED;
        $to = 0;
        break;
      case ContentBase::ACTIVATION_DISABLED;
        $to = 1;
        break;
      default: return; // invalid activation status
    }

    $parentSql = " AND FK_CIID = $this->page_id ";
    if (isset($settings['parentField']) && isset($settings['parentId']))
      $parentSql = " AND {$settings['parentField']} = {$settings['parentId']} ";

    $sql = " UPDATE {$this->table_prefix}contentitem_{$this->_contentPrefix} "
         . " SET {$this->_columnPrefix}Disabled = $to "
         . " WHERE {$this->_columnPrefix}ID = $id "
         . $parentSql;
    $this->db->query($sql);

    $msg = $_LANG[$this->_contentPrefix.'_message_activation_'.$type];

    $page = $this->_navigation->getPageByID($this->page_id);

    // change activation for linked contentitem's subelements
    // products can not be matched by position, they have to be compared by
    // attributes:
    //   only product with all attributes linked to each other are equal
    if ($this->_structureLinks && in_array($page->getContentTypeId(), ConfigHelper::get('m_sructure_links_enable_disable'))) {
      $sql = " SELECT COUNT(FK_AVID) "
           . " FROM {$this->table_prefix}contentitem_pp_product_attribute "
           . " WHERE FK_PPPID = $id ";
      $attrCount = $this->db->GetOne($sql);

      $sql = " SELECT FK_ALID "
           . " FROM {$this->table_prefix}contentitem_pp_product_attribute "
           . " JOIN {$this->table_prefix}module_attribute "
           . "      ON FK_AVID = AVID "
           . " WHERE FK_PPPID = $id "
           . "   AND FK_ALID != 0 ";
      $relations = $this->db->GetCol($sql);

      if ($attrCount == count($relations)) {
        $count = 0; // amount of linked items updated successfully
        $sqlRelations = implode(',', $relations);
        foreach ($this->_structureLinks as $pageId) {
          // get linked product
          $sql = " SELECT FK_PPPID "
               . " FROM {$this->table_prefix}contentitem_pp_product pp "
               . " JOIN {$this->table_prefix}contentitem_pp_product_attribute "
               . "      ON FK_PPPID = PPPID "
               . " JOIN {$this->table_prefix}module_attribute at "
               . "      ON FK_AVID = AVID "
               . " WHERE pp.FK_CIID = $pageId "
               . "   AND FK_ALID IN ($sqlRelations) "
               . " GROUP BY FK_PPPID "
               . " HAVING COUNT(FK_ALID) = $attrCount "; // all attributes have to be linked
          $productId = $this->db->GetOne($sql);

          if ($productId) {
            $sql = " UPDATE {$this->table_prefix}contentitem_{$this->_contentPrefix} "
                 . " SET {$this->_columnPrefix}Disabled = $to "
                 . " WHERE {$this->_columnPrefix}ID = $productId ";
            $this->db->query($sql);
            $count++;
          }
        }
        $msg = sprintf($_LANG['pp_product_message_activation_linked_'.$type], $count);
      }
    }

    $this->setMessage(Message::createSuccess($msg));
  }

  protected function _processedValues()
  {
    return array( 'changeActivationID',
                  'changeShowOnLevelID',
                  'deleteProductID',
                  'deleteProductImage',
                  'moveProductID',
                  'process_new_element',
                  'process_pp_product',
                  'process_pp_product_filter',
                  'resetFilter', );
  }

  /**
   * May adds a new element, if corresponding process button was clicked
   */
  private function _addElement(Input $post)
  {
    global $_LANG;

    if (!$post->exists('process_new_element'))
      return;

    // Determine the amount of currently existing elements.
    $existingElements = $this->_getNumberOfProducts();
    $numberOfElements = $this->_getMaximumNumberOfProducts();

    // invalid request, as the maximum number of elements has been reached
    if ($existingElements >= $numberOfElements)
      return;

    $existing = $this->_getUsedVariations();

    // find new attribute variation
    $sql = " SELECT a.FK_AID, GROUP_CONCAT(CAST(AVID AS CHAR)) AS Attributes "
         . " FROM {$this->table_prefix}module_attribute a "
         . " JOIN {$this->table_prefix}contentitem_pp_attribute_global ag "
         . "   ON a.FK_AID = ag.FK_AID "
         . " WHERE FK_CIID = $this->page_id "
         . " GROUP BY a.FK_AID ";
    $col = $this->db->GetAssoc($sql);

    $attrs = array();
    foreach ($col as $key => $val)
      $attrs[] = explode(',', $val);


    $newItemAttrs = array();
    if (!$this->_calcAttributes($existing, $attrs, $newItemAttrs, 0) || empty($newItemAttrs)) {
      $this->setMessage(Message::createFailure($_LANG['pp_message_product_create_failure']));
      return;
    }

    // insert the product row
    $pos = $existingElements + 1;
    $showOnLevelDefault = (int)ConfigHelper::get('pp_product_show_on_level_default');
    $sql = " INSERT INTO {$this->table_prefix}contentitem_pp_product "
         . " (FK_CIID, PPPPosition, PPPShowOnLevel) VALUES "
         . " ($this->page_id, $pos, $showOnLevelDefault) ";
    $result = $this->db->query($sql);

    // insert product attributes
    if ($result) {
      $id = $this->db->insert_id();
      $sql = " INSERT INTO {$this->table_prefix}contentitem_pp_product_attribute "
           . " (FK_PPPID, FK_AVID) VALUES ";
      foreach ($newItemAttrs as $val)
        $sqlParts[] = "($id, $val)";
      $sql .= implode(',', $sqlParts);
      $this->db->query($sql);

      $this->setMessage(Message::createSuccess($_LANG["pp_message_product_create_success"]));
    }
  }

  private function _calcAttributes($used, $attrs, &$tmpAttrs, $index)
  {
    $ids = $attrs[$index];

    if (isset($attrs[$index + 1])) {
      foreach ($ids as $val) {
        $tmpAttrs[$index] = $val;
        if ($this->_calcAttributes($used, $attrs, $tmpAttrs, $index + 1))
          return true;
      }
    }

    foreach ($ids as $val) {
      $tmpAttrs[$index] = $val;

      $unused = true;
      if ($this->_isVariationUsed($used, $tmpAttrs))
        $unused = false;

      if ($unused)
        return true;
    }

    return false;
  }

  /**
   * Deletes a product if the GET parameter deleteProductID is set.
   */
  private function _deleteItem()
  {
    global $_LANG;

    if (!isset($_GET["deleteProductID"]))
      return;

    $id = (int)$_GET["deleteProductID"];

    if (ContentItemPP_Products::deleteItemById($this->db, $this->table_prefix, $id, $this->page_id))
      $this->setMessage(Message::createSuccess($_LANG["pp_message_product_delete_success"]));
  }

  /**
   * Deletes a product image if the GET parameter deleteProductImage is set.
   */
  private function _deleteProductImage()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);
    $id = $get->readInt('deleteProductImage');
    $number = $get->readInt('img');

    if (!$id || !$number)
      return;

    // determine image files
    $sql = " SELECT PPPImage{$number} "
         . " FROM {$this->table_prefix}contentitem_pp_product "
         . " WHERE PPPID = $id ";
    $image = $this->db->GetOne($sql);

    // update product database entry before actually deleting the image file
    // if it was the other way around there could be a reference to a
    // non-existing file in case of a crash
    $sql = " UPDATE {$this->table_prefix}contentitem_pp_product "
         . " SET PPPImage{$number} = '' "
         . " WHERE PPPID = $id ";
    $this->db->query($sql);

    // delete image file
    self::_deleteImageFiles($image);

    $this->setMessage(Message::createSuccess($_LANG['pp_message_product_success']));
  }

  /**
   * Determine the amount of currently filtered products.
   * @return int
   */
  private function _getNumberOfCurrentProducts()
  {
    if ($this->_numberOfCurrentProducts === null) {
      $sql = " SELECT PPPID "
           . " FROM {$this->table_prefix}contentitem_pp pp "
           . " JOIN {$this->table_prefix}contentitem_pp_product p "
           . "   ON p.FK_CIID = pp.FK_CIID "
           . " JOIN {$this->table_prefix}contentitem_pp_product_attribute a "
           . "   ON FK_PPPID = PPPID "
           . " WHERE p.FK_CIID = $this->page_id "
           . $this->_getSqlConditionFromCurrentFilters()
           . " GROUP BY PPPID "
           . $this->_getSqlHavingCountFromCurrentFilters();
      $cols = $this->db->GetCol($sql);
      $this->_numberOfCurrentProducts = is_array($cols) ? count($cols) : 0;
    }
    return $this->_numberOfCurrentProducts;
  }

  /**
   * @return string
   */
  private function _getSqlConditionFromCurrentFilters()
  {
    $where = '';
    $attributeFilters = $this->_getSelectedAttributeFilters();
    if ($attributeFilters) {
      $where .= " AND ";
      $args = array();
      foreach ($attributeFilters as $attribute) {
        $args[] = " a.FK_AVID = '" . (int)$attribute . "' ";
      }
      $where .= ' (' . implode(' OR ', $args) . ') ';
    }

    $compareFilter = $this->_getSelectedCompareFilter();
    if ($compareFilter) {
      $where .= ' AND ' . $this->_compareFilters[$compareFilter];
    }

    return $where;
  }

  private function _getSqlHavingCountFromCurrentFilters()
  {
    $sql = '';
    $attributeFilters = $this->_getSelectedAttributeFilters();
    if ($attributeFilters) {
      $count = count($attributeFilters);
      $sql = " HAVING COUNT(*) = $count ";
    }

    return $sql;
  }

  /**
   * @param int $productId
   * @return array the attribute ids of attributes for this product
   */
  private function _getProductAttributes($productId)
  {
    $this->_readProductAttributes();
    return $this->_productAttributes[$productId];
  }

  private function _readProductAttributes()
  {
    if ($this->_productAttributes === null) {
      $sql = " SELECT PPPID, FK_AVID "
           . " FROM {$this->table_prefix}contentitem_pp pp "
           . " JOIN {$this->table_prefix}contentitem_pp_product p "
           . "   ON p.FK_CIID = pp.FK_CIID "
           . " JOIN {$this->table_prefix}contentitem_pp_product_attribute a "
           . "   ON FK_PPPID = PPPID "
           . " WHERE p.FK_CIID = $this->page_id "
           . " ORDER BY PPPPosition ASC ";
      $result = $this->db->query($sql);



      $this->_productAttributes = array();
      while ($row = $this->db->fetch_row($result)) {
        $productId = $row['PPPID'];
        if (!isset($this->_productAttributes[$productId])) {
          $this->_productAttributes[$productId] = array();
        }
        $this->_productAttributes[$productId][] = $row['FK_AVID'];
      }
    }
  }

  /**
   * Determine the amount of currently existing products.
   *
   * @return int
   *         The number of products
   */
  private function _getNumberOfProducts()
  {
    $sql = " SELECT COUNT(PPPID) "
         . " FROM {$this->table_prefix}contentitem_pp_product "
         . " WHERE FK_CIID = {$this->page_id} ";
    return (int) $this->db->GetOne($sql);
  }

  /**
   * Return existing attribute variations for product
   *
   * @param array $excludedProductIds [optional]
   *        An array containing product ids, from products, where attribute
   *        variation should not be retrieved from.
   *
   * @return array
   *         Existing attribute variations, empty if no products have been
   *         created for current contentitem
   */
  private function _getUsedVariations($excludedProductIds =array())
  {
    // get all existing attribute variations from products
    $sql = " SELECT GROUP_CONCAT(CAST(FK_AVID AS CHAR)) AS Attributes "
         . " FROM {$this->table_prefix}contentitem_pp_product_attribute "
         . " JOIN {$this->table_prefix}contentitem_pp_product "
         . "   ON FK_PPPID = PPPID "
         . " WHERE FK_CIID = $this->page_id "
         . (!empty($excludedProductIds) ? "   AND FK_PPPID NOT IN (" . implode(',', $excludedProductIds) . ") " : "")
         . " GROUP BY FK_PPPID ";
    $col = $this->db->GetCol($sql);

    $existing = array();
    foreach ($col as $val)
      $existing[] = explode(',', $val);

    return $existing;
  }

  /**
   * Check if the given attribute variation is already used
   *
   * @param array $used
   *        The used attribute variations ( array(0 => array(1,10), 1 => array(5,9)) ).
   *        Use data from ContentItemPP_Products::_getUsedVariations().
   * @param array $attrs
   *        The attribute variation to check
   *
   * @return bool
   */
  private function _isVariationUsed($used, $attrs)
  {
    foreach ($used as $row) {
      // variation exists within used variations
      if (array_diff($row, $attrs) === array_diff($attrs, $row))
        return true;
    }

    return false;
  }

  /**
   * Moves a product if the GET parameters moveProductID and moveProductTo are
   * set.
   */
  private function _moveItem()
  {
    global $_LANG;

    if (!isset($_GET['moveProductID'], $_GET['moveProductTo']))
      return;

    $moveID = (int)$_GET['moveProductID'];
    $moveTo = (int)$_GET['moveProductTo'];

    $positionHelper = new PositionHelper($this->db, "{$this->table_prefix}contentitem_pp_product",
                                         'PPPID', 'PPPPosition',
                                         'FK_CIID', $this->page_id);
    $moved = $positionHelper->move($moveID, $moveTo);

    if ($moved)
      $this->setMessage(Message::createSuccess($_LANG['pp_message_product_move_success']));
  }

  /**
   * Determine the maximum number of products from attributes available
   *
   * @return int
   *         The maximum number of products
   */
  private function _getMaximumNumberOfProducts()
  {
    global $_LANG, $_LANG2;

    $max = 1;
    $sql = " SELECT COUNT(AVID) "
         . " FROM {$this->table_prefix}contentitem_pp_attribute_global ca "
         . " JOIN {$this->table_prefix}module_attribute a "
         . "   ON ca.FK_AID = a.FK_AID "
         . " WHERE FK_CIID = $this->page_id "
         . " GROUP BY (a.FK_AID) ";
    $col = $this->db->GetCol($sql);
    foreach ($col as $val)
      $max *= $val;

    return $max;
  }

  /**
   * Updates a product if the POST parameter process_pp_product is set.
   */
  private function _updateItem(Input $post)
  {
    global $_LANG;

    $id = $post->readKey('process_pp_product');
    if (!$id)
      return;

    // Read attributes
    $attrs = $post->readArrayIntToInt('pp_product'.$id.'_attribute');
    if ($this->_isVariationUsed($this->_getUsedVariations(array($id)), $attrs)) {
      $this->_updateProductFailed = $id;
      $this->setMessage(Message::createFailure($_LANG['pp_message_product_edit_failure']));
      return;
    }

    // Read all content elements.
    $input['Title'] = $this->_readContentElementsTitles($id);
    $input['Text'] = $this->_readContentElementsTexts($id);
    $components = array($this->site_id, $this->page_id, $id);
    $input['Image'] = $this->_readContentElementsImages($components, $id, $id, 'PPPID');
    $price = $post->readFloat('pp_product'.$id.'_price');
    $casePacks = abs($post->readInt('pp_product'.$id.'_case_packs'));
    $number = $post->readString('pp_product'.$id.'_number', Input::FILTER_PLAIN);
    $shippingCosts = $post->readFloat('pp_product'.$id.'_shipping_costs');
    $additionalData = $this->_makeAdditionalDataString($this->_readAdditionalDataFromInput($id));

    $taxRates = $this->_parent->getTaxRates();
    $taxRate = $post->readInt('pp_product'.$id.'_tax_rate');
    $invalidTaxRate = $taxRate > 0 && !array_key_exists($taxRate, $taxRates);
    if ($invalidTaxRate) { // invalid tax rate, so we reset it here
      $taxRate = 0;
    }

    // update images of linked content items
    if ($this->_structureLinksAvailable && $this->_structureLinks)
    {
      $currentPage = $this->_navigation->getCurrentPage();
      foreach ($this->_structureLinks as $pageID)
      {
        $page = $this->_navigation->getPageByID($pageID);
        $products = new ContentItemPP_Products($page->getSite()->getID(), $pageID,
                            $this->tpl, $this->db, $this->table_prefix, $this->_user,
                            $this->session, $this->_navigation, $this->_parent);
        $products->updateStructureLinkSubContentImages($id);
      }
    }

    // Update the database. Do not use ContentItem::_buildContentElementsUpdateStatement
    $sql = " UPDATE {$this->table_prefix}contentitem_pp_product "
         . " SET PPPTitle = '{$this->db->escape($input['Title']['PPPTitle'])}', "
         . "     PPPText = '{$this->db->escape($input['Text']['PPPText'])}', "
         . "     PPPImage1 = '{$input['Image']['PPPImage1']}', "
         . "     PPPImage2 = '{$input['Image']['PPPImage2']}', "
         . "     PPPImage3 = '{$input['Image']['PPPImage3']}', "
         . "     PPPImage4 = '{$input['Image']['PPPImage4']}', "
         . "     PPPImage5 = '{$input['Image']['PPPImage5']}', "
         . "     PPPImage6 = '{$input['Image']['PPPImage6']}', "
         . "     PPPImageTitles = '{$this->db->escape($input['Image']['PPPImageTitles'])}', "
         . "     PPPPrice = $price, "
         . "     PPPCasePacks = $casePacks, "
         . "     PPPShippingCosts = $shippingCosts, "
         . "     PPPTaxRate = $taxRate, "
         . "     PPPAdditionalData = '{$this->db->escape($additionalData)}', "
         . "     PPPNumber = '{$this->db->escape($number)}' "
         . " WHERE PPPID = $id ";
    $result = $this->db->query($sql);

    // delete old attribute settings
    $sql = " DELETE FROM {$this->table_prefix}contentitem_pp_product_attribute "
         . " WHERE FK_PPPID = $id ";
    $this->db->query($sql);

    // insert new attribute settings
    $sql = " INSERT INTO {$this->table_prefix}contentitem_pp_product_attribute "
         . " (FK_PPPID, FK_AVID) VALUES ";
    foreach ($attrs as $val)
      $sqlParts[] = "($id, $val)";
    $sql .= implode(',', $sqlParts);
    $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG['pp_message_product_success']));
  }

  /**
   * @param array $row
   *
   * @return array
   */
  private function _getShowOnProductBoxLevelData($row)
  {
    global $_LANG;

    $paginationPage = $this->_getPageNavigation()->getCurrentPageFromUrlParam();
    $showOnLevel = (bool)$row['PPPShowOnLevel'];
    $id = (int)$row[$this->_columnPrefix.'ID'];
    $idParam = 'changeShowOnLevelID';
    $toParam = 'changeShowOnLevelTo';
    $link = "index.php?action=content&amp;site=$this->site_id&amp;page=$this->page_id&amp;p=$paginationPage&amp;$idParam=$id&amp;$toParam=";

    if ($showOnLevel) {
      $status = ActivationLightInterface::GREEN;
      $link .= '0';
    }
    else {
      $status = ActivationLightInterface::RED;
      $link .= '1';
    }

    $label = $_LANG['pp_product_show_on_level_'.$status.'_label'];

    return array(
      $this->_contentPrefix.'_show_on_level_light' => $status,
      $this->_contentPrefix.'_show_on_level_label' => $label,
      $this->_contentPrefix.'_show_on_level_link'  => $link,
    );
  }

  private function _changeProductShowOnLevelIfRequested()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);
    $id = (int)$get->readInt('changeShowOnLevelID');
    $value = (int)$get->readInt('changeShowOnLevelTo');

    if (!$id) {
      return;
    }

    $sql = " UPDATE {$this->table_prefix}contentitem_pp_product "
         . " SET PPPShowOnLevel = $value "
         . " WHERE PPPID = $id ";
    if ($this->db->query($sql)) {
      $action = $value ? 'activated' : 'deactivated';
      $this->setMessage(Message::createSuccess(
          $_LANG["pp_product_message_show_on_level_$action"]));
    }
  }

  /**
   * @return bool
   */
  private function _showPageOnLevelAvailable()
  {
    return ConfigHelper::get('pp_product_show_on_level') &&
           $this->_page->getParent() && $this->_page->getParent()->isProductBox();
  }

  /**
   * @param string $str
   * @return array
   */
  private function _getAdditionalDataTemplateVariables($additionalData)
  {
    global $_LANG;

    $config = $this->getConfig('additional_data');
    $labels = $_LANG['pp_product_additional_data_labels'];
    $data = $this->_splitAdditionalDataString($additionalData);
    $tplVars = array();

    foreach ($config as $index => $type) {
      $value = isset($data[$index]) ? parseOutput($data[$index]) : '';
      $tplVars[] = array(
        'pp_product_additional_data_index' => $index,
        'pp_product_additional_data_label' => $labels[$index],
        'pp_product_additional_data_type'  => $type,
        'pp_product_additional_data_value' => $value,
      );
    }
    return $tplVars;
  }

  /**
   * @param string $str
   * @return array
   */
  private function _splitAdditionalDataString($str)
  {
    return explode('$%$', $str);
  }

  /**
   * @param array $data
   * @return string
   */
  private function _makeAdditionalDataString($data)
  {
    return implode('$%$', (array)$data);
  }

  /**
   * @param int $productId
   * @return array
   */
  private function _readAdditionalDataFromInput($productId)
  {
    $input = new Input(Input::SOURCE_POST);
    $name = 'pp_product' . $productId . '_additional_data';
    $data = $input->readArrayIntToString($name, Input::FILTER_PLAIN);
    $config = $this->getConfig('additional_data');

    $values = array();
    foreach ($config as $index => $type) {
      $values[] = isset($data[$index]) ? $data[$index] : '';
    }
    return $values;
  }

  /**
   * @return PageNavigation
   */
  private function _getPageNavigation()
  {
    global $_LANG;

    if ($this->_pageNavigation === null) {
      $resultsPerPage = (int)ConfigHelper::get('pp_product_results_per_page');
      $linksPerPage = (int)ConfigHelper::get('pp_product_links_per_page');

      $navigation = new PageNavigation(new Pagination());
      $navigation->setLinkCurrentHtml($_LANG['global_results_showpage_current'])
                 ->setLinkOtherHtml($_LANG['global_results_showpage_other'])
                 ->setLinkFirstHtml($_LANG['global_results_showpage_first'])
                 ->setLinkLastHtml($_LANG['global_results_showpage_last'])
                 ->setLinkPreviousHtml($_LANG['global_results_showpage_previous'])
                 ->setLinkNextHtml($_LANG['global_results_showpage_next'])
                 ->setPageUrlParam('p')
                 ->setUrl($this->_getPageUrl())
                 ->setTotalResults($this->_getNumberOfCurrentProducts())
                 ->setResultsPerPage($resultsPerPage)
                 ->setLinksPerPage($linksPerPage);
      $p = isset($_POST['p']) ? (int)$_POST['p'] :
           $navigation->getCurrentPageFromUrlParam();
      $navigation->setCurrentPage($p);
      $this->_pageNavigation = $navigation;
    }
    return $this->_pageNavigation;
  }

  /**
   * @return string
   */
  private function _getPageUrl($params = array())
  {
    $part = '';
    foreach ($params as $name => $value) {
      $part .= '&amp;' . $name . '=' . rawurlencode($value);
    }

    return "index.php?action=content&amp;site=$this->site_id&amp;page=$this->page_id$part";
  }

  /**
   * Creates attribute select boxes and set selected attributes
   *
   * @param array $attributeTypes
   * @param array $attributes
   * @param array $selectedAttributes
   *
   * @return array
   */
  private function _getAttributeComboBoxes($attributeTypes, $attributes, $selectedAttributes)
  {
    global $_LANG;

    $items = array();
    foreach ($attributeTypes as $key => $type) {
      $select = '<select name="pp_product_filter_attribute[' . $key . ']" class="form-control">'
                 . '<option value="0">' . $_LANG['pp_product_filter_none_label'] . '</option>';
      $tmp = $attributes[$key];

      foreach ($tmp as $id => $type1) {
        $select .= '<option value="' . $id . '" '
                   . ( in_array($id, $selectedAttributes) ? 'selected="selected"' : '')
                   . '>' . $type1['title'] . '</option>';
      }
      $select .= '</select>';

      $items[$key] = array(
        'pp_product_attr_global_id'    => $type['id'],
        'pp_product_attr_global_title' => $type['title'],
        'pp_product_attr_select'       => $select,
      );
    }
    return $items;
  }

  /**
   * Creates attribute select boxes and set selected attributes
   *
   * @param int $selectedFilter
   * @return string
   */
  private function _getCompareComboBoxes($selectedFilter)
  {
    global $_LANG;

    $items = array();
    $select = '<select name="pp_product_filter_compare" class="form-control">'
            . '<option value="">' . $_LANG['pp_product_filter_none_label'] . '</option>';
    foreach ($this->_compareFilters as $key => $val) {
      $select .= '<option value="' . $key . '" '
                 . ( $key === $selectedFilter ? 'selected="selected"' : '')
                 . '>' . $_LANG['pp_product_filter_compare'][$key] . '</option>';
    }
    $select .= '</select>';
    return $select;
  }

  /**
   * @return array
   */
  private function _getSelectedAttributeFilters()
  {
    $request = new Input(Input::SOURCE_REQUEST);
    $filter = $this->_getProductSessionFilter();
    $filter = $filter['attribute'];
    // clean from non-set filters ( combobox value equals 0 )
    foreach ($filter as $key => $val) {
      if (!$val) {
        unset($filter[$key]);
      }
    }
    return $filter;
  }

  /**
   * @return int
   */
  private function _getSelectedCompareFilter()
  {
    $request = new Input(Input::SOURCE_REQUEST);
    $filter = $this->_getProductSessionFilter();
    $filter = $filter['compare'];
    return $filter;
  }

  private function _updateProductFilter()
  {
    global $_LANG;

    $post = new Input(Input::SOURCE_POST);
    if (!$post->exists('process_pp_product_filter')) {
      return;
    }

    $attributeFilter = $post->readArrayIntToInt('pp_product_filter_attribute');
    $compareFilter = $post->readString('pp_product_filter_compare');
    $compareFilter = isset($this->_compareFilters[$compareFilter]) ? $compareFilter : '';

    $all = $this->session->read('pp_product_filter');
    $filter = isset($filter[$this->page_id]) ? $filter[$this->page_id] : array();
    $filter['attribute'] = $attributeFilter;
    $filter['compare'] = $compareFilter;
    $all[$this->page_id] = $filter;
    $this->session->save('pp_product_filter', $all);

    // additionaly reset the current page of list, so we do not display an empty
    // list of products
    $this->_getPageNavigation()->setCurrentPage(1);
  }

  private function _resetProductFilter()
  {
    global $_LANG;

    $get = new Input(Input::SOURCE_GET);
    if (!$get->exists('resetFilter')) {
      return;
    }

    $all = $this->session->read('pp_product_filter');
    if (isset($all[$this->page_id])) {
      unset($all[$this->page_id]);
    }
    $this->session->save('pp_product_filter', $all);
  }

  /**
   * @return array
   *         - attributes : array of filtered attribute ids
   *         - compare : id of the selected 'compare' filter
   */
  private function _getProductSessionFilter()
  {
    $default = array('attribute' => array(), 'compare' => '');
    $all = $this->session->read('pp_product_filter');
    $filter = isset($all[$this->page_id]) ? $all[$this->page_id] : array();
    return array_merge($default, $filter);
  }

  /**
   * @return bool
   */
  private function _isProductFilterSet()
  {
    if (   ConfigHelper::get('pp_product_filterable')
        && $this->_getSelectedAttributeFilters()
        && $this->_getSelectedCompareFilter()
    ) {
      return true;
    }
    else {
      return false;
    }
  }
}