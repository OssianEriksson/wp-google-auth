<?php
/**
 * Class for logging
 *
 * @package ftek/google-auth
 */

namespace Ftek\GoogleAuth;

/**
 * User utils and hooks.
 */
class User {

	/**
	 * User constructor
	 */
	public function __construct() {
		add_filter(
			'get_avatar',
			array( $this, 'get_avatar' ),
			9,
			5
		);
		add_filter(
			'show_password_fields',
			array( $this, 'show_password_fields' ),
			2,
			10
		);
		add_filter(
			'allow_password_reset',
			array( $this, 'allow_password_reset' ),
			2,
			10
		);
		add_filter(
			'user_profile_picture_description',
			array( $this, 'user_profile_picture_description' ),
			2,
			10
		);
		add_action(
			'personal_options',
			array( $this, 'personal_options' )
		);
	}

	/**
	 * Filter for the get_avatar hook
	 *
	 * @param string $avatar HTML for the user's avatar.
	 * @param mixed  $id_or_email The avatar to retrieve.
	 * @param int    $size Square avatar width and height in pixels to retrieve.
	 * @param string $default URL for the default image or a default type.
	 * @param string $alt Alternative text to use in the avatar image tag.
	 */
	public function get_avatar( string $avatar, $id_or_email, int $size, string $default, string $alt ): string {
		if ( is_numeric( $id_or_email ) ) {
			$user_id = (int) $id_or_email;
		} elseif ( is_object( $id_or_email ) ) {
			if ( ! empty( $id_or_email->ID ) ) {
				$user_id = $id_or_email->ID;
			} elseif ( ! empty( $id_or_email->user_id ) ) {
				$user_id = (int) $id_or_email->user_id;
			} elseif ( ! empty( $id_or_email->post_author ) ) {
				$user_id = (int) $id_or_email->post_author;
			} else {
				return $avatar;
			}
		} else {
			$user = get_user_by( 'email', $id_or_email );
			if ( ! $user ) {
				return $avatar;
			}
			$user_id = $user->ID;
		}

		$meta = $this->get_meta_fields( $user_id );
		if ( empty( $meta['picture'] ) ) {
			return $avatar;
		}

		ob_start();
		?>
		<img class="avatar avatar-<?php echo esc_attr( $size ); ?> photo" loading="lazy" src="<?php echo esc_attr( $meta['picture'] ); ?>" width="<?php echo esc_attr( $size ); ?>" height="<?php echo esc_attr( $size ); ?>" alt="<?php echo esc_attr( $alt ); ?>" />
		<?php
		return ob_get_clean();
	}

	/**
	 * Callback for the show_password_fields filter hook
	 *
	 * Disables password resets
	 *
	 * @param bool     $show Whether to show the password fields.
	 * @param \WP_User $user User object for the current user to edit.
	 */
	public function show_password_fields( bool $show, \WP_User $user ): bool {
		return $this->allow_password_reset( $show, $user->ID );
	}

	/**
	 * Callback for the allow_password_reset filter hook
	 *
	 * Disables password resets
	 *
	 * @param bool $allow   Whether to allow the password to be reset.
	 * @param int  $user_id The ID of the user attempting to reset a password.
	 */
	public function allow_password_reset( bool $allow, int $user_id ): bool {
		if ( ! $this->get_meta_fields( $user_id )['is_google_user'] ) {
			return $allow;
		}
		return false;
	}

	/**
	 * Callback for the user_profile_picture_description filter hook
	 *
	 * Removes profile description
	 *
	 * @param string   $description The description that will be printed.
	 * @param \WP_User $user        The current WP_User object.
	 */
	public function user_profile_picture_description( string $description, \WP_User $user ): string {
		if ( ! $this->get_meta_fields( $user->ID )['is_google_user'] ) {
			return $description;
		}
		return '';
	}

	/**
	 * Callback for the personal_options action hook
	 *
	 * Disables editing of some fields tied to the user's Google account
	 *
	 * @param \WP_User $user The current WP_User object.
	 */
	public function personal_options( \WP_User $user ): void {
		if ( ! $this->get_meta_fields( $user->ID )['is_google_user'] ) {
			return;
		}

		?>
		<script type="text/javascript">
			document.addEventListener("DOMContentLoaded", () => {
				document.querySelectorAll("#your-profile").forEach(yourProfile => {
					yourProfile.querySelectorAll("#first_name,#last_name,#email,#role").forEach(input => {
						input.setAttribute("disabled", true);
					})
				});
			});
		</script>
		<?php
	}

	/**
	 * Retrieve a custom meta fields for a user
	 *
	 * @param int $user_id User ID.
	 */
	public function get_meta_fields( int $user_id ): array {
		$meta = get_user_meta( $user_id, 'wp_google_auth', true );
		return array_merge(
			array(
				'picture'        => '',
				'is_google_user' => false,
			),
			empty( $meta ) ? array() : $meta
		);
	}

	/**
	 * Update a custom meta fields for a user
	 *
	 * @param int   $user_id User ID.
	 * @param array $meta    Metadata fields.
	 */
	public function update_meta_fields( int $user_id, array $meta ): void {
		update_user_meta( $user_id, 'wp_google_auth', $meta );
	}
}
