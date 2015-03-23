<?php get_header(); ?>
<?php
wp_enqueue_style( 'sets2', '/wp-admin/css/wp-admin.min.css' );
$profileuser = $current_user = wp_get_current_user();
$user_id = (int) $current_user->ID;

if ( ! $user_id ){
	wp_die(__( 'Invalid user ID.' ) );
}
?>
<div class="wrapper">
	<a href="<?php echo home_url();?>">home on map</a>
	<h1>User profile</h1>
	<?php echo do_shortcode( '[wppb-edit-profile]' );
	if(do_shortcode( '[wppb-edit-profile]' )){
	?><button type="button" onclick="window.location='<?php echo home_url();?>';"><?php _e('Cancel', GeoSets::CONTENT);?></button><?php
	}
	?>
	<?php do_action('showTable');?>
</div>

<?php get_footer(); ?>
