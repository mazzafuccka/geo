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
	<a href="<?php echo home_url();?>" title="<?php _e('Go to map', GeoSets::CONTENT); ?>"><?php _e('Main Page', GeoSets::CONTENT); ?></a>
	<h1><?php _e('User profile', GeoSets::CONTENT);?></h1>
	<?php echo do_shortcode( '[wppb-edit-profile]' );
	if(do_shortcode( '[wppb-edit-profile]' )){
	?><button type="button" onclick="window.location='<?php echo home_url();?>';"><?php _e('Cancel', GeoSets::CONTENT);?></button><?php
	}
	?>
	<?php do_action('showTable');?>

	<br><br>
	<?php 

		if ( is_user_logged_in() ) 
		{
			$userRole = ($current_user->caps);
			$role = key($userRole);
			unset($userRole);
			
			if (strstr($role, 'administrator') || strstr($role, 'editor'))
			{	
				do_action('showTable_devices');
				print "<br><br>\n\n";
				do_action('showTable_routes');
			} else print "$role is bad<br>\n";
		}
		?>
	<br><br>

</div>

<?php get_footer(); ?>
