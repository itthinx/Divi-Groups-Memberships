<?php
/**
 * class-divi-groups-memberships.php
 *
 * Copyright (c) www.itthinx.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 * 
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * This header and all notices must be kept intact.
 *
 * @author itthinx
 * @package divi-groups-memberships
 * @since 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Overrides access restrictions imposed by Groups and by Groups Restrict Categories.
 */
class Divi_Groups_Memberships {

	/**
	 * Register the init action handler.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'wp_init' ) );
	}

	/**
	 * Handler for the init action.
	 */
	public static function wp_init() {
		add_filter( 'groups_post_access_posts_where_apply', array( __CLASS__, 'groups_post_access_posts_where_apply' ), 10, 3 );
		add_filter( 'groups_post_access_get_the_excerpt_apply', array( __CLASS__, 'groups_post_access_get_the_excerpt_apply' ), 10, 2 );
		add_filter( 'groups_post_access_the_content_apply', array( __CLASS__, 'groups_post_access_the_content_apply' ), 10, 2 );
		add_filter( 'groups_restrict_categories_posts_where_apply', array( __CLASS__, 'groups_restrict_categories_posts_where_apply' ), 10, 3 );
		add_filter( 'groups_restrict_categories_get_the_excerpt_apply', array( __CLASS__, 'groups_restrict_categories_get_the_excerpt_apply' ), 10, 2 );
		add_filter( 'groups_restrict_categories_the_content_apply', array( __CLASS__, 'groups_restrict_categories_the_content_apply' ), 10, 2 );
		add_filter( 'the_content', array( __CLASS__, 'the_content' ) );
	}

	/**
	 * Overrides access restriction imposed by Groups.
	 *
	 * @param boolean $apply
	 * @param string $where
	 * @param WP_Query $query
	 *
	 * @return boolean
	 */
	public static function groups_post_access_posts_where_apply( $apply, $where, $query ) {
		$result = $apply;
		$post_types = $query->get( 'post_type', null );
		if (
			!is_array( $post_types ) &&
			( empty( $post_types ) || $post_types === '' || $post_types === 'post' )
		) {
			$result = false;
		}
		return $result;
	}

	/**
	 * Overrides excerpt restriction imposed by Groups.
	 *
	 * @param boolean $apply
	 * @param string $output
	 *
	 * @return boolean
	 */
	public static function groups_post_access_get_the_excerpt_apply( $apply, $output ) {
		return self::groups_post_access_the_content_apply( $apply, $output );
	}

	/**
	 * Overrides excerpt restriction imposed by Groups Restrict Categories.
	 *
	 * @param boolean $apply
	 * @param string $output
	 *
	 * @return boolean
	 */
	public static function groups_restrict_categories_get_the_excerpt_apply( $apply, $output ) {
		return self::groups_post_access_get_the_excerpt_apply( $apply, $output );
	}

	/**
	 * Overrides content restriction imposed by Groups.
	 *
	 * @param boolean $apply
	 * @param string $output
	 *
	 * @return boolean
	 */
	public static function groups_post_access_the_content_apply( $apply, $output ) {
		global $post;
		$result = $apply;
		if ( isset( $post ) && $post instanceof WP_Post ) {
			if ( $post->post_type === 'post' ) {
				$result = false;
			}
		}
		return $result;
	}

	/**
	 * Overrides access restriction imposed by Groups Restrict Categories.
	 *
	 * @param boolean $apply
	 * @param string $where
	 * @param WP_Query $query
	 *
	 * @return boolean
	 */
	public static function groups_restrict_categories_posts_where_apply( $apply, $where, $query ) {
		return self::groups_post_access_posts_where_apply( $apply, $where, $query );
	}

	/**
	 * Overrides content restriction imposed by Groups Restrict Categories.
	 *
	 * @param boolean $apply
	 * @param string $output
	 *
	 * @return boolean
	 */
	public static function groups_restrict_categories_the_content_apply( $apply, $output ) {
		return self::groups_post_access_the_content_apply( $apply, $output );
	}

	/**
	 * Returns the excerpt instead of the content if the user is not authorized to see the full post.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public static function the_content( $content ) {
		global $post;

		$result = $content;
		if ( isset( $post ) && $post instanceof WP_Post ) {
			if ( $post->post_type === 'post' ) {
				if ( class_exists( 'Groups_Post_Access' ) ) {
					if ( !Groups_Post_Access::user_can_read_post( $post->ID ) ) {

						remove_filter( 'the_content', array( __CLASS__, 'the_content' ) );
						$result = apply_filters(
							'divi_groups_memberships_excerpt',
							get_the_excerpt( $post ),
							$post
						);
						add_filter( 'the_content', array( __CLASS__, 'the_content' ) );

						if ( !is_home() && !is_front_page() && !is_archive() ) {
							$login_url = wp_login_url( get_permalink( $post ) );
							if ( is_user_logged_in() ) {
								$text = __( 'Please subscribe to our memberships to read the full article.', 'divi-groups-memberships' );
							} else {
								$text = sprintf(
									__( 'Please subscribe to our memberships and %1$slog in%2$s to read the full article.', 'divi-groups-memberships' ),
									sprintf( '<a href="%s">', esc_attr( $login_url ) ),
									'</a>'
								);
							}
							$result .= apply_filters(
								'divi_groups_memberships_excerpt_suffix',
								' ' .
								'<div class="divi-groups-memberships-cta-subscribe">' .
								wp_kses_post( $text ),
								'</div>',
								$post
							);
						}
					}
				}
			}
		}
		return $result;
	}
}

Divi_Groups_Memberships::init();
