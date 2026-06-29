<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Domain;

interface SubscriptionAnonymizer {

	/**
	 * Scrub individual subscriptions
	 *
	 * @param int ...$subscriptionIds
	 *
	 * @return int amount of deleted rows
	 */
	public function anonymizeWithIds( int ...$subscriptionIds ): int;

	/**
	 * Anonymize all subscriptions that are allowed to be scrubbed.
	 * @return int
	 */
	public function anonymizeAll(): int;

}
