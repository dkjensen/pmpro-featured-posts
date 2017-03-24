<?php

function pmpro_fpost_admin_menu() {
	add_submenu_page( 'pmpro-membershiplevels', __( 'Featured Post Settings', 'pmpro-fpost' ), __( 'Featured Post Settings', 'pmpro-fpost' ), 'pmpro_advancedsettings', 'pmpro-fpostsettings', 'pmpro_fpostsettings' );
}
add_action( 'admin_menu', 'pmpro_fpost_admin_menu', 15 );


function pmpro_fpostsettings() {
	if( ! current_user_can( "pmpro_advancedsettings" ) ) {
		die( __( "You do not have permissions to perform this action.", "pmpro" ) );
	}

	global $wpdb, $msg, $msgt;

	if( ! empty($_REQUEST['savesettings'] ) ) {
		pmpro_setOption( 'fpostcost' );
		pmpro_setOption( 'fpostduration' );
		pmpro_setOption( 'fpostlevels' );

		$msg = true;
		$msgt = __( 'Your featured post settings have been updated.', 'pmpro' );
	}

	$fpostcost     = pmpro_fpost_get_featured_price();
	$fpostduration = pmpro_getOption( 'fpostduration' );
	$fpostlevels   = explode( ',', pmpro_getOption( 'fpostlevels' ) );

	$levels = $wpdb->get_results( "SELECT * FROM {$wpdb->pmpro_membership_levels}", OBJECT );
?>

	<form action="" method="post" enctype="multipart/form-data">
		<h2><?php _e( 'Featured Post Settings', 'pmpro-fpost' );?></h2>

		<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top">
					<label for="nonmembertext"><?php _e( 'Featured Post Cost', 'pmpro-fpost' ); ?>:</label>
				</th>
				<td>
					<input type="text" name="fpostcost" value="<?php print esc_attr( $fpostcost ); ?>" placeholder="30.00" /><br />
					<small class="litegray"><?php _e( 'The price of purchasing a featured post.', 'pmpro-fpost' ); ?></small>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="nonmembertext"><?php _e( 'Featured Post Duration', 'pmpro-fpost' ); ?>:</label>
				</th>
				<td>
					<input type="text" name="fpostduration" value="<?php print esc_attr( $fpostduration ); ?>" /> <?php _e( 'Day(s)', 'pmpro-fpost' ); ?><br />
					<small class="litegray"><?php _e( 'The duration of purchased featured posts in days.', 'pmpro-fpost' ); ?></small>
				</td>
			</tr>
			<?php if( ! empty( $levels ) ) : ?>
			<tr>
				<th scope="row" valign="top">
					<label for="nonmembertext"><?php _e( 'Featured Posts Enable For', 'pmpro-fpost' ); ?>:</label>
				</th>
				<td>
					<?php foreach( $levels as $level ) : ?>

						<label><input type="checkbox" name="fpostlevels[]" value="<?php print $level->id; ?>" <?php if( in_array( $level->id, $fpostlevels ) ) print 'checked="checked"'; ?> /> <?php esc_attr_e( $level->name ); ?></label><br />

					<?php endforeach; ?>
				</td>
			</tr>
			<?php endif; ?>
        </tbody>
		</table>
		<p class="submit">
			<input name="savesettings" type="submit" class="button button-primary" value="<?php _e( 'Save Settings', 'pmpro-fpost' ); ?>" />
		</p>
	</form>

	<?php
}