<?php
/**
 * Lang: EN
 *
 * $LastChangedDate: 2017-08-23 08:29:24 +0200 (Mi, 23 Aug 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */

if (!isset($_LANG2['bl'])) $_LANG2['bl'] = array();

$_LANG = array_merge($_LANG,array(


  'bl_function_edit_label' => 'Edit comment',
  'bl_function_edit_label2' => 'Edit the data of the comment',
  'bl_function_reply_label' => 'Post comment reply',
  'bl_function_reply_label2' => 'Reply to a comment',

  // list
  "bl_filter_type_author"     => "Author",
  "bl_filter_type_email"      => "E-mail adress",
  "bl_filter_type_page"       => "Page",
  "bl_filter_type_user"       => "System user",
  "bl_filter_active_label"    => "<b>Active comment filter</b>: <span class=\"fontsize11\">%s <b>'%s'</b>...</span>",
  "bl_filter_inactive_label"  => "<i>No active comment filter</i>",

  // messages
  'bl_message_trashitems_success'  => 'Comment (s) successfully moved to the recycle bin!',
  'bl_message_deleteitems_success' => 'Comment (s) deleted irrevocably!',
  'bl_message_approveitems_success'=> 'Comment (s) published successfully !',
  'bl_message_approveitems_warning'=> 'Attention! One or more comments are responses to already replied comments and could not be published!',
  'bl_message_edititem_success'    => 'Comment successfully edited!',
  'bl_message_replyitem_success'   => 'Answer written successfully!',
  'bl_message_no_title_failure'    => 'No valid title specified!',
  'bl_message_no_text_failure'     => 'No valid content specified!',

  // actionbox
  'bl_option_approve_label' => 'Publish',
  'bl_option_trash_label'   => 'Trash',
  'bl_option_delete_label'  => 'Delete',

  "end",""));

$_LANG2['bl'] = array_merge($_LANG2['bl'], array(

  "bl_filter_label"             => "Filter",
  "bl_sort_link_info"           => "auf / absteigend sortieren",
  "bl_filter_reset_label"       => "Reset filter",
  "bl_show_change_filter_label" => "Edit filter",
  "bl_filter_text1"             => "To",
  "bl_filter_text2"             => "Contains",
  "bl_filter_no_page_selected"  => "No",
  "bl_filter_no_user_selected"  => "No",
  "bl_button_filter_label"      => "Filter",

  'bl_page_label'    => 'Page',
  'bl_author_label'  => 'Author',
  'bl_email_label'   => 'E-mail',
  'bl_time_label'    => 'Posted on',
  'bl_trash_label'   => 'Trash',
  'bl_approve_label' => 'Publish',
  'bl_delete_label'  => 'Delete',
  'bl_show_label'    => 'Show',
  'bl_edit_label'    => 'Edit',
  'bl_reply_label'   => 'Reply',
  'bl_reply_inactive_label'      => 'Not possible to reply',
  'bl_deleteitem_question_label' => 'Do you really want to delete this answer?',

  'bl_reply_comment_label' => 'Response',
  'bl_comment_title_label' => 'Title',
  'bl_comment_text_label'  => 'Content',

  'bl_button_edit_label'   => 'Edit',
  'bl_button_reply_label'  => 'Reply',
  'bl_button_cancel_label' => 'Cancel',

  // actionbox
  'bl_comment_action_label' => 'Marked comments',
  'bl_action_button_label'  => 'Implement',
  'bl_mark_label'           => 'Mark all comments',
  'bl_unmark_label'         => 'Unmark',

  // Legende
  "bl_caption_comment_replied_label"  => "Replied",
  "bl_caption_comment_reply_label"    => "Reply to a comment",


  "end",""));

