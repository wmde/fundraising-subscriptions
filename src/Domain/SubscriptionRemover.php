<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Domain;

interface SubscriptionRemover {

	/**
	 * Permanently delete individual subscriptions
	 *
	 * @param int ...$subscriptionIds
	 *
	 * @return int amount of deleted rows
	 */
	public function removeByIds( int ...$subscriptionIds ): int;

	/**
	 * Permanently delete all subscriptions that are allowed to be deleted.
	 * @return int
	 */
	public function removeAll(): int;

}
