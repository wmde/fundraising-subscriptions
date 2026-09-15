<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\UseCases;

use WMDE\Fundraising\SubscriptionContext\Domain\Repositories\SubscriptionRepository;

class SearchSubscriptionUseCase {

	public function __construct(
		private readonly SubscriptionRepository $subscriptionRepository
	) {
	}

	public function findSubscriptionsByPersonalData( string $emailAddress ) {

		return $this->subscriptionRepository->findSubscriptionsByPersonalData( $emailAddress );
	}

}