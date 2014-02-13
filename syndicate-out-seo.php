<?php
/* Plugin Name: Syndicate Out SEO
 * Version: 1.0
 * Author: PressLabs
 * Description: This plugin add SEO for the `Syndicate Out` WP plugin. It stores the category of cross-posts. All posts from this category will be redirected to the 404 Page. The permalink of this posts will be also rewrited with the original permalink from the source site.
 */

define( 'SYNDICATE_OUT_OPTIONS', 'so_options' );
define( 'SYNDICATE_OUT_SEO_OPTION', 'so_seo_category' );
define( 'SYNDICATE_OUT_SEO_META_KEY', 'so_original_permalink' );

//------------------------------------------------------------------------------
// Add the original permalink to the cross-posts. When the post will be cross posted
// with the `Syndicate Out` plugin, the original permalink will be propagated also
//
function so_seo_add_new_post_meta( $post_id ) {
  $so_options = get_option(SYNDICATE_OUT_OPTIONS, '-1');
  if ( '-1' != $so_options ) {
    $group = $so_options['group'];
    foreach ( $group as $item ) {
      $so_category = $item['category'];
      if ( has_category($so_category, $post_id) )
        add_post_meta($post_id, SYNDICATE_OUT_SEO_META_KEY, get_permalink( $post_id ) );
    }
  }
}
add_action('publish_post', 'so_seo_add_new_post_meta');

//------------------------------------------------------------------------------
// Rewrite the `permalink` of all cross-posts with the original permalink
//
function so_seo_filter_permalink( $permalink ) {
  $post_id = url_to_postid( $permalink );
  $new_permalink = get_post_meta( $post_id, SYNDICATE_OUT_SEO_META_KEY, true );
  if ( '' < $new_permalink ) $permalink = $new_permalink;

  return $permalink;
}
add_filter('the_permalink', 'so_seo_filter_permalink');

//------------------------------------------------------------------------------
// Redirect all cross-posts to the 404 Page
//
function so_seo_set_404() {
  global $wp_query;

  if ( is_single() ) // only in single post page
  {
    $so_seo_category = get_option( SYNDICATE_OUT_SEO_OPTION, '-1' );
    if ('-1' != $so_seo_category) // if there is one seo category then
      if ( has_category($so_seo_category, $wp_query->post->ID) ) // if the post has the seo category
        $wp_query->set_404(); // set the 404 Page template on this post
  }
}
add_action('wp','so_seo_set_404');

//------------------------------------------------------------------------------
// Add new option(404 Syndicate Category) to `Settings` page
// This option is used to get all cross-posts for SEO actions
//
class SO_SEO_General_Setting {
  function SO_SEO_General_Setting() {
    add_filter( 'admin_init' , array( &$this , 'register_fields' ) );
  }

  function register_fields() {
    register_setting( 'general', SYNDICATE_OUT_SEO_OPTION, 'esc_attr' );
    add_settings_field(
      SYNDICATE_OUT_SEO_OPTION,
      '<label for="' . SYNDICATE_OUT_SEO_OPTION . '">404 Syndicate Category</label>',
      array(&$this, 'fields_html'),
      'general'
    );
  }

  function fields_html() {
    $so_seo_category = get_option( SYNDICATE_OUT_SEO_OPTION, '' );
    if ( '' == $so_seo_category ) $so_seo_category = '-1';
    $all_categories = get_categories();
?>
    <select id="<?php print SYNDICATE_OUT_SEO_OPTION; ?>" name="<?php print SYNDICATE_OUT_SEO_OPTION; ?>">
      <option value="-1">No category</option>
      <?php foreach ( $all_categories as $category ) : ?>
      <?php $selected = ''; if ( $category->slug == $so_seo_category ) $selected = ' selected="selected"'; ?>
      <option <?php print $selected; ?>value="<?php print $category->slug; ?>"><?php print $category->name . " (" . $category->slug . ")"; ?></option>
      <?php endforeach; ?>
    </select>
    <p class="description">Select the category of cross-posts. All posts from this category will be redirected to 404 Page.</p>
<?php
  }
}
$new_general_setting = new SO_SEO_General_Setting();

