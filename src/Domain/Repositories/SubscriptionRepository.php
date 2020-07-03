<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Domain\Repositories;

use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;

/**
 * @license GPL-2.0-or-later
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
interface SubscriptionRepository {

	/**
	 * Add or update a subscription.
	 *
	 * If the subscription exists in the repository, update it.
	 *
	 * @param Subscription $subscription
	 * @throws SubscriptionRepositoryException
	 */
	public function storeSubscription( Subscription $subscription ): void;

	/**
	 * Count the number of subscriptions with the same email address that were created after the cutoff date.
	 *
	 * @param Subscription $subscription
	 * @param \DateTime $cutoffDateTime
	 * @return int
	 * @throws SubscriptionRepositoryException
	 */
	public function countSimilar( Subscription $subscription, \DateTime $cutoffDateTime ): int;

	/**
	 * @param string $confirmationCode
	 * @return Subscription|null
	 * @throws SubscriptionRepositoryException
	 */
	public function findByConfirmationCode( string $confirmationCode ): ?Subscription;

}
