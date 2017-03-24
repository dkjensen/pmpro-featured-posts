<?php


function pmpro_fpost_metabox_scripts( $hook ) {
	if( $hook == 'post.php' || ( $hook == 'post-new.php' && isset( $_REQUEST['post_type'] ) && in_array( $_REQUEST['post_type'], apply_filters( 'pmpro_fpost_featured_post_metabox_locations', array( 'post' ) ) ) ) ) {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-ui-datepicker' );
	}
}
add_action( 'admin_enqueue_scripts', 'pmpro_fpost_metabox_scripts' );

function pmpro_fpost_metaboxes() {
    add_meta_box( 'pmpro_fpost_featured_post', __( 'Featured Post', 'msw' ), 'pmpro_fpost_featured_post_metabox', apply_filters( 'pmpro_fpost_featured_post_metabox_locations', array( 'post' ) ), 'side', 'high' );
}
add_action( 'add_meta_boxes', 'pmpro_fpost_metaboxes' );


function pmpro_fpost_featured_post_metabox() {
	global $post;

	$featured_item = (int) get_post_meta( $post->ID, 'pmpro_fpost_featured_item', true );

	if( ! empty( $featured_expiration = get_post_meta( $post->ID, 'pmpro_fpost_featured_expiration', true ) ) ) {
		$featured_expiration = date( 'Y-m-d', $featured_expiration );
	}

	do_action( 'pmpro_fpost_before_featured_metabox', $post->ID );

	?>

	<p><label><input type="checkbox" name="pmpro_fpost_featured_item" value="1" <?php checked( $featured_item, 1 ); ?> /></label> <?php _e( 'Featured post?', 'pmpro-fpost' ); ?></p>
	<p><input type="text" name="pmpro_fpost_featured_expiration" value="<?php print esc_attr( $featured_expiration ); ?>" placeholder="<?php _e( 'No expiration set', 'pmpro-fpost' ); ?>" style="width: 100%;" class="datepicker" />

	<script>
	jQuery(document).ready(function() {
		jQuery('.datepicker').datepicker({
			dateFormat: 'yy-mm-dd'
		});
	});
	</script>

	<?php

	do_action( 'pmpro_fpost_after_featured_metabox', $post->ID );
}


function pmpro_fpost_save_postmeta( $post_id ) {
	$post_type = get_post_type( $post_id );

	if( wp_is_post_revision( $post_id ) || ! in_array( $post_type, apply_filters( 'pmpro_fpost_featured_post_metabox_locations', array( 'post' ) ) ) )
		return;

	update_post_meta( $post_id, 'pmpro_fpost_featured_item', intval( $_POST['pmpro_fpost_featured_item'] ) );

	if( isset( $_POST['pmpro_fpost_featured_expiration'] ) ) {
		update_post_meta( $post_id, 'pmpro_fpost_featured_expiration', strtotime( $_POST['pmpro_fpost_featured_expiration'] ) );
	}

	do_action( 'pmpro_fpost_save_postmeta', $post_id );
}
add_action( 'save_post', 'pmpro_fpost_save_postmeta' );