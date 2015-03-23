<?php get_header(); ?>
<?php
$profileuser = $current_user = wp_get_current_user();
$user_id = (int) $current_user->ID;

if ( ! $user_id ){
	wp_die(__( 'Invalid user ID.' ) );
}
?>
<?php echo do_shortcode( '[wppb-edit-profile]' );?>
<button type="button" onclick="window.location='<?php echo home_url();?>';"><?php _e('Cancel', GeoSets::CONTENT);?></button>
<?php do_action('showTable');?>
<?php get_footer(); ?>
