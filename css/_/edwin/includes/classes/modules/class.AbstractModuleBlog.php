<?php

/**
 * Blog Module Class
 *
 * All ModuleManagement module classes should extend this class.
 *
 * $LastChangedDate: 2018-03-14 09:11:50 +0100 (Mi, 14 Mrz 2018) $
 * $LastChangedBy: ham $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2010 Q2E GmbH
 */
abstract class AbstractModuleBlog extends Module
{
  /**
   * Stores actions allowed for comment handling.
   *
   * @var array
   */
  protected $_commentActions = array();
  /**
   * Module prefix
   *
   * @var string
   */
  protected $_prefix = '';
  /**
   * Shortname used in the 'action2' url parameter for module subclasses.
   *
   * i.e. For subclasses of ModuleBlogManagement $_moduleAction should be set.
   *      ModuleBlogManagementApproved::$_moduleAction = 'approved'
   *      ModuleBlogManagementTrash::$_moduleAction = 'trash'
   *
   * @var string
   */
  protected $_moduleAction = '';
  /**
   * The published database value. (Used with database queries)
   *
   * @var int 0 | 1
   */
  protected $_published = null;
  /**
   * The canceled database value. (Used with database queries)
   *
   * @var int 0 | 1
   */
  protected $_canceled = null;
  /**
   * The deleted database value. (Used with database queries)
   *
   * @var int 0 | 1
   */
  protected $_deleted = null;
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
   * The active filter type.
   *
   * @var string
   */
  protected $_filterType = null;
  /**
   * The filter text.
   *
   * @var string
   */
  protected $_filterText = null;
  /**
   * Construct
   */
  public function __construct($allSites, $site_id, Template $tpl, db $db, $table_prefix,
                              $action = '', $item_id = '', User $user = null,
                              Session $session = null, Navigation $navigation, $originalAction = '')
  {
    parent::__construct($allSites, $site_id, $tpl, $db, $table_prefix, $action,
                        $item_id, $user, $session, $navigation, $originalAction);

    $this->_blog = new GeneralBlog($this->db, $this->table_prefix, $this->site_id, $this, $this->_user, 'bl',
                            $this->_published, $this->_canceled, $this->_deleted);
    $this->_initPreferences();
  }

  public function sendResponse($request)
  {
    switch ($request) {
      case 'ContentItemCommentsAutoComplete':
        $this->_sendResponseContentItemCommentsAutoComplete();
        break;
      default:
        break;
    }

    parent::sendResponse($request);
  }

  /**
   * Show contents in a list
   */
  protected function _showList()
  {
    global $_LANG, $_LANG2;

    // read message from session in case of a redirect and reset the message
    // session variable.
    if ($this->session->read('bl_message_failure'))
    {
      $this->setMessage(Message::createSuccess($this->session->read('bl_message_failure')));
      $this->session->reset('bl_message_failure');
    }
    if ($this->session->read('bl_message_success'))
    {
      $this->setMessage(Message::createSuccess($this->session->read('bl_message_success')));
      $this->session->reset('bl_message_success');
    }

    $action2 = $this->_moduleAction ? "&amp;action2={$this->_moduleAction}" :
                                      "&amp;action2=main";

    // initialize the config variables
    $titleSeparator = ConfigHelper::get('hierarchical_title_separator', 'bl');
    $dateFormat = $this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'bl');
    $resultsPerPage = (int)ConfigHelper::get('bl_results_per_page');

    $resultsPage = $this->_offset;

    $order = $this->_order;
    $asc = $this->_asc;
    $urlOrder = "order=$order&amp;asc=$asc";

    $filterType = $this->_filterType;
    $filterText = $this->_filterText;

    $urlFilter = "filter_type=$filterType&amp;filter_text=" . urlencode($filterText);
    $filtermessage = "";

    // read total amount of comments
    $commentsCount = $this->_blog->getCommentsCount($filterType, $filterText);
    // handle paging
    $bmPageNavigation = "";
    $offset = 0;
    if ($commentsCount > $resultsPerPage)
    {
      $pagelink = "index.php?action=mod_blogmgmt{$action2}&amp;site={$this->site_id}&amp;$urlOrder&amp;$urlFilter&amp;offset=";
      $bmPageNavigation = create_page_navigation($commentsCount, $resultsPage, 5, $resultsPerPage, $_LANG["global_results_showpage_current"], $_LANG["global_results_showpage_other"], $pagelink);
      $offset = (($resultsPage - 1) * $resultsPerPage);
    }
    $urlPage = "offset=$resultsPage";
    // Store all necessary url parameters for preserving site, paging, list filter and
    // list order.
    $urlPart = "site={$this->site_id}&amp;$urlPage&amp;$urlFilter&amp;$urlOrder";

    // handle ordering
    // column page
    $sortPreference = array_keys($this->_blog->listOrders["page"]);
    $bmListPageSort = $order == "page" ? ($asc ? "asc" : "desc") : "none";
    $bmListPageSortNext = $order == "page" ? ($asc ? "desc" : "asc") : $sortPreference[0];
    $bmListPageLink = "index.php?action=mod_blogmgmt{$action2}&amp;"
                     . "site=$this->site_id&amp;$urlPage&amp;$urlFilter&amp;order=page&amp;asc="
                     .($order == 'page' ? ($asc ? 0 : 1) : intval($sortPreference[0] == 'asc'));

    // column author
    $sortPreference = array_keys($this->_blog->listOrders["author"]);
    $bmListAuthorSort = $order == "author" ? ($asc ? "asc" : "desc") : "none";
    $bmListAuthorSortNext = $order == "author" ? ($asc ? "desc" : "asc") : $sortPreference[0];
    $bmListAuthorLink = "index.php?action=mod_blogmgmt{$action2}&amp;"
                     . "site=$this->site_id&amp;$urlPage&amp;$urlFilter&amp;order=author&amp;asc="
                     .($order == 'author' ? ($asc ? 0 : 1) : intval($sortPreference[0] == 'asc'));
    // column email
    $sortPreference = array_keys($this->_blog->listOrders["email"]);
    $bmListEmailSort = $order == "email" ? ($asc ? "asc" : "desc") : "none";
    $bmListEmailSortNext = $order == "email" ? ($asc ? "desc" : "asc") : $sortPreference[0];
    $bmListEmailLink = "index.php?action=mod_blogmgmt{$action2}&amp;site={$this->site_id}&amp;"
                      . "$urlPage&amp;$urlFilter&amp;order=email&amp;asc="
                      .($order == "email" ? ($asc ? 0 : 1) : intval($sortPreference[0] == "asc"));

    // column time
    $sortPreference = array_keys($this->_blog->listOrders["time"]);
    $bmListTimeSort = $order == "time" ? ($asc ? "asc" : "desc") : "none";
    $bmListTimeSortNext = $order == "time" ? ($asc ? "desc" : "asc") : $sortPreference[0];
    $bmListTimeLink = "index.php?action=mod_blogmgmt{$action2}&amp;site={$this->site_id}&amp;"
                      . "$urlPage&amp;$urlFilter&amp;order=time&amp;asc="
                      .($order == "time" ? ($asc ? 0 : 1) : intval($sortPreference[0] == "asc"));

    // handle filtering
    // create filter dropdown
    $tempFilterSelect = '';
    foreach (array_keys($this->_blog->listFilters) as $filter)
    {
      $tempFilterSelect .= '<option value="'.$filter.'"';

      // create the javascript function calls in order to display different filter options
      if ($filter == 'page') {
        $tempFilterSelect .= "onclick=\"ed.modules.bl.ui.showFilter('page');\"";
      }
      else if ($filter == 'user') {
        $tempFilterSelect .= "onclick=\"ed.modules.bl.ui.showFilter('user');\"";
      }
      else {
        $tempFilterSelect .= "onclick=\"ed.modules.bl.ui.showFilter('text');\"";
      }

      if ($filterType == $filter) {
        $tempFilterSelect .= ' selected="selected"';
      }
      $tempFilterSelect .= '>'.$_LANG["bl_filter_type_$filter"].'</option>';
    }
    $bmFilterSelect = $tempFilterSelect;

    // read all comments
    //$comments = $this->_getComments($order, $asc, $filterType, $filterText, $resultsPerPage, $offset);
    $comments = $this->_blog->getComments($order, $asc, $filterType, $filterText, $resultsPerPage, $offset);
    $commentItems = array();
    if ($comments)
    {
      $count = 0;
      foreach ($comments as $comment)
      {
        $count++;
        $commentItems[] = array (
          'bl_comment_id'             => intval($comment['CID']),
          'bl_comment_page_title'     => parseOutput($this->_getHierarchicalTitle($comment['FK_CIID'], $titleSeparator)),
          'bl_comment_page'           => parseOutput($comment['PageTitle']),
          'bl_comment_page_link'      => 'index.php?action=comments&amp;site='.$comment['FK_SID'].'&amp;page='.$comment['FK_CIID'],
          'bl_comment_author'         => parseOutput($comment['CAuthor']),
          'bl_comment_email'          => parseOutput($comment['CEmail']),
          'bl_comment_createdatetime' => date($dateFormat, ContentBase::strToTime($comment['CCreateDateTime'])),
          'bl_comment_shorttext'      => parseOutput($comment['CShortText']),
          'bl_comment_title'          => parseOutput($comment['CTitle']),
          'bl_comment_text'           => nl2br(parseOutput($comment['CText'])),
          // there is no reply available for comments that are already replied,
          // are a reply themselves or have not been published yet
          'bl_reply_available'        => ($comment['Reply'] || $comment['FK_CID']) ? 0 : 1,
          'bl_comment_type'           => $comment['Reply'] ? 'replied' : ($comment['FK_CID'] ? 'reply' : 'comment'),
          'bl_approve_link'           => "index.php?action=mod_blogmgmt{$action2}&amp;g_blg_aid={$comment['CID']}&amp;$urlPart",
          'bl_edit_link'              => "index.php?action=mod_blogmgmt{$action2};edit&amp;g_blg_comment={$comment['CID']}&amp;$urlPart",
          'bl_reply_link'             => "index.php?action=mod_blogmgmt{$action2};reply&amp;g_blg_comment={$comment['CID']}&amp;$urlPart",
          'bl_trash_link'             => "index.php?action=mod_blogmgmt{$action2}&amp;g_blg_tid={$comment['CID']}&amp;$urlPart",
          'bl_delete_link'            => "index.php?action=mod_blogmgmt{$action2}&amp;g_blg_did={$comment['CID']}&amp;$urlPart",
        );
      }
    }

    /**
     * Create the page filter, which allows filtering comments of certain pages.
     */
    $users = $this->_blog->getUsers();
    $userData = array();
    foreach ($users as $userNick)
    {
      $selected = "";
      if ($filterType == 'user' && $userNick == $filterText) {
        $selected = 'selected="selected"';
      }

      $userData[] = array(
        'user_nick'     => $userNick,
        'user_selected' => $selected,
      );
    }

    /**
     * If the page filter is selected the id is stored in the $filterText variable.
     * Store the page title in $filterText in order to display it in the currently
     * selected filter section and set the filter visibility correctly.
     */
    $blTextFilterStyle = 'display:none;';
    $blPageFilterStyle = 'display:none;';
    $blUserFilterStyle = 'display:none;';
    if ($filterType == 'page' && $filterText)
    {
      // $filterText contains the content items path (CIIdentifier), try to get
      // the id of the content item (page) in order to get the hierarchical title.
      $sql = ' SELECT CIID '
           . " FROM {$this->table_prefix}contentitem "
           . " WHERE FK_SID = $this->site_id "
           . " AND CIIdentifier = '$filterText' ";
      $ciid = $this->db->GetOne($sql);
      // If the selected page is not valid, diplay the $filterText
      $realFilterText = $ciid ? $this->_getHierarchicalTitle($ciid, $titleSeparator) : $filterText;
      $blPageFilterStyle = '';
    }
    else if ($filterType == 'user' && $filterText)
    {
      // $filterText contains the user id
      $realFilterText = $filterText;
      $blUserFilterStyle = '';
    }
    // another filter selected
    else {
      $realFilterText = $filterText;
      $blTextFilterStyle = '';
    }

    $maxlength = (int)ConfigHelper::get('m_mod_filtertext_maxlength');
    $aftertext = ConfigHelper::get('m_mod_filtertext_aftertext');
    $shortFilterText = StringHelper::setText($realFilterText)
                       ->purge()
                       ->truncate($maxlength, $aftertext)
                       ->getText();

    // parse template
    $this->tpl->load_tpl('content_bmlist', 'modules/ModuleBlog_list.tpl');
    $this->tpl->parse_if('content_bmlist', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('bl'));
    $this->tpl->parse_if("content_bmlist", "filter_set", $filterText);
    $this->tpl->parse_if("content_bmlist", "filter_set", $filterText);

    // always parse available actions (buttons) before parsing the comments loop
    // except the reply action
    $this->tpl->parse_if("content_bmlist", 'comment_action_'.GeneralBlog::ACTION_APPROVE,
                         in_array(GeneralBlog::ACTION_APPROVE, $this->_commentActions));
    $this->tpl->parse_if("content_bmlist", 'comment_action_'.GeneralBlog::ACTION_DELETE,
                         in_array(GeneralBlog::ACTION_DELETE, $this->_commentActions));
    $this->tpl->parse_if("content_bmlist", 'comment_action_'.GeneralBlog::ACTION_EDIT,
                         in_array(GeneralBlog::ACTION_EDIT, $this->_commentActions));
    $this->tpl->parse_if("content_bmlist", 'comment_action_'.GeneralBlog::ACTION_TRASH,
                         in_array(GeneralBlog::ACTION_TRASH, $this->_commentActions));
    $this->tpl->parse_if("content_bmlist", 'comment_title_available', ConfigHelper::get('bl_title_available'));
    $this->tpl->parse_if("content_bmlist", 'comment_title_available', ConfigHelper::get('bl_title_available'));

    $this->tpl->parse_loop('content_bmlist', $commentItems, 'comment_items');

    // reply action handling
    foreach ($commentItems as $commentItem)
    {
      // if the comment is a standard comment without a reply to it show the
      // reply action button
      $this->tpl->parse_if("content_bmlist", 'comment_action_'.GeneralBlog::ACTION_REPLY.$commentItem['bl_comment_id'],
                         in_array(GeneralBlog::ACTION_REPLY, $this->_commentActions) && $commentItem['bl_reply_available'] == 1);
      // if the comment is a reply itself or it has been replied, do not show the reply button
      $this->tpl->parse_if("content_bmlist", 'comment_action_'.GeneralBlog::ACTION_REPLY.'_not_available'.$commentItem['bl_comment_id'],
                         in_array(GeneralBlog::ACTION_REPLY, $this->_commentActions) && $commentItem['bl_reply_available'] == 0);
    }

    $this->tpl->parse_loop('content_bmlist', $userData, 'user_items');
    $this->tpl->parse_if('content_bmlist', "more_pages", $bmPageNavigation, array(
      'bl_page_navigation'       => $bmPageNavigation,
    ));
    $content = $this->tpl->parsereturn('content_bmlist', array_merge(array (
      'bl_action'              => "index.php?action=mod_blogmgmt{$action2}&amp;$urlPart",
      'bl_function_list_label' => $_LANG["{$this->_prefix}_function_list_label"],
      'bl_function_list_label2'=> $_LANG["{$this->_prefix}_function_list_label2"],
      'bl_site_selection' => $this->_parseModuleSiteSelection('blogmgmt', $_LANG["{$this->_prefix}_site_label"]),
      'bl_filter_active_label' => $filterText ? sprintf($_LANG['bl_filter_active_label'], $_LANG["bl_filter_type_$filterType"], parseOutput($realFilterText), parseOutput($shortFilterText)) : $_LANG['bl_filter_inactive_label'],
      'bl_filter_text'         => $filterText,
      'bl_filter_type_select'  => $bmFilterSelect,
      'bl_list_page_sort'      => $bmListPageSort,
      'bl_list_page_sort_next' => $bmListPageSortNext,
      'bl_list_page_link'      => $bmListPageLink,
      'bl_list_nick_sort'      => $bmListAuthorSort,
      'bl_list_nick_sort_next' => $bmListAuthorSortNext,
      'bl_list_nick_link'      => $bmListAuthorLink,
      'bl_list_email_sort'     => $bmListEmailSort,
      'bl_list_email_sort_next'=> $bmListEmailSortNext,
      'bl_list_email_link'     => $bmListEmailLink,
      'bl_list_time_sort'      => $bmListTimeSort,
      'bl_list_time_sort_next' => $bmListTimeSortNext,
      'bl_list_time_link'      => $bmListTimeLink,
      'bl_textfilter_style'    => $blTextFilterStyle,
      'bl_pagefilter_style'    => $blPageFilterStyle,
      'bl_userfilter_style'    => $blUserFilterStyle,
      'bl_module_action_box'   => $this->_getContentActionBoxes(),
      'bl_autocomplete_contentitem_url' => "index.php?action=mod_response_blogmgmt$action2&site=$this->site_id&request=ContentItemCommentsAutoComplete",
    ), $_LANG2['bl']));

    return array(
        'content' => $content,
    );
  }

  /**
   * Show a form for replying or editing a comment
   */
  protected function _showForm()
  {
    if ($this->action[0] == GeneralBlog::ACTION_EDIT) {
      return $this->_editForm();
    }
    else if ($this->action[0] == GeneralBlog::ACTION_REPLY) {
      return $this->_replyForm();
    }
    // no special handling for given action - show list
    else {
      return $this->_showList();
    }
  }

  /**
   * Show a form for editing a comment
   */
  protected function _editForm()
  {
    global $_LANG, $_LANG2;

    $action2 = $this->_moduleAction ? "action2={$this->_moduleAction}" :
                                      "action2=main";

    // edit blog
    $this->_blog->editComment();

    // If there was a comment edited and it has been stored successfully
    // redirect to the comment list.
    if ($this->_getMessage() && ($this->_getMessage()->getType() == Message::TYPE_SUCCESS))
    {
      $this->session->save('bl_message_success', $this->_getMessage()->getText());
      // create url parameters with &
      $urlPart = 'site=' . $this->site_id
               . '&offset=' . $this->_offset
               . '&filter_type=' . $this->_filterType
               . '&filter_text=' . urlencode($this->_filterText)
               . '&order=' . $this->_order
               . '&asc=' . $this->_asc;
      header("Location: index.php?action=mod_blogmgmt&{$action2}&$urlPart");
      exit();
    }
    $action = $this->action[0];

    $request = new Input(Input::SOURCE_REQUEST);
    $ID = $request->readInt('g_blg_comment');

    $dateFormat = $this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'bl');

    $sql = ' SELECT CID, CTitle, CText, CCreateDateTime, CAuthor, CEmail '
         . " FROM {$this->table_prefix}comments "
         . " WHERE CID = {$ID} ";
    $row = $this->db->GetRow($sql);

    // create url parameters with &amp;
    $urlPart = 'site=' . $this->site_id
             . '&amp;offset=' . $this->_offset
             . '&amp;filter_type=' . $this->_filterType
             . '&amp;filter_text=' . urlencode($this->_filterText)
             . '&amp;order=' . $this->_order
             . '&amp;asc=' . $this->_asc;

    // parse template
    $this->tpl->load_tpl('content_comment', 'modules/ModuleBlog_edit.tpl');
    $this->tpl->parse_if('content_comment', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('bl'));
    $this->tpl->parse_if("content_comment", 'comment_title_available', ConfigHelper::get('bl_title_available'));
    $content = $this->tpl->parsereturn('content_comment', array_merge(array (
      'bl_action'          => "index.php?action=mod_blogmgmt&amp;{$action2};".GeneralBlog::ACTION_EDIT."&amp;g_blg_comment={$row['CID']}&amp;$urlPart",
      'bl_action_cancel'   => "index.php?action=mod_blogmgmt&amp;{$action2}&amp;g_blg_comment={$row['CID']}&amp;$urlPart",
      'bl_function_label'  => $_LANG['bl_function_'.$action.'_label'],
      'bl_function_label2' => $_LANG['bl_function_'.$action.'_label2'],
      'bl_comment_title'   => parseOutput($row['CTitle']),
      'bl_comment_text'    => parseOutput($row['CText']),
      'bl_comment_date'    => date($dateFormat, ContentBase::strToTime($row['CCreateDateTime'])),
      'bl_comment_author'  => parseOutput($row['CAuthor']),
      'bl_comment_email'   => parseOutput($row['CEmail']),
    ), $_LANG2['bl']));

    return array(
        'content' => $content,
    );
  }

  /**
   * Show a form for replying a comment
   */
  protected function _replyForm()
  {
    global $_LANG, $_LANG2;

    $action2 = $this->_moduleAction ? "action2={$this->_moduleAction}" :
                                      "action2=main";

    // reply to a comment
    $this->_blog->replyComment();

    // If there was a reply written and it has been stored successfully
    // redirect to the comment list.
    if ($this->_getMessage() && ($this->_getMessage()->getType() == Message::TYPE_SUCCESS))
    {
      $this->session->save('bl_message_success', $this->_getMessage()->getText());
      // create url parameters with &
      $urlPart = 'site=' . $this->site_id
               . '&offset=' . $this->_offset
               . '&filter_type=' . $this->_filterType
               . '&filter_text=' . urlencode($this->_filterText)
               . '&order=' . $this->_order
               . '&asc=' . $this->_asc;
      header("Location: index.php?action=mod_blogmgmt&{$action2}&$urlPart");
      exit();
    }

    $action = $this->action[0];

    $request = new Input(Input::SOURCE_REQUEST);
    $ID = $request->readInt('g_blg_comment');

    $dateFormat = $this->_configHelper->getDateTimeFormat($this->_user->getLanguage(), 'bl');

    $sql = ' SELECT CID, FK_CIID, CTitle, CText, CCreateDateTime, CAuthor, CEmail '
         . " FROM {$this->table_prefix}comments "
         . " WHERE CID = {$ID} ";
    $row = $this->db->GetRow($sql);

    // create url parameters with &amp;
    $urlPart = 'site=' . $this->site_id
             . '&amp;offset=' . $this->_offset
             . '&amp;filter_type=' . $this->_filterType
             . '&amp;filter_text=' . urlencode($this->_filterText)
             . '&amp;order=' . $this->_order
             . '&amp;asc=' . $this->_asc;

    // parse template
    $this->tpl->load_tpl('content_comment', 'modules/ModuleBlog_reply.tpl');
    $this->tpl->parse_if('content_comment', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('bl'));
    $this->tpl->parse_if("content_comment", 'comment_title_available', ConfigHelper::get('bl_title_available'));
    $this->tpl->parse_if("content_comment", 'comment_title_available', ConfigHelper::get('bl_title_available'));
    $content = $this->tpl->parsereturn('content_comment', array_merge(array (
      'bl_action'          => "index.php?action=mod_blogmgmt&amp;{$action2};".GeneralBlog::ACTION_REPLY."&amp;g_blg_comment={$row['CID']}&amp;$urlPart",
      'bl_action_cancel'   => "index.php?action=mod_blogmgmt&amp;{$action2}&amp;g_blg_comment={$row['CID']}&amp;$urlPart",
      'bl_function_label'  => $_LANG['bl_function_'.$action.'_label'],
      'bl_function_label2' => $_LANG['bl_function_'.$action.'_label2'],
      'bl_comment_title'   => parseOutput($row['CTitle']),
      'bl_comment_text'    => nl2br(parseOutput($row['CText'])),
      'bl_comment_date'    => date($dateFormat, ContentBase::strToTime($row['CCreateDateTime'])),
      'bl_comment_author'  => parseOutput($row['CAuthor']),
      'bl_comment_email'   => parseOutput($row['CEmail']),
      'bl_reply_comment_title' => isset($_POST['g_blg_comment_title']) ? $_POST['g_blg_comment_title'] : '',
      'bl_reply_comment_text'  => isset($_POST['g_blg_comment_text']) ? $_POST['g_blg_comment_text'] : '',
    ), $_LANG2['bl']));

    return array(
        'content' => $content,
    );
  }

  protected function _getContentActionBoxes($buttons = null)
  {
    global $_LANG;

    // if there aren't any actions defined
    if (empty($this->_commentActions)) {
      return '';
    }

    $prefix = $this->_prefix;

    $actionOptions = array();
    foreach ($this->_commentActions as $action) {

      /**
       * Do not display actions which are only called for single comments.
       * i.e. the 'edit' and 'reply' action
       */
      if (in_array($action, array('edit', 'reply'))) {
        continue;
      }

      $actionOptions[] = array(
        'module_action_option'       => $action,
        'module_action_option_label' => isset($_LANG["{$prefix}_option_{$action}_label"]) ?
                                              $_LANG["{$prefix}_option_{$action}_label"] :
                                              $_LANG["bl_option_{$action}_label"],
      );
    }

    /**
     * Once again check if there are any  action options available
     */
    if (empty($actionOptions)) {
      return '';
    }

    $this->tpl->load_tpl('module_action_box', 'modules/ModuleBlog_action_box.tpl');
    $this->tpl->parse_loop('module_action_box', $actionOptions, 'module_action_items');
    $actionBox = $this->tpl->parsereturn('module_action_box', array());

    return $actionBox;
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
    $request = new Input(Input::SOURCE_REQUEST);
    $listFilterKeys = array_keys($this->_blog->listFilters);
    $this->_filterType = coalesce($request->readString('filter_type'),
                           $this->session->read('bl_filter_type'),
                           $listFilterKeys[0]);
    if (!isset($this->_blog->listFilters[$this->_filterType])) {
      $this->_filterType = $listFilterKeys[0];
    }

    // if a page filter is selected set filter text value from page dropdown selection
    if ($this->_filterType == 'page') {
      $this->_filterText = $request->readString('filter_page');
    }
    // if a page filter is selected set filter text value from page dropdown selection
    else if ($this->_filterType == 'user') {
      $this->_filterText = $request->readString('filter_user');
    }
    // If filter_text was sent with the request it has to be used, even if it's empty.
    else if ($request->exists('filter_text')) {
      $this->_filterText = $request->readString('filter_text');
    }
    else {
      $this->_filterText = coalesce($this->session->read('bl_filter_text'), '');
    }

    /**
     * Reset the filter to author filter if there isn't selected a page for the
     * page filter or a user for the userfilter.
     */
    if (($this->_filterType == 'page' && !$this->_filterText) ||
        ($this->_filterType == 'user' && !$this->_filterText)) {
      $this->_filterType = $listFilterKeys[0];
      $this->_filterText = '';
    }

    $this->session->save('bl_filter_type', $this->_filterType);
    $this->session->save('bl_filter_text', $this->_filterText);
  }

  private function _sendResponseContentItemCommentsAutoComplete()
  {
    $get = new Input(Input::SOURCE_GET);

    $searchString = $get->readString('q');
    if (!$searchString) {
      echo Json::Encode(array());
      return;
    }

    $currentSite = $this->_navigation->getCurrentSite();

    $sql = ' SELECT ci.CIID, ci.CIIdentifier, ci.CTitle, CTClass, ci.FK_SID '
         . " FROM {$this->table_prefix}contentitem ci "
         . " JOIN {$this->table_prefix}contenttype ct "
         . '      ON ci.FK_CTID = ct.CTID '
         . " JOIN {$this->table_prefix}comments com "
         . '      ON com.FK_CIID = ci.CIID '
         . " WHERE FK_SID = {$currentSite->getID()} "
         . (($this->_published === 1 || $this->_published === 0) ? " AND CPublished = {$this->_published} " : '')
         . (($this->_canceled === 1 || $this->_canceled === 0) ? " AND CCanceled = {$this->_canceled} " : '')
         . (($this->_deleted === 1 || $this->_deleted === 0) ? " AND CDeleted = {$this->_deleted} " : '')
         . ' AND ( '
         . "   ci.CIIdentifier LIKE '%$searchString%' OR "
         . "   ci.CTitle LIKE '%$searchString%' "
         . ' ) '
         . ' GROUP BY ci.CIID, ci.CIIdentifier, ci.CTitle, CTClass, ci.FK_SID  ';
    $result = $this->db->query($sql);

    $searchResult = array();
    while ($row = $this->db->fetch_row($result)) {
      // There is only local site scope allowed.
      $siteScope = ScopeHelper::SCOPE_LOCAL;

      $searchResult[] = array(
        'id'          => (int)$row['CIID'],
        'identifier'  => $row['CIIdentifier'],
        'title'       => $row['CTitle'],
        'contenttype' => $row['CTClass'],
        'siteID'      => (int)$row['FK_SID'],
        'siteToken'   => ScopeHelper::getSiteToken((int)$row['FK_SID']),
        'siteScope'   => $siteScope,
      );
    }
    $this->db->free_result($result);
    $autoCompleteResultSorter = new AutoCompleteResultSorter($currentSite->getID());
    usort($searchResult, array($autoCompleteResultSorter, 'sortCallback'));

    header('Content-Type: application/json');

    echo Json::Encode($searchResult);
  }
}
