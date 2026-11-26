<?php
/**
 * EnquiryStatus: the stages an enquiry moves through, and who may manage them.
 *
 * Plain PHP, so it can be unit-tested. The wp-admin Enquiries screen uses it
 * for the status filter, the status drop-down and its permission check.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enquiry statuses and the screen's access rule.
 */
final class EnquiryStatus {

	/** Status => label, in workflow order. New enquiries start as 'new'. */
	public const ALL = array(
		'new'       => 'New',
		'contacted' => 'Contacted',
		'converted' => 'Converted',
		'closed'    => 'Closed',
	);

	/**
	 * Is this a known status?
	 *
	 * @param string $status Status value.
	 */
	public static function is_valid( string $status ): bool {
		return array_key_exists( $status, self::ALL );
	}

	/**
	 * May a user with these roles see and update enquiries in wp-admin?
	 * Administrators only: wp-admin is administrator-only (Phase 0 decision);
	 * pz_admin staff get enquiries in the /manage portal in Phase 8.
	 *
	 * @param string[] $roles The user's roles.
	 */
	public static function can_manage( array $roles ): bool {
		return in_array( 'administrator', $roles, true );
	}
}
