<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests\Fixtures;

use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;
use WMDE\Fundraising\SubscriptionContext\Domain\Repositories\SubscriptionRepository;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class InMemorySubscriptionRepository implements SubscriptionRepository {

	/**
	 * @var Subscription[]
	 */
	private $subscriptions = [];

	public function storeSubscription( Subscription $subscription ): void {
		$subscriptionKey = array_search( $subscription, $this->subscriptions, true );
		if ( $subscriptionKey === false ) {
			$subscriptionKey = count( $this->subscriptions );
		}
		$this->subscriptions[$subscriptionKey] = $subscription;
	}

	/**
	 * @return Subscription[]
	 */
	public function getSubscriptions(): array {
		return $this->subscriptions;
	}

	public function countSimilar( Subscription $subscription, \DateTime $cutoffDateTime ): int {
		$count = 0;
		foreach ( $this->subscriptions as $sub ) {
			if ( $sub->getEmail() == $subscription->getEmail() && $subscription->getCreatedAt() > $cutoffDateTime ) {
				$count++;
			}
		}
		return $count;
	}

	public function findByConfirmationCode( string $confirmationCode ): ?Subscription {
		foreach ( $this->subscriptions as $subscription ) {
			if ( $subscription->getConfirmationCode() === $confirmationCode ) {
				return $subscription;
			}
		}

		return null;
	}

}
