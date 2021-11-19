<?php
/**
 * Lang: EN
 *
 * $LastChangedDate: 2017-09-12 09:02:14 +0200 (Di, 12 Sep 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */
if (!isset($_LANG2['tg'])) $_LANG2['tg'] = array();

$_LANG = array_merge($_LANG, array(

  'tg_message_upload_missing_file_error' => 'The image upload failed because no picture was selected! Please select an image!',
  'tg_message_tag_error' => 'It could not create a new tag! Please enter a title!',
  'tg_message_tag_success' => 'A new tag has been created successfully!',
  'tg_message_tag_duplicate_title' => 'A tag with this title exists already (case insensitivity)!',

  "tg_message_fileupload_success" => "ZIP file was uploaded successfully. Please continue by adding the uploaded images to your gallery below.",
  "tg_message_zipfile_error" => "Error when opening the ZIP file!",
  "tg_message_process_zip_success" => "ZIP file is processed (%s of %s were successful).",
  "tg_message_process_zip_error" => "<br />The following errors were found: %sx incorrect resolution; %sx wrong dimension; %sx too large;.",
  "tg_message_process_zip_error_too_much_images" => "<br />The ZIP file's maximum amount of images was exceeded by %s (%s images allowed), redundant images were discarded.",
  "tg_message_insert_image_success" => "A new image has been inserted at position %s.",
  "tg_message_gallery_image_customdata_update_success" => "A specific title/text has been saved.",
  "tg_message_gallery_image_move_success" => "The image has been moved.",
  "tg_message_gallery_image_delete_success" => "The image has been deleted from the gallery.",
  "tg_message_gallery_image_customdata_delete_success" => "The specific title and text were deleted from the image.",
  "tg_message_gallery_images_delete_success_one" => "One image was deleted from the gallery.",
  "tg_message_gallery_images_delete_success_more" => "%s images were deleted from the gallery.",

  "tg_gallery_image_customdata_available_label" => "There exists a description for this image",
  "tg_gallery_image_customdata_not_available_label" => "No description available",

  "tg_file_extension_unknown_label" => "unknown",
  "tg_image_resolution" => "%s x %s",

  "end",""));

$_LANG2['tg'] = array_merge($_LANG2['tg'], array(

  "tg_boximage_data_label" => "Box-image",
  "tg_boximage_showhide_label" => "Show/hide area",
  "tg_common_label" => "Main area",
  "tg_common_showhide_label" => "Show/hide general layout area",
  "tg_common_actions_label" => "Main area actions",
  "tg_button_submit_label" => "Save",
  "tg_gallery_upload_zip_tab_label" => "Upload ZIP",
  "tg_gallery_upload_image_tab_label" => "Upload image",

  "tg_gallery_upload_label" => "Gallery-upload",
  "tg_gallery_upload_showhide_label" => "Show/hide upload area",

  "tg_gallery_upload_image_label" => "Further image",
  "tg_gallery_upload_image_position_label" => "Position of the new image",
  "tg_button_gallery_upload_image_label" => "Insert image",

  "tg_gallery_upload_zip_label" => "Enter a ZIP file!",
  "tg_button_gallery_upload_zip_label" => "Upload ZIP file",
  "tg_gallery_uploaded_zip_label" => "Uploaded ZIP file",
  "tg_gallery_uploaded_zip_position_label" => "Position",
  "tg_gallery_uploaded_zip_position_start_label" => "At the beginning",
  "tg_gallery_uploaded_zip_position_end_label" => "At the end",
  "tg_gallery_uploaded_zip_process_label" => "Insert images",

  "tg_gallery_image_customdata_subtitle_label" => "Image subtitle",
  "tg_gallery_image_customdata_title_label" => "Description title",
  "tg_gallery_image_customdata_text_label" => "Description text",
  "tg_gallery_image_tags_label"  => "Tags for the image",
  "tg_gallery_image_tags_placeholder" => "No tags yet. Click here to assign tags to this image.",
  'tg_gallery_image_save_label'  => 'Save',

  "tg_message_gallery_maximum_reached" => "You can insert any more pictures in the gallery, as the maximum number of images has been reached.",

  "tg_gallery_images_label" => "Existing images",
  "tg_gallery_images_showhide_label" => "Show/hide existing images",
  "tg_gallery_images_actions_label" => "Existing images Actions",
  "tg_gallery_images_markall_label" => "Highlight all images",
  "tg_gallery_images_unmarkall_label" => "Unmark all images",
  "tg_gallery_images_delete_question_label" => "Do you really want to delete the selected image(s)?",
  "tg_gallery_image_delete_label" => "Delete image",
  "tg_gallery_image_delete_question_label" => "Do you really want to delete this image?",
  "tg_gallery_image_customdata_label" => "Edit description",
  "tg_gallery_image_move_label" => "Move image",
  "tg_button_gallery_image_customdata_delete_label" => "Delete",
  "tg_button_gallery_image_customdata_save_label" => "Save",
  "tg_button_gallery_images_delete_label" => "Delete image(s)",
  "tg_button_gallery_image_customdata_cancel_label"  => "Cancel",

  // tags
  "tg_tag_add_label" => "Add tag",
  "tg_tag_remove_label" => "Delete tag",
  "tg_message_max_tags" => "No more tags allowed!",

  "end",""));

