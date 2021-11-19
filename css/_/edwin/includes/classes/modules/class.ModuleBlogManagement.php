<?php

/**
 * ModuleBlogManagement Module Class
 *
 * $LastChangedDate: 2014-04-15 10:44:16 +0200 (Di, 15 Apr 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2010 Q2E GmbH
 */

class ModuleBlogManagement extends AbstractModuleBlog
{
  public static $subClasses = array(
      'approved' => 'ModuleBlogManagementApproved',
      'trash'    => 'ModuleBlogManagementTrash',
  );

  /**
   * Stores actions allowed for comment handling.
   *
   * With unapproved comments, the available actions are:
   * - trash - put the comment into the trash can
   * - approve - approve the comment
   *
   * - edit - edit a single comment before approving it
   *
   * @var array
   */
  protected $_commentActions = array(GeneralBlog::ACTION_APPROVE, GeneralBlog::ACTION_TRASH,
                                     GeneralBlog::ACTION_EDIT);
  protected $_prefix = 'bm';
  /**
   * Shortname used in the 'action2' url parameter for module subclasses.
   *
   * As ModuleBlogManagement isn't a subclass $_moduleAction is not set
   *
   * @var string
   */
  protected $_moduleAction = '';
  protected $_published = 0;
  protected $_canceled = 0;
  protected $_deleted = 0;

  /**
   * Show inner content
   */
  public function show_innercontent()
  {
    $this->_blog->trashComments();

    $this->_blog->approveComments();

    if (isset($this->action[0]) && $this->action[0]) {
      return $this->_showForm();
    }
    else {
      return $this->_showList();
    }
  }
}

