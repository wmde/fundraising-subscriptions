<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Domain\Repositories;

/**
 * @license GPL-2.0-or-later
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
class SubscriptionRepositoryException extends \RuntimeException {

	public function __construct( string $message, \Exception $previous = null ) {
		parent::__construct( $message, 0, $previous );
	}

}
