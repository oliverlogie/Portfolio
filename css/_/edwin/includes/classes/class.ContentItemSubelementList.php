<?php

/**
 * Represents a list of ContentItems, providing standard array access.
 *
 * Additionally there are custom functions for working with ContentItems
 * provided by the list class.
 *
 * Use this list within ContentItem objects to store subelements of type
 * ContentItem.
 *
 * $LastChangedDate: 2014-03-10 11:34:35 +0100 (Mo, 10 Mär 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2012 Q2E GmbH
 */
class ContentItemSubelementList extends CmsList
{
  /**
   * Calls the delete_content() method for all ContentItems in the list
   *
   * @return void
   */
  public function delete_content()
  {
    foreach ($this->_items as $item) {
      $item->delete_content();
    }
  }

  /**
   * Calls the duplicateContent() method for all ContentItems in the list
   *
   * @see ContentItem::duplicateContent
   */
  public function duplicateContent($pageId, $newParentId = 0, $parentField = '', $id = 0, $idField = '')
  {
    foreach ($this->_items as $item) {
      $item->duplicateContent($pageId, $newParentId, $parentField, $id, $idField);
    }
  }

  /**
   * Calls the edit_content() method for all ContentItems in the list
   *
   * @return void
   */
  public function edit_content()
  {
    foreach ($this->_items as $item) {
      $item->edit_content();
    }
  }

  /**
   * Returns the request value ( $_POST, $_GET ) identifying, which type of
   * processing is requested.
   *
   * @see ContentItem::getProcessedValue()
   *
   * @return string
   *         the editing / processing type, empty string if the ContentItem
   *         is not processed
   *         I.e. 'process', 'process_date' or the value for processing
   *               one of the ContentItems's subelements
   *               i.e. 'process_qs_statement'
   */
  public function getProcessedValue()
  {
    $value = '';

    foreach ($this->_items as $key => $item) {
      $request = $item->getProcessedValue();

      if ($request) {
        $value = $request;
      }
    }

    return $value;
  }

  /**
   * Checks if one of the ContentItems in the list has to be processed
   *
   * @return bool
   *         true | false
   */
  public function isProcessed()
  {
    if ($this->getProcessedValue()) {
      return true;
    }
    else {
      return false;
    }
  }

  /**
   * Implements the offsetSet() method from the interface ArrayAccess. Allows
   * only instances of class ContentItem to be added to list.
   *
   * @return void
   */
  public function offsetSet($offset, $value)
  {
    if (!($value instanceof ContentItem)) {
      throw new Exception('Wrong object type for ContentItemSubitemList. Only objects of type ContentItem may be added to this list.');
    }

    if ($offset) {
      $this->_items[$offset] = $value;
    }
    else {
      $this->_items[] = $value;
    }
  }
}