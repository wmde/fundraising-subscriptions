<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\SubscriptionContext\Domain\AnonymizationException;
use WMDE\Fundraising\SubscriptionContext\Domain\SubscriptionAnonymizer;

class DatabaseSubscriptionAnonymizer implements SubscriptionAnonymizer {

	public function __construct(
		private readonly DoctrineSubscriptionRepository $subscriptionRepository,
		private readonly EntityManager $entityManager
	) {
	}

	public function anonymizeWithIds( int ...$subscriptionIds ): void {
		foreach ( $subscriptionIds as $subscriptionId ) {
			$subscription = $this->subscriptionRepository->getSubscriptionById( $subscriptionId );
			if( $subscription == null ){
				throw new AnonymizationException( "Could not find subscription with id $subscriptionId" );
			}

			$subscription->scrubPersonalData();
			$this->subscriptionRepository->storeSubscription( $subscription );
		}
	}

	public function anonymizeAll(): int {
		// TODO: Implement anonymizeAll() method.
	}
}