<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\DataAccess;

use Doctrine\DBAL\Connection;
use WMDE\Clock\Clock;

class DatabaseSubscriptionRemovalMonitor implements SubscriptionRemovalMonitor {

	private Connection $conn;
	private Clock $clock;
	private \DateInterval $exportGracePeriod;

	public function __construct( Connection $conn, Clock $clock, \DateInterval $exportGracePeriod ) {
		$this->conn = $conn;
		$this->clock = $clock;
		$this->exportGracePeriod = $exportGracePeriod;
	}

	/**
	 * Checks if there are old subscription entries in the database.
	 * Subscriptions usually should get deleted after export (or after a grace period has passed).
	 * @return int
	 * @throws \DateInvalidOperationException
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function countUnremovedSubscriptions(): int {
		$now = $this->clock->now();
		$gracePeriodDate = \DateTime::createFromImmutable( $now->sub( $this->exportGracePeriod ) );

		$sqlQuery = "SELECT COUNT(*) as count FROM subscription s WHERE s.export IS NOT NULL OR s.createdAt < :gracePeriodDate; ";
		$queryResult = $this->conn->executeQuery(
			sql: $sqlQuery,
			params: [ 'gracePeriodDate' => $gracePeriodDate->format( 'Y-m-d H:i:s' ) ]
		);

		$count = $queryResult->fetchOne();

		if ( !is_scalar( $count ) ) {
			return -1;
		}
		return intval( $count );
	}
}
