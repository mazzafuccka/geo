<?php get_header(); ?>
<?php

$profileuser = $current_user = wp_get_current_user();
$user_id = (int) $current_user->ID;

if ( ! $user_id ){
	wp_die(__( 'Invalid user ID.' ) );
}
const IS_PROFILE_PAGE = true;
$wp_http_referer= '/cabinet/';
$user_can_edit = true;

if ( !current_user_can('edit_user', $user_id) )
	wp_die(__('You do not have permission to edit this user.'));

$sessions = WP_Session_Tokens::get_instance( $profileuser->ID );

wp_enqueue_script('user-profile');
?>
<main>
	<form id="your-profile" action="<?php echo esc_url( self_admin_url( IS_PROFILE_PAGE ? 'profile.php' : 'user-edit.php' ) ); ?>" method="post" novalidate="novalidate"<?php
	/**
	 * Fires inside the your-profile form tag on the user editing screen.
	 *
	 * @since 3.0.0
	 */
	do_action( 'user_edit_form_tag' );
	?>>
		<?php wp_nonce_field('update-user_' . $user_id) ?>
		<p>
			<input type="hidden" name="from" value="profile" />
			<input type="hidden" name="checkuser_id" value="<?php echo get_current_user_id(); ?>" />
		</p>

		<h3><?php _e('Personal Options'); ?></h3>

		<table class="form-table">
			<?php
			/**
			 * Fires at the end of the 'Personal Options' settings table on the user editing screen.
			 *
			 * @since 2.7.0
			 *
			 * @param WP_User $profileuser The current WP_User object.
			 */
			do_action( 'personal_options', $profileuser );
			?>

		</table>
		<?php
		if ( IS_PROFILE_PAGE ) {
			/**
			 * Fires after the 'Personal Options' settings table on the 'Your Profile' editing screen.
			 *
			 * The action only fires if the current user is editing their own profile.
			 *
			 * @since 2.0.0
			 *
			 * @param WP_User $profileuser The current WP_User object.
			 */
			do_action( 'profile_personal_options', $profileuser );
		}
		?>

		<h3><?php _e('Name') ?></h3>

		<table class="form-table">
			<tr class="user-user-login-wrap">
				<th><label for="user_login"><?php _e('Username'); ?></label></th>
				<td><input type="text" name="user_login" id="user_login" value="<?php echo esc_attr($profileuser->user_login); ?>" disabled="disabled" class="regular-text" /> <span class="description"><?php _e('Usernames cannot be changed.'); ?></span></td>
			</tr>

			<?php if ( !IS_PROFILE_PAGE && !is_network_admin() ) : ?>
				<tr class="user-role-wrap"><th><label for="role"><?php _e('Role') ?></label></th>
					<td><select name="role" id="role">
							<?php
							// Compare user role against currently editable roles
							$user_roles = array_intersect( array_values( $profileuser->roles ), array_keys( get_editable_roles() ) );
							$user_role  = array_shift( $user_roles );

							// print the full list of roles with the primary one selected.
							wp_dropdown_roles($user_role);

							// print the 'no role' option. Make it selected if the user has no role yet.
							if ( $user_role )
								echo '<option value="">' . __('&mdash; No role for this site &mdash;') . '</option>';
							else
								echo '<option value="" selected="selected">' . __('&mdash; No role for this site &mdash;') . '</option>';
							?>
						</select></td></tr>
			<?php endif; //!IS_PROFILE_PAGE

			if ( is_multisite() && is_network_admin() && ! IS_PROFILE_PAGE && current_user_can( 'manage_network_options' ) && !isset($super_admins) ) { ?>
				<tr class="user-super-admin-wrap"><th><?php _e('Super Admin'); ?></th>
					<td>
						<?php if ( $profileuser->user_email != get_site_option( 'admin_email' ) || ! is_super_admin( $profileuser->ID ) ) : ?>
							<p><label><input type="checkbox" id="super_admin" name="super_admin"<?php checked( is_super_admin( $profileuser->ID ) ); ?> /> <?php _e( 'Grant this user super admin privileges for the Network.' ); ?></label></p>
						<?php else : ?>
							<p><?php _e( 'Super admin privileges cannot be removed because this user has the network admin email.' ); ?></p>
						<?php endif; ?>
					</td></tr>
			<?php } ?>

			<tr class="user-first-name-wrap">
				<th><label for="first_name"><?php _e('First Name') ?></label></th>
				<td><input type="text" name="first_name" id="first_name" value="<?php echo esc_attr($profileuser->first_name) ?>" class="regular-text" /></td>
			</tr>

			<tr class="user-last-name-wrap">
				<th><label for="last_name"><?php _e('Last Name') ?></label></th>
				<td><input type="text" name="last_name" id="last_name" value="<?php echo esc_attr($profileuser->last_name) ?>" class="regular-text" /></td>
			</tr>

			<tr class="user-nickname-wrap">
				<th><label for="nickname"><?php _e('Nickname'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
				<td><input type="text" name="nickname" id="nickname" value="<?php echo esc_attr($profileuser->nickname) ?>" class="regular-text" /></td>
			</tr>

			<tr class="user-display-name-wrap">
				<th><label for="display_name"><?php _e('Display name publicly as') ?></label></th>
				<td>
					<select name="display_name" id="display_name">
						<?php
						$public_display = array();
						$public_display['display_nickname']  = $profileuser->nickname;
						$public_display['display_username']  = $profileuser->user_login;

						if ( !empty($profileuser->first_name) )
							$public_display['display_firstname'] = $profileuser->first_name;

						if ( !empty($profileuser->last_name) )
							$public_display['display_lastname'] = $profileuser->last_name;

						if ( !empty($profileuser->first_name) && !empty($profileuser->last_name) ) {
							$public_display['display_firstlast'] = $profileuser->first_name . ' ' . $profileuser->last_name;
							$public_display['display_lastfirst'] = $profileuser->last_name . ' ' . $profileuser->first_name;
						}

						if ( !in_array( $profileuser->display_name, $public_display ) ) // Only add this if it isn't duplicated elsewhere
							$public_display = array( 'display_displayname' => $profileuser->display_name ) + $public_display;

						$public_display = array_map( 'trim', $public_display );
						$public_display = array_unique( $public_display );

						foreach ( $public_display as $id => $item ) {
							?>
							<option <?php selected( $profileuser->display_name, $item ); ?>><?php echo $item; ?></option>
						<?php
						}
						?>
					</select>
				</td>
			</tr>
		</table>

		<h3><?php _e('Contact Info') ?></h3>

		<table class="form-table">
			<tr class="user-email-wrap">
				<th><label for="email"><?php _e('E-mail'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
				<td><input type="email" name="email" id="email" value="<?php echo esc_attr( $profileuser->user_email ) ?>" class="regular-text ltr" />
					<?php
					$new_email = get_option( $current_user->ID . '_new_email' );
					if ( $new_email && $new_email['newemail'] != $current_user->user_email && $profileuser->ID == $current_user->ID ) : ?>
						<div class="updated inline">
							<p><?php printf( __('There is a pending change of your e-mail to <code>%1$s</code>. <a href="%2$s">Cancel</a>'), $new_email['newemail'], esc_url( self_admin_url( 'profile.php?dismiss=' . $current_user->ID . '_new_email' ) ) ); ?></p>
						</div>
					<?php endif; ?>
				</td>
			</tr>

			<tr class="user-url-wrap">
				<th><label for="url"><?php _e('Website') ?></label></th>
				<td><input type="url" name="url" id="url" value="<?php echo esc_attr( $profileuser->user_url ) ?>" class="regular-text code" /></td>
			</tr>

			<?php
			foreach ( wp_get_user_contact_methods( $profileuser ) as $name => $desc ) {
				?>
				<tr class="user-<?php echo $name; ?>-wrap">
					<th><label for="<?php echo $name; ?>">
							<?php
							/**
							 * Filter a user contactmethod label.
							 *
							 * The dynamic portion of the filter hook, `$name`, refers to
							 * each of the keys in the contactmethods array.
							 *
							 * @since 2.9.0
							 *
							 * @param string $desc The translatable label for the contactmethod.
							 */
							echo apply_filters( "user_{$name}_label", $desc );
							?>
						</label></th>
					<td><input type="text" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="<?php echo esc_attr($profileuser->$name) ?>" class="regular-text" /></td>
				</tr>
			<?php
			}
			?>
		</table>

		<h3><?php IS_PROFILE_PAGE ? _e('About Yourself') : _e('About the user'); ?></h3>

		<table class="form-table">
			<tr class="user-description-wrap">
				<th><label for="description"><?php _e('Biographical Info'); ?></label></th>
				<td><textarea name="description" id="description" rows="5" cols="30"><?php echo $profileuser->description; // textarea_escaped ?></textarea>
					<p class="description"><?php _e('Share a little biographical information to fill out your profile. This may be shown publicly.'); ?></p></td>
			</tr>

			<?php
			/** This filter is documented in wp-admin/user-new.php */
			$show_password_fields = apply_filters( 'show_password_fields', true, $profileuser );
			if ( $show_password_fields ) :
				?>
				<tr id="password" class="user-pass1-wrap">
					<th><label for="pass1"><?php _e( 'New Password' ); ?></label></th>
					<td>
						<input class="hidden" value=" " /><!-- #24364 workaround -->
						<input type="password" name="pass1" id="pass1" class="regular-text" size="16" value="" autocomplete="off" />
						<p class="description"><?php _e( 'If you would like to change the password type a new one. Otherwise leave this blank.' ); ?></p>
					</td>
				</tr>
				<tr class="user-pass2-wrap">
					<th scope="row"><label for="pass2"><?php _e( 'Repeat New Password' ); ?></label></th>
					<td>
						<input name="pass2" type="password" id="pass2" class="regular-text" size="16" value="" autocomplete="off" />
						<p class="description"><?php _e( 'Type your new password again.' ); ?></p>
						<br />
						<div id="pass-strength-result"><?php _e( 'Strength indicator' ); ?></div>
						<p class="description indicator-hint"><?php echo wp_get_password_hint(); ?></p>
					</td>
				</tr>
			<?php endif; ?>
		</table>

		<?php
		if ( IS_PROFILE_PAGE ) {
			/**
			 * Fires after the 'About Yourself' settings table on the 'Your Profile' editing screen.
			 *
			 * The action only fires if the current user is editing their own profile.
			 *
			 * @since 2.0.0
			 *
			 * @param WP_User $profileuser The current WP_User object.
			 */
			do_action( 'show_user_profile', $profileuser );
		} else {
			/**
			 * Fires after the 'About the User' settings table on the 'Edit User' screen.
			 *
			 * @since 2.0.0
			 *
			 * @param WP_User $profileuser The current WP_User object.
			 */
			do_action( 'edit_user_profile', $profileuser );
		}
		?>

		<?php
		/**
		 * Filter whether to display additional capabilities for the user.
		 *
		 * The 'Additional Capabilities' section will only be enabled if
		 * the number of the user's capabilities exceeds their number of
		 * of roles.
		 *
		 * @since 2.8.0
		 *
		 * @param bool    $enable      Whether to display the capabilities. Default true.
		 * @param WP_User $profileuser The current WP_User object.
		 */
		if ( count( $profileuser->caps ) > count( $profileuser->roles )
		     && apply_filters( 'additional_capabilities_display', true, $profileuser )
		) : ?>
			<h3><?php _e( 'Additional Capabilities' ); ?></h3>
			<table class="form-table">
				<tr class="user-capabilities-wrap">
					<th scope="row"><?php _e( 'Capabilities' ); ?></th>
					<td>
						<?php
						$output = '';
						foreach ( $profileuser->caps as $cap => $value ) {
							if ( ! $wp_roles->is_role( $cap ) ) {
								if ( '' != $output )
									$output .= ', ';
								$output .= $value ? $cap : sprintf( __( 'Denied: %s' ), $cap );
							}
						}
						echo $output;
						?>
					</td>
				</tr>
			</table>
		<?php endif; ?>

		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr($user_id); ?>" />
		<input type="submit" value="<?php _e('Submit', GeoSets::CONTENT);?>"/>

	</form>
	<?php do_action('showTable');?>
</main>

<?php get_footer(); ?>
