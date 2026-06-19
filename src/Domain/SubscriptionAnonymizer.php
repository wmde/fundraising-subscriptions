<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Domain;

interface SubscriptionAnonymizer {

	/**
	 * Scrub individual subscriptions
	 *
	 * @param int ...$subscriptionIds
	 *
	 * @return void
	 */
	public function anonymizeWithIds( int ...$subscriptionIds ): void;

	/**
	 * Anonymize all subscriptions that are allowed to be scrubbed.
	 * @return int
	 */
	public function anonymizeAll(): int;

}