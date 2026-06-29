<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\DataAccess;

use Doctrine\DBAL\Connection;
use WMDE\Clock\Clock;

class DatabaseSubscriptionAnonymizationMonitor implements SubscriptionAnonymizationMonitor {

	private const string EXPORT_GRACE_PERIOD = 'P1M';
	private Connection $conn;
	private Clock $clock;

	public function __construct( Connection $conn, Clock $clock ) {
		$this->conn = $conn;
		$this->clock = $clock;
	}

	public function countUnscrubbedSubscriptions(): int {
		$now = $this->clock->now();
		$gracePeriodDate = \DateTime::createFromImmutable( $now->sub( new \DateInterval( self::EXPORT_GRACE_PERIOD ) ) );

		$sqlQuery = "SELECT COUNT(id) as count FROM subscription s WHERE (s.email is not null AND s.email!='') AND s.createdAt < :gracePeriodDate; ";
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
