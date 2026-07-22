<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Domain;

interface SubscriptionRemover {

	/**
	 * Permanently delete individual subscriptions **without any deletion policy checks.**
	 * This is intended to be used by GDPR removal tools (e.g. Fundraising Operation Center).
	 *
	 * @param int ...$subscriptionIds
	 *
	 * @return int amount of deleted rows
	 */
	public function forceRemoveByIds( int ...$subscriptionIds ): int;

	/**
	 * Permanently delete all subscriptions that are allowed to be deleted.
	 * @return int
	 */
	public function removeAll(): int;

}
