<?php
/**
 * Plugin Name: Pinata Uploader
 * Plugin URI: https://example.com/pinata-uploader
 * Description: A plugin that uploads blog posts to the Pinata API.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

// Define the function that will handle the post upload
function pinata_upload_post($post_id) {
  // Get the post data
  $post = get_post($post_id);

  // Set the API endpoint URL
  $url = 'https://api.pinata.cloud/pinning/pinJSONToIPFS';

  // Set the API key and secret
  $api_key = '1ad2be03714e995a727c';
  $api_secret = '1877c841a0fa09d8450b5095c6a0ade06aee34d09518084447b8a6b45b162046';

  // Set the post data
  $data = array(
    'pinataOptions' => array(
      'cidVersion' => 1
    ),
    'pinataContent' => array(
      'title' => $post->post_title,
      'content' => $post->post_content,
    )
  );

  // Set the API headers
  $headers = array(
    'Content-Type' => 'application/json',
    'pinata_api_key' => $api_key,
    'pinata_secret_api_key' => $api_secret,
  );

  // Make the HTTP request
  $response = wp_remote_post($url, array(
    'headers' => $headers,
    'body' => json_encode($data),
  ));

  // Check the response status code
  if (wp_remote_retrieve_response_code($response) == 200) {
    // Success!
    $response_body = wp_remote_retrieve_body($response);
    $hash = json_decode($response_body)->IpfsHash;
    update_post_meta($post_id, 'pinata_hash', $hash);
    echo '<div class="notice notice-success"><p>Post uploaded to Pinata successfully! CID: ' . $hash . '</p></div>';
  } else {
    // Error uploading post
    $error_message = wp_remote_retrieve_response_message($response);
    echo '<div class="notice notice-error"><p>Error uploading post to Pinata: ' . $error_message . '</p></div>';
  }
}

// Add a meta box to the post editor screen
function pinata_meta_box() {
  add_meta_box(
    'pinata_meta_box',
    'Pinata Uploader',
    'pinata_meta_box_callback',
    'post',
    'side',
    'high'
  );
}
add_action('add_meta_boxes', 'pinata_meta_box');

// Callback function to display the meta box
function pinata_meta_box_callback($post) {
  // Add a nonce field for security
  wp_nonce_field('pinata_meta_box', 'pinata_meta_box_nonce');
  

  // Output the logo and form
  echo '<img src="https://www.pinata.cloud/images/logo.svg" alt="Pinata logo" width="200"><br><br>';
  echo '<form method="post">';
  echo '<p><input type="submit" name="pinata_upload_post" class="button" value="Upload to Pinata"></p>';
  echo '</form>';
}

// Save the uploaded post to Pinata when the form is submitted
function pinata_save_post($post_id) {
  // Check if the pinata_upload_post form has been submitted
  if (!isset($_POST['pinata_upload_post'])) {
    return;
  }

  // Verify the nonce for security
  if (!isset($_POST['pinata_upload_post_nonce']) || !wp_verify_nonce($_POST['pinata_upload_post_nonce'], 'pinata_upload_post_nonce')) {
    return;
  }

  // Upload the post to Pinata
  pinata_upload_post($post_id);
}
add_action('publish_post', 'pinata_save_post');


// Add the Pinata upload form to the post editor page
function pinata_add_upload_form() {
  global $post;
  $pinata_nonce = wp_create_nonce('pinata_upload_post_nonce');
  ?>
  <div class="pinata-upload">
    <h2>Upload to Pinata</h2>
    <form method="post">
      <?php wp_nonce_field('pinata_upload_post_nonce', 'pinata_upload_post_nonce'); ?>
      <input type="hidden" name="post_id" value="<?php echo $post->ID; ?>">
      <input type="submit" name="pinata_upload_post" class="button" value="Upload to Pinata">
    </form>
  </div>
  <?php
}
add_action('post_submitbox_misc_actions', 'pinata_add_upload_form'); 

// Display the Pinata CID in the post editor after the post is published
function pinata_display_cid() {
  global $post;
  $hash = get_post_meta($post->ID, 'pinata_hash', true);
  if (!empty($hash)) {
    echo '<div class="notice notice-success"><p>File uploaded to Pinata successfully! CID: ' . $hash . '</p></div>';
  }
}
add_action('edit_form_after_title', 'pinata_display_cid'); 

?>
