<?php


/**
 * Sets the post as featured for x amount of time
 * 
 * @param  integer $post_id 
 * @return boolean
 */
function pmpro_fpost_set_featured( $post_id ) {
	$_post = get_post( $post_id );

	if( ! empty( $_post ) ) {
		$duration   = pmpro_fpost_get_featured_duration();
		$expiration = ! empty( $duration ) ? strtotime( "+" . $duration . " days" ) : strtotime( "+30 days" );;

		$current_expiration = get_post_meta( $post_id, 'pmpro_fpost_featured_expiration', true );

		if( ! empty( $current_expiration ) && intval( $current_expiration ) > time() ) {
			$expiration = strtotime( "+" . $duration . " days", $current_expiration );
		}

		update_post_meta( $post_id, 'pmpro_fpost_featured_item', 1 );
		update_post_meta( $post_id, 'pmpro_fpost_featured_expiration', $expiration );
		update_post_meta( $post_id, 'pmpro_fpost_last_featured', time() );

		do_action( 'pmpro_fpost_set_featured', $post_id );

		return true;
	}

	return false;
}


/**
 * Check if the specified level has featured post capability enabled
 * 
 * @param  integer $level 
 * @return boolean
 */
function pmpro_fpost_level_can_featured( $level = 0 ) {
	$fpostlevels = explode( ',', pmpro_getOption( 'fpostlevels' ) );

    if( ! in_array( $level, $fpostlevels ) ) {
        return false;
    }

    return true;
}


/**
 * Return the price for a featured post
 * 
 * @return integer
 */
function pmpro_fpost_get_featured_price() {
	return number_format( (int) pmpro_getOption( 'fpostcost' ), 2 );
}


/**
 * Return the duration a post will be featured in days
 * 
 * @return integer
 */
function pmpro_fpost_get_featured_duration() {
	return intval( pmpro_getOption( 'fpostduration' ) );
}


/**
 * Returns the given posts featured expiration
 * 
 * @param  integer $post_id 
 * @return integer
 */
function pmpro_fpost_get_post_expiration( $post_id = 0 ) {
	return get_post_meta( $post_id, 'pmpro_fpost_featured_expiration', true );
}


/**
 * Check if a given post is featured and the expiration has not passed
 * 
 * @param  integer $post_id 
 * @return boolean
 */
function pmpro_fpost_post_featured( $post_id = 0 ) {
	$fpost_featured   = (int) get_post_meta( $post_id, 'pmpro_fpost_featured_item', true );
	$fpost_expiration = get_post_meta( $post_id, 'pmpro_fpost_featured_expiration', true );

	if( $fpost_featured && $fpost_expiration > time() ) {
		return true;
	}

	return false;
}