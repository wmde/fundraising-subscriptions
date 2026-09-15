<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\UseCases;

use WMDE\Fundraising\SubscriptionContext\Domain\Repositories\SubscriptionRepository;
use WMDE\Fundraising\SubscriptionContext\Domain\SubscriptionRemover;

class DeleteSubscriptionUseCase {

	public function __construct(
		private readonly SubscriptionRepository $subscriptionRepository,
		private readonly SubscriptionRemover $subscriptionRemover
	) {
	}

	/**
	 * @param string $email the user's address
	 *
	 * @return int amount of rows deleted
	 */
	public function removeSubscriptionByEmailAddress( string $email ): int {
		$subscriptionsToDelete = $this->subscriptionRepository->findSubscriptionsByPersonalData( $email );

		return $this->subscriptionRemover->forceRemoveByIds( ...$subscriptionsToDelete );
	}

}