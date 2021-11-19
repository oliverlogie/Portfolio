<?php

/**
 * ModuleBlogManagementApproved Module Class
 *
 * $LastChangedDate: 2014-04-04 14:17:32 +0200 (Fr, 04 Apr 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2010 Q2E GmbH
 */

class ModuleBlogManagementApproved extends AbstractModuleBlog
{
  /**
   * Stores actions allowed for comment handling.
   *
   * With approved comments, the available actions are:
   * - trash - put the comment into the trash can
   *
   * - reply - reply to a single comment
   *
   * @var array
   */
  protected $_commentActions = array(GeneralBlog::ACTION_TRASH, GeneralBlog::ACTION_REPLY,
                                     GeneralBlog::ACTION_EDIT);
  protected $_prefix = 'ba';
  /**
   * Shortname used in the 'action2' url parameter for module subclasses.
   *
   * As ModuleBlogManagementApproved is a module subclass of ModuleBlogManagement
   * the $_moduleAction is set to its value defined in the
   * ModuleBlogManagement::$subClasses array.
   *
   * @var string
   */
  protected $_moduleAction = 'approved';
  protected $_published = 1;
  protected $_canceled = 0;
  protected $_deleted = 0;

  /**
   * Show inner content
   */
  public function show_innercontent()
  {
    $this->_blog->trashComments();

    if (isset($this->action[0]) && $this->action[0]) {
      return $this->_showForm();
    }
    else {
      return $this->_showList();
    }
  }
}

