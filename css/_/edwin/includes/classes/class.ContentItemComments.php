<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2017-08-23 08:29:24 +0200 (Mi, 23 Aug 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2010 Q2E GmbH
 */
class ContentItemComments extends ContentItem
{
  /**
   * Stores actions allowed for comment handling.
   *
   * @var array
   */
  protected $_commentActions = array(GeneralBlog::ACTION_APPROVE, GeneralBlog::ACTION_EDIT, GeneralBlog::ACTION_REPLY, GeneralBlog::ACTION_TRASH);
  /**
   * The published database value. (Used with database queries)
   *
   * @var int 0 | 1 | null
   */
  protected $_published = null;
  /**
   * The canceled database value. (Used with database queries)
   *
   * @var int 0 | 1 | null
   */
  protected $_canceled = 0;
  /**
   * The deleted database value. (Used with database queries)
   *
   * @var int 0 | 1 | null
   */
  protected $_deleted = 0;
  /**
   * The GeneralBlog object handles comments.
   *
   * @var GeneralBlog
   */
  protected $_blog = null;
  /**
   * The active offset in the list paging.
   *
   * @var int
   */
  protected $_offset = null;
  /**
   * The active list order type i.e. author, page, email
   *
   * @var string
   */
  protected $_order = null;
  /**
   * The active list order.
   *
   * @var int
   *      1: list order is asc
   *      0: list order is desc
   */
  protected $_asc = null;
  /**
   * The active filter type. Filter type is always 'page' for ContentItemComments.
   *
   * @var string
   */
  protected $_filterType = null;
  /**
   * The filter text. Filter text is always the page id for ContentItemComments.
   *
   * @var int
   */
  protected $_filterText = null;

  /**
   * Construct
   */
  public function __construct($site_id, $page_id, Template $tpl, db $db, $table_prefix, $action = '',
                              $page_path = '', User $user = null, Session $session = null, $navigation)
  {
    parent::__construct($site_id, $page_id, $tpl, $db, $table_prefix, $action,
                        $page_path, $user, $session, $navigation);

    $this->_blog = new GeneralBlog($this->db, $this->table_prefix, $this->site_id, $this, $this->_user, 'co',
                            $this->_published, $this->_canceled, $this->_deleted);
    $this->_initPreferences();
  }

  public function get_content($params = array())
  {
    $this->_blog->trashComments();

    $this->_blog->approveComments();

    $this->_blog->newComment();

    $get = new Input(Input::SOURCE_GET);

    if ($get->exists('form'))
    {
      if ($get->readString('form') == GeneralBlog::ACTION_REPLY) {
        return $this->_replyForm();
      }
      else if ($get->readString('form') == GeneralBlog::ACTION_EDIT) {
        return $this->_editForm();
      }
    }

    return $this->_showList();
  }

  /**
   * Show contents in a list
   */
  private function _showList()
  {
    global $_LANG, $_LANG2;

    // read message from session in case of a redirect and reset the message
    // session variable.
    if ($this->session->read('co_message_failure'))
    {
      $this->setMessage(Message::createSuccess($this->session->read('co_message_failure')));
      $this->session->reset('co_message_failure');
    }
    if ($this->session->read('co_message_success'))
    {
      $this->setMessage(Message::createSuccess($this->session->read('co_message_success')));
      $this->session->reset('co_message_success');
    }

    // initialize the config variables
    $titleSeparator = ConfigHelper::get('hierarchical_title_separator', 'bl');
    $dateFormat = $this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'bl');

    $resultsPage = $this->_offset;

    $order = $this->_order;
    $asc = $this->_asc;
    $urlOrder = "order=$order&amp;asc=$asc";

    $filterType = $this->_filterType;
    $filterText = $this->_filterText;
    $urlFilter = "filter_type=$filterType&amp;filter_text=" . urlencode($filterText);

    // read total amount of comments
    $commentsCount = $this->_blog->getCommentsCount($filterType, $filterText);
    // handle paging
    $resultsPerPage = (int)ConfigHelper::get('bl_results_per_page');
    $bmPageNavigation = "";
    $offset = 0;
    if ($commentsCount > $resultsPerPage)
    {
      $pagelink = "index.php?action=comments&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;$urlOrder&amp;$urlFilter&amp;offset=";
      $bmPageNavigation = create_page_navigation($commentsCount, $resultsPage, 5, $resultsPerPage, $_LANG["global_results_showpage_current"], $_LANG["global_results_showpage_other"], $pagelink);
      $offset = (($resultsPage - 1) * $resultsPerPage);
    }
    $urlPage = "offset=$resultsPage";
    // Store all necessary url parameters for preserving site, page, paging,
    // list filter and list order.
    $urlPart = "site={$this->site_id}&amp;page={$this->page_id}&amp;$urlPage&amp;$urlFilter&amp;$urlOrder";

    // handle ordering
    // column author
    $sortPreference = array_keys($this->_blog->listOrders["author"]);
    $bmListAuthorSort = $order == "author" ? ($asc ? "asc" : "desc") : "none";
    $bmListAuthorSortNext = $order == "author" ? ($asc ? "desc" : "asc") : $sortPreference[0];
    $bmListAuthorLink = "index.php?action=comments&amp;site=$this->site_id&amp;page={$this->page_id}"
                      . "&amp;$urlPage&amp;$urlFilter&amp;order=author&amp;asc="
                      . ($order == 'author' ? ($asc ? 0 : 1) : intval($sortPreference[0] == 'asc'));
    // column email
    $sortPreference = array_keys($this->_blog->listOrders["email"]);
    $bmListEmailSort = $order == "email" ? ($asc ? "asc" : "desc") : "none";
    $bmListEmailSortNext = $order == "email" ? ($asc ? "desc" : "asc") : $sortPreference[0];
    $bmListEmailLink = "index.php?action=comments&amp;site=$this->site_id&amp;page={$this->page_id}"
                     . "&amp;$urlPage&amp;$urlFilter&amp;order=email&amp;asc="
                     . ($order == "email" ? ($asc ? 0 : 1) : intval($sortPreference[0] == "asc"));

    // column time
    $sortPreference = array_keys($this->_blog->listOrders["time"]);
    $bmListTimeSort = $order == "time" ? ($asc ? "asc" : "desc") : "none";
    $bmListTimeSortNext = $order == "time" ? ($asc ? "desc" : "asc") : $sortPreference[0];
    $bmListTimeLink = "index.php?action=comments&amp;site=$this->site_id&amp;page={$this->page_id}"
                    . "&amp;$urlPage&amp;$urlFilter&amp;order=time&amp;asc="
                    . ($order == "time" ? ($asc ? 0 : 1) : intval($sortPreference[0] == "asc"));

    // Display the new comment area if there aren't any comments available.
    $coDisplayNewComment = 1;
    // read all comments
    //$comments = $this->_getComments($order, $asc, $filterType, $filterText, $resultsPerPage, $offset);
    $comments = $this->_blog->getComments($order, $asc, $filterType, $filterText, $resultsPerPage, $offset);
    $commentItems = array();
    if ($comments)
    {
      // Do not display the new comment area if there are comments posted for the
      // current content item.
      $coDisplayNewComment = 0;

      $count = 0;
      foreach ($comments as $comment)
      {
        $count++;
        $commentItems[] = array (
          'co_comment_id'         => intval($comment['CID']),
          'co_comment_author'     => parseOutput($comment['CAuthor']),
          'co_comment_email'      => parseOutput($comment['CEmail']),
          'co_comment_createdatetime' => date($dateFormat, ContentBase::strToTime($comment['CCreateDateTime'])),
          'co_comment_shorttext'  => parseOutput($comment['CShortText']),
          'co_comment_title'      => parseOutput($comment['CTitle']),
          'co_comment_text'       => nl2br(parseOutput($comment['CText'])),
          'co_comment_approved'   => $comment['CPublished'] ? 1 : 0,
          'co_comment_status_cls' => $comment['CPublished'] ? 'published' : 'warning',
          'co_comment_status'     => $this->_getCommentStatus($comment['CPublished']),
          // there is no reply available for comments that are already replied,
          // are a reply themselves or have not been published yet
          'co_reply_available'    => ($comment['Reply'] || $comment['FK_CID']) ? 0 : 1,
          'co_comment_type'       => $comment['Reply'] ? 'replied' : ($comment['FK_CID'] ? 'reply' : 'comment'),
          'co_approve_link'       => "index.php?action=comments&amp;g_blg_aid={$comment['CID']}&amp;$urlPart",
          'co_edit_link'          => "index.php?action=comments&amp;form=edit&amp;g_blg_comment={$comment['CID']}&amp;$urlPart",
          'co_reply_link'         => "index.php?action=comments&amp;form=reply&amp;g_blg_comment={$comment['CID']}&amp;$urlPart",
          'co_trash_link'         => "index.php?action=comments&amp;g_blg_tid={$comment['CID']}&amp;$urlPart",
          'co_delete_link'        => "index.php?action=comments&amp;g_blg_did={$comment['CID']}&amp;$urlPart",
        );
      }
    }
    else {
      $this->setMessage(Message::createFailure($_LANG['co_message_no_items_failure']));
    }

    $coHiddenFields = '<input type="hidden" name="g_blg_comment_page" value="'.$this->page_id.'"/>';//var_dump($_POST);exit;
    /**
     * Set the title and text inside the new comment area, where a new comment
     * can be posted, if posting a comment failed. Also display the area.
     */
    $coNewCommentTitle = '';
    $coNewCommentText = '';

    if (isset($_POST['g_blg_comment_title']) || isset($_POST['g_blg_comment_text'])) {
      $coDisplayNewComment = 1;
    }
    else if (
      (isset($_POST['g_blg_comment_title']) && $_POST['g_blg_comment_title'] ) ||
      (isset($_POST['g_blg_comment_text']) && $_POST['g_blg_comment_text'] )
    ) {
      $coDisplayNewComment = 1;
      $coNewCommentTitle = $_POST['g_blg_comment_title'];
      $coNewCommentText = $_POST['g_blg_comment_text'];
    }

    // Fuctions (edit, reply, trash, write a new comment) are available if the
    // user has permission to the blog function (module) and it is activated for
    // the current content item.
    $available = $this->_user->AvailableModule('blog', $this->site_id);
    $userAvailable = $available ? 1 : 0;
    if ($available)
    {
      // User has permission to blog function, so check if it is activated.
      $sql = ' SELECT CBlog '
           . " FROM {$this->table_prefix}contentitem "
           . " WHERE CIID = $this->page_id ";
      $available = $this->db->GetOne($sql);
    }

    $titleAvailable = ConfigHelper::get('bl_title_available');

    // parse template
    $this->tpl->load_tpl('content_colist', 'content_comments.tpl');
    $this->tpl->parse_if('content_colist', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('co'));
    $this->tpl->parse_if('content_colist', "filter_set", $filterText);

    /**
     * Always parse available actions (buttons) before parsing the comments loop
     * except the reply and approve action as they might be available only for
     * certain comments.
     */
    $this->tpl->parse_if('content_colist', 'comment_action_'.GeneralBlog::ACTION_EDIT,
                         in_array(GeneralBlog::ACTION_EDIT, $this->_commentActions) && $available);
    $this->tpl->parse_if('content_colist', 'comment_action_'.GeneralBlog::ACTION_TRASH,
                         in_array(GeneralBlog::ACTION_TRASH, $this->_commentActions) && $available);
    $this->tpl->parse_if('content_colist', 'comment_action_new', $available);
    $this->tpl->parse_if("content_colist", 'comment_title_available', $titleAvailable);
    $this->tpl->parse_if("content_colist", 'comment_title_available', $titleAvailable);
    $this->tpl->parse_if("content_colist", 'comment_title_available', $titleAvailable);

    $this->tpl->parse_loop('content_colist', $commentItems, 'comment_items');

    // reply action handling & approve action
    foreach ($commentItems as $commentItem)
    {
      // if the comment is a standard comment without a reply to it show the
      // reply action button
      $this->tpl->parse_if('content_colist', 'comment_action_'.GeneralBlog::ACTION_REPLY.$commentItem['co_comment_id'],
                         in_array(GeneralBlog::ACTION_REPLY, $this->_commentActions) &&
                         $commentItem['co_reply_available'] == 1 && $available);
      // if the comment is a reply itself or it has been replied, do not show the reply button
      $this->tpl->parse_if('content_colist', 'comment_action_'.GeneralBlog::ACTION_REPLY.'_not_available'.$commentItem['co_comment_id'],
                         in_array(GeneralBlog::ACTION_REPLY, $this->_commentActions) &&
                         $commentItem['co_reply_available'] == 0 && $available);

      // if the comment isn't published (approved) show the approve action button
      $this->tpl->parse_if('content_colist', 'comment_action_'.GeneralBlog::ACTION_APPROVE.$commentItem['co_comment_id'],
                         in_array(GeneralBlog::ACTION_APPROVE, $this->_commentActions) &&
                         $commentItem['co_comment_approved'] == 0 && $available);
      // if the comment is already published (approved) do not show the approve action button
      $this->tpl->parse_if('content_colist', 'comment_action_'.GeneralBlog::ACTION_APPROVE.'_not_available'.$commentItem['co_comment_id'],
                         in_array(GeneralBlog::ACTION_APPROVE, $this->_commentActions) &&
                         $commentItem['co_comment_approved'] == 1 && $available);
    }

    // Message informs the user why the comment functions are not available
    $this->tpl->parse_if('content_colist', 'comment_actions_not_available', !$available, array(
      'co_comments_inactive_status' => $_LANG["co_comments_inactive$userAvailable"],
    ));
    $this->tpl->parse_if('content_colist', "more_pages", $bmPageNavigation, array(
      'co_page_navigation'       => $bmPageNavigation,
    ));
    $content = $this->tpl->parsereturn('content_colist', array_merge(array (
      'co_action'              => "index.php?action=comments&amp;$urlPart",
      'co_hidden_fields'       => $coHiddenFields,
      'co_function_label'      => $_LANG["co_function_label"],
      'co_function_new_label'  => $_LANG['co_function_new_label'],
      'co_newcomment_title'    => $coNewCommentTitle,
      'co_newcomment_text'     => $coNewCommentText,
      'co_newcomment_display'  => $coDisplayNewComment,
      'co_list_nick_sort'      => $bmListAuthorSort,
      'co_list_nick_sort_next' => $bmListAuthorSortNext,
      'co_list_nick_link'      => $bmListAuthorLink,
      'co_list_email_sort'     => $bmListEmailSort,
      'co_list_email_sort_next'=> $bmListEmailSortNext,
      'co_list_email_link'     => $bmListEmailLink,
      'co_list_time_sort'      => $bmListTimeSort,
      'co_list_time_sort_next' => $bmListTimeSortNext,
      'co_list_time_link'      => $bmListTimeLink,
    ), $_LANG2['global']));

    $contentTop = $this->_getContentTop(self::ACTION_COMMENTS);

    return array('content' => $content, 'content_left' => '', 'content_top' => $contentTop, 'content_contenttype' => get_class($this));
  }

  /**
   * Show a form for editing a comment
   */
  protected function _editForm()
  {
    global $_LANG, $_LANG2;

    // edit blog
    $this->_blog->editComment();

    // If there was a comment edited and it has been stored successfully
    // redirect to the comment list.
    if ($this->_getMessage() && $this->_getMessage()->getType() == Message::TYPE_SUCCESS)
    {
      $this->session->save('co_message_success', $this->_getMessage()->getText());
      // create url parameters with &
      $urlPart = 'site=' . $this->site_id
               . '&page=' . $this->page_id
               . '&offset=' . $this->_offset
               . '&filter_type=' . $this->_filterType
               . '&filter_text=' . urlencode($this->_filterText)
               . '&order=' . $this->_order
               . '&asc=' . $this->_asc;
      header("Location: index.php?action=comments&$urlPart");
      exit();
    }
    $request = new Input(Input::SOURCE_REQUEST);
    $ID = $request->readInt('g_blg_comment');

    $dateFormat = $this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'bl');

    $sql = ' SELECT CID, CTitle, CText, CCreateDateTime, CAuthor, CEmail, CPublished '
         . " FROM {$this->table_prefix}comments "
         . " WHERE CID = {$ID} ";
    $row = $this->db->GetRow($sql);

    // create url parameters with &amp;
    $urlPart = 'site=' . $this->site_id
             . '&amp;page=' . $this->page_id
             . '&amp;offset=' . $this->_offset
             . '&amp;filter_type=' . $this->_filterType
             . '&amp;filter_text=' . urlencode($this->_filterText)
             . '&amp;order=' . $this->_order
             . '&amp;asc=' . $this->_asc;

    // parse template
    $this->tpl->load_tpl('content_comment', 'content_comments_edit.tpl');
    $this->tpl->parse_if('content_comment', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('co'));
    $this->tpl->parse_if("content_comment", 'comment_title_available', ConfigHelper::get('bl_title_available'));
    $content = $this->tpl->parsereturn('content_comment', array_merge(array (
      'co_action'          => "index.php?action=comments&amp;form=".GeneralBlog::ACTION_EDIT."&amp;g_blg_comment={$row['CID']}&amp;$urlPart",
      'co_action_cancel'   => "index.php?action=comments&amp;g_blg_comment={$row['CID']}&amp;$urlPart",
      'co_function_label'  => $_LANG['co_function_'.GeneralBlog::ACTION_EDIT.'_label'],
      'co_function_label2' => $_LANG['co_function_'.GeneralBlog::ACTION_EDIT.'_label2'],
      'co_comment_title'   => $row['CTitle'],
      'co_comment_text'    => $row['CText'],
      'co_comment_date'    => date($dateFormat, ContentBase::strToTime($row['CCreateDateTime'])),
      'co_comment_author'  => $row['CAuthor'],
      'co_comment_email'   => $row['CEmail'],
      'co_comment_status'  => $this->_getCommentStatus($row['CPublished']),
    ), $_LANG2['global']));

    $contentTop = $this->_getContentTop(self::ACTION_COMMENTS);

    return array( "content" => $content, "content_left" => '', "content_top" => $contentTop, "content_contenttype" => get_class($this));
  }

  /**
   * Show a form for replying a comment
   */
  protected function _replyForm()
  {
    global $_LANG, $_LANG2;

    // reply to a comment
    $this->_blog->replyComment();

    // If there was a reply written and it has been stored successfully
    // redirect to the comment list.
    if ($this->_getMessage() && $this->_getMessage()->getType() == Message::TYPE_SUCCESS)
    {
      $this->session->save('co_message_success', $this->_getMessage()->getText());
      // create url parameters with &
      $urlPart = 'site=' . $this->site_id
               . '&page=' . $this->page_id
               . '&offset=' . $this->_offset
               . '&filter_type=' . $this->_filterType
               . '&filter_text=' . urlencode($this->_filterText)
               . '&order=' . $this->_order
               . '&asc=' . $this->_asc;
      header("Location: index.php?action=comments&$urlPart");
      exit();
    }

    $request = new Input(Input::SOURCE_REQUEST);
    $ID = $request->readInt('g_blg_comment');

    $dateFormat = $this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'bl');

    $sql = ' SELECT CID, FK_CIID, CTitle, CText, CCreateDateTime, CAuthor, CEmail, CPublished '
         . " FROM {$this->table_prefix}comments "
         . " WHERE CID = {$ID} ";
    $row = $this->db->GetRow($sql);

    // create url parameters with &amp;
    $urlPart = 'site=' . $this->site_id
             . '&amp;page=' . $this->page_id
             . '&amp;offset=' . $this->_offset
             . '&amp;filter_type=' . $this->_filterType
             . '&amp;filter_text=' . urlencode($this->_filterText)
             . '&amp;order=' . $this->_order
             . '&amp;asc=' . $this->_asc;
    $titleAvailable = ConfigHelper::get('bl_title_available');

    // parse template
    $this->tpl->load_tpl('content_comment', 'content_comments_reply.tpl');
    $this->tpl->parse_if('content_comment', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('co'));
    $this->tpl->parse_if("content_comment", 'comment_title_available', $titleAvailable);
    $this->tpl->parse_if("content_comment", 'comment_title_available', $titleAvailable);
    $content = $this->tpl->parsereturn('content_comment', array_merge(array (
      'co_action'          => "index.php?action=comments&amp;form=".GeneralBlog::ACTION_REPLY."&amp;g_blg_comment={$row['CID']}&amp;$urlPart",
      'co_action_cancel'   => "index.php?action=comments&amp;g_blg_comment={$row['CID']}&amp;$urlPart",
      'co_function_label'  => $_LANG['co_function_'.GeneralBlog::ACTION_REPLY.'_label'],
      'co_function_label2' => $_LANG['co_function_'.GeneralBlog::ACTION_REPLY.'_label2'],
      'co_comment_title'   => parseOutput($row['CTitle']),
      'co_comment_text'    => nl2br(parseOutput($row['CText'])),
      'co_comment_date'    => date($dateFormat, ContentBase::strToTime($row['CCreateDateTime'])),
      'co_comment_author'  => $row['CAuthor'],
      'co_comment_email'   => $row['CEmail'],
      'co_comment_status'  => $this->_getCommentStatus($row['CPublished']),
      'co_reply_comment_title' => isset($_POST['g_blg_comment_title']) ? $_POST['g_blg_comment_title'] : '',
      'co_reply_comment_text'  => isset($_POST['g_blg_comment_text']) ? $_POST['g_blg_comment_text'] : '',
    ), $_LANG2['global']));

    $contentTop = $this->_getContentTop(self::ACTION_COMMENTS);

    return array( "content" => $content, "content_left" => '', "content_top" => $contentTop, "content_contenttype" => get_class($this));
  }


  /**
   * Initialize all paging, filtering and ordering variables. This method
   * is called from the constructor in order to initialize all necessary
   * variables.
   */
  private function _initPreferences()
  {
    // initialize paging
    $this->_offset = 1;
    if (isset($_GET["offset"]) && intval($_GET["offset"])) {
      $this->_offset = intval($_GET["offset"]);
    }

    // initialize ordering
    $listOrderKeys = array_keys($this->_blog->listOrders);
    $this->_order = isset($_GET["order"], $this->_blog->listOrders[$_GET["order"]]) ? $_GET["order"] : $listOrderKeys[0];
    $this->_asc = isset($_GET["asc"]) ? intval($_GET["asc"]) : 1;

    // initialize filtering
    $this->_filterType = 'page';
    $this->_filterText = $this->page_path;
  }

  /**
   * Get the comment status label.
   * - published
   * - unpublished
   *
   * @param int $published
   *        The CPublished flag from database.
   * @return string
   */
  private function _getCommentStatus($published)
  {
    global $_LANG;

    $showUnpublishedComments = ConfigHelper::get('m_show_unpublished_comments', '', $this->site_id);
    $status = '';
    if ($published || $showUnpublishedComments) {
      $status = $_LANG['co_comment_status_published'];
    }
    else {
      $status = $_LANG['co_comment_status_unpublished'];
    }

    return $status;
  }
}
