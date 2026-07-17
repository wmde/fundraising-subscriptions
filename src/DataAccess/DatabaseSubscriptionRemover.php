<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\DataAccess;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManager;
use WMDE\Clock\Clock;
use WMDE\Fundraising\SubscriptionContext\Domain\SubscriptionRemover;

/**
 * In order to clean up old subscriptions instead of scrubbing certain fields we can safely delete the entire entry.
 */
class DatabaseSubscriptionRemover implements SubscriptionRemover {

	public function __construct(
		private readonly EntityManager $entityManager,
		private readonly Clock $clock,
		private readonly \DateInterval $exportGracePeriod
	) {
	}

	/**
	 * @param int ...$subscriptionIds
	 *
	 * @return int amount of deleted rows
	 * @throws Exception
	 * @throws \DateInvalidOperationException
	 */
	public function removeByIds( int ...$subscriptionIds ): int {
		$cutoffDate = $this->clock->now()->sub( $this->exportGracePeriod );

		$queryResult = $this->entityManager->getConnection()->executeQuery(
			sql: 'DELETE FROM subscription WHERE id IN ( ? ) AND ( export IS NOT NULL OR createdAt < ? ); ',
			params: [
				$subscriptionIds,
				$cutoffDate->format( 'Y-m-d H:i:s' )
			],
			types: [
				ArrayParameterType::INTEGER,
				ParameterType::STRING
			]
		);

		return intval( $queryResult->rowCount() );
	}

	public function removeAll(): int {
		$cutoffDate = $this->clock->now()->sub( $this->exportGracePeriod );

		$queryResult = $this->entityManager->getConnection()->executeQuery(
			sql: 'DELETE FROM subscription WHERE export IS NOT NULL OR createdAt < :gracePeriodDate; ',
			params: [ 'gracePeriodDate' => $cutoffDate->format( 'Y-m-d H:i:s' ) ],
			types: [ ParameterType::STRING ]
		);

		return intval( $queryResult->rowCount() );
	}
}
