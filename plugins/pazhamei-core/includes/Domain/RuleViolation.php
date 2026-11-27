<?php
/**
 * RuleViolation: thrown when an action breaks a business rule
 * (e.g. skipping a locked day). The message is safe to show the customer.
 *
 * REST endpoints turn it into a 422 response; WP-CLI prints it as an error.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

use DomainException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A refused action, with a short machine-readable code.
 */
final class RuleViolation extends DomainException {

	private string $rule;

	/**
	 * @param string $rule    Short code, e.g. 'locked', 'not_scheduled'.
	 * @param string $message Customer-facing explanation.
	 */
	public function __construct( string $rule, string $message ) {
		parent::__construct( $message );
		$this->rule = $rule;
	}

	/**
	 * The short code.
	 */
	public function rule(): string {
		return $this->rule;
	}
}
