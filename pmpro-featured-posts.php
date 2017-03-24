<?php
/*
Plugin Name: Paid Memberships Pro - Featured Posts
Description: Adds the ability to sell featured posts with one-off payments
Version: 1.0.0
Author: David Jensen
Author URI: https://dkjensen.com
*/


if( ! defined( 'ABSPATH' ) ) exit;


require_once 'includes/pmpro-featured-posts-functions.php';
require_once 'includes/admin/pmpro-fpost-settings.php';
require_once 'includes/admin/pmpro-fpost-metaboxes.php';


function pmpro_fpost_paypal_express_return_url_parameters( $params ) {
    if( ! empty( $_REQUEST['fpost'] ) ) {
        $params['fpost'] = isset( $_REQUEST['fpost'] ) ? intval( $_REQUEST['fpost'] ) : null;
    }

    return $params;
}
add_filter( 'pmpro_paypal_express_return_url_parameters', 'pmpro_fpost_paypal_express_return_url_parameters' );


function pmpro_fpost_checkout_level( $level ) {
    // Check if this level can have featured posts
    if( ! pmpro_fpost_level_can_featured( $level->id ) ) {
        return false;
    }

	if( ! empty( $_REQUEST['fpost'] ) ) {
		$fpost  = get_post( $_REQUEST['fpost'] );
		$fprice = pmpro_fpost_get_featured_price();

		// Make sure the post exists
		if( empty( $fpost ) ) {
            return false;
        }

        // Check if the current user is the owner of this post
        if( get_current_user_id() !== intval( $fpost->post_author ) ) {
            wp_die( __( 'You can not feature this post as you are not the author.', 'pmpro-fpost' ) );
        }

		// Check if the user already has the subscription level
		if( pmpro_hasMembershipLevel( $level->id ) ) {

			$level->initial_payment = $fprice;

            //zero the rest out
            $level->billing_amount = 0;
            $level->cycle_number = 0;
            $level->trial_amount = 0;
            $level->trial_limit = 0;

            //unset expiration period and number
            $level->expiration_period = NULL;
            $level->expiration_number = NULL;

			// Do not cancel previous subscription
			add_filter( 'pmpro_cancel_previous_subscriptions', '__return_false' );

            if( ! function_exists( 'pmpro_fpost_disable_change_membership' ) ) {
                /**
                 * Do not deactivate the old membership
                 */
                function pmpro_fpost_disable_change_membership( $user_id, $morder ) {
                    add_filter( 'pmpro_deactivate_old_levels', '__return_false' );
                }
            }
            add_action( 'pmpro_checkout_before_change_membership_level', 'pmpro_fpost_disable_change_membership', 10, 2 );


            if( ! function_exists( 'pmpro_fpost_deactivate_new_membership' ) ) {
                /**
                 * Set the new membership to inactive to revert back to the users original
                 */
                function pmpro_fpost_deactivate_new_membership( $level_id, $user_id, $cancel_level ) {
                    global $wpdb;

                    $membership = pmpro_getMembershipLevelForUser( $user_id );

                    if( ! empty( $membership ) ) {
                        $update = $wpdb->update(
                            $wpdb->pmpro_memberships_users,
                            array( 'status' => 'inactive' ),
                            array( 'id' => $membership->subscription_id ),
                            array( '%s' ),
                            array( '%d' )
                        );
                    } 
                }
            }
            add_action( 'pmpro_after_change_membership_level', 'pmpro_fpost_deactivate_new_membership', 10, 3 );

			//keep current enddate
            if( ! function_exists( 'pmpro_fpost_checkout_end_date' ) ) {
                function pmpro_fpost_checkout_end_date( $enddate, $user_id, $pmpro_level, $startdate ) {
    	            $user_level = pmpro_getMembershipLevelForUser( $user_id );
    				if( ! empty( $user_level ) && ! empty( $user_level->enddate ) && $user->enddate != '0000-00-00 00:00:00' ) {
    					return date_i18n( 'Y-m-d H:i:s', $user_level->enddate );
    				}else {
    					return $enddate;
    				}
                }
            }
            add_filter( 'pmpro_checkout_end_date', 'pmpro_fpost_checkout_end_date', 15, 4 );
		}else {
			$level->initial_payment = $level->initial_payment + $fprice;
		}

		// Hide discount box
		add_filter( 'pmpro_show_discount_code', '__return_false' );

        add_action( 'pmpro_checkout_boxes', 'pmpro_fpost_checkout_boxes' );

        add_action( 'pmpro_after_checkout', 'pmpro_fpost_after_checkout' );
	}

	return $level;
}
add_filter( 'pmpro_checkout_level', 'pmpro_fpost_checkout_level' );


if( ! function_exists( 'pmpro_fpost_checkout_boxes' ) ) {

function pmpro_fpost_checkout_boxes() {
    if( ! empty( $_REQUEST['fpost'] ) ) { ?>

    <input type="hidden" name="fpost" value="<?php print intval( $_REQUEST['fpost'] ); ?>" />

    <style>
        #pmpro_account_loggedin { display: none !important; }
    </style>
        
    <?php
    }
}

}

if( ! function_exists( 'pmpro_fpost_after_checkout' ) ) {

function pmpro_fpost_after_checkout( $user_id ) {
    $pmpro_fpost = null;

    if( ! empty( $_SESSION['fpost'] ) ) {
        $pmpro_fpost = intval( $_SESSION['fpost'] );
    }elseif( ! empty( $_REQUEST['fpost'] ) ) {
        $pmpro_fpost = intval( $_REQUEST['fpost'] );
    }

    if( ! empty( $pmpro_fpost ) ) {
        $_SESSION['fpost'] = $pmpro_fpost;

        pmpro_fpost_set_featured( $pmpro_fpost );

        function pmpro_fpost_confirmation_url( $url, $user_id, $level ) {
            global $pmpro_fpost;

            $url = add_query_arg( array( 'fpost' => $pmpro_fpost ), $url );

            return $url;
        }
        add_filter( 'pmpro_confirmation_url', 'pmpro_fpost_confirmation_url', 10, 3 );
    }
}

}


function pmpro_fpost_checkout_level_have_it( $level ) {
    global $pmpro_pages;

    if( is_page( $pmpro_pages['checkout'] ) && ! empty( $_REQUEST['fpost'] ) && pmpro_hasMembershipLevel( $level->id ) ) {
        $level->description = '';
    }

    return $level;
}
add_filter( 'pmpro_checkout_level', 'pmpro_fpost_checkout_level_have_it' );



function pmpro_fpost_gettext_you_have_selected($translated_text, $text, $domain) {
    global $pmpro_pages;

    if( ! empty( $pmpro_pages ) && is_page( $pmpro_pages['checkout'] ) && ! empty( $_REQUEST['fpost'] ) && $domain == "pmpro" && strpos( $text, "have selected" ) !== false && pmpro_hasMembershipLevel( intval( $_REQUEST['level'] ) ) ) {
        $_post = get_post( $_REQUEST['fpost'] );


        if( ! empty( $_post ) ) {
            $translated_text = str_replace( __( " membership level", "pmpro_fpost"), "", $translated_text );
            $translated_text = str_replace( __( "You have selected the", "pmpro_fpost"), sprintf( __( "You are upgrading <strong>\"%s\"</strong> to a featured post with your subscription:", "pmpro_fpost" ), $_post->post_title ), $translated_text );
        }
    }

    return $translated_text;
}
add_filter( 'gettext', 'pmpro_fpost_gettext_you_have_selected', 10, 3 );



function pmpro_fpost_level_cost_text( $text, $level ) {
    global $pmpro_pages;

    if( is_page( $pmpro_pages['checkout'] ) && ! empty( $_REQUEST['fpost'] ) && pmpro_hasMembershipLevel( $level->id ) ) {
        $text = str_replace( __( "The price for membership", "pmpro_fpost" ), __( "The price is", "pmpro_fpost" ), $text );
        $text = str_replace( __( " now", "pmpro_fpost" ), "", $text );
    }

    return $text;
}
add_filter( 'pmpro_level_cost_text', 'pmpro_fpost_level_cost_text', 10, 2 );


function pmpro_fpost_confirmation_message( $message ) {
    $pmpro_fpost = null;

    if( ! empty( $_SESSION['fpost'] ) ) {
        $pmpro_fpost = intval( $_SESSION['fpost'] );
    }elseif( ! empty( $_REQUEST['fpost'] ) ) {
        $pmpro_fpost = intval( $_REQUEST['fpost'] );
    }

    if( ! empty( $pmpro_fpost ) ) {
        $expiration = pmpro_fpost_get_post_expiration( $pmpro_fpost );

        $message = sprintf( '<p>Thank you, your featured post will expire on <strong>%s</strong>.</p>%s', date( 'F j, Y', $expiration ), '<style>#pmpro_confirmation_table { display: none !important; } </style>' );

        unset( $_SESSION['fpost'] );
    }

    return $message;
}
add_filter( 'pmpro_confirmation_message', 'pmpro_fpost_confirmation_message' );