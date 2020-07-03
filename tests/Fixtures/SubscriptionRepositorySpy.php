<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests\Fixtures;

use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;
use WMDE\Fundraising\SubscriptionContext\Domain\Repositories\SubscriptionRepository;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SubscriptionRepositorySpy implements SubscriptionRepository {

	/**
	 * @var Subscription[]
	 */
	private $subscriptions = [];

	public function storeSubscription( Subscription $subscription ): void {
		$this->subscriptions[] = $subscription;
	}

	/**
	 * @return Subscription[]
	 */
	public function getSubscriptions(): array {
		return $this->subscriptions;
	}

	public function subscriptionsWereStored(): bool {
		return count( $this->subscriptions ) > 0;
	}

	public function getFirstSubscription(): Subscription {
		if ( !$this->subscriptionsWereStored() ) {
			throw new \RuntimeException( 'No Subscriptions were stored' );
		}
		return $this->subscriptions[0];
	}

	public function countSimilar( Subscription $subscription, \DateTime $cutoffDateTime ): int {
		return 0;
	}

	public function findByConfirmationCode( string $confirmationCode ): ?Subscription {
		return null;
	}

}
