<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\DataAccess;

/**
 * This class contains methods to monitor the amount of old subscriptions in the database (which might still
 * contain private data).
 * We use them to check whether our private data deletion processes work correctly.
 */
interface SubscriptionRemovalMonitor {

	/**
	 * @return int amount of old subscriptions in the database that did not get scrubbed (deleted in this case) e.g. due to errors
	 */
	public function countUnremovedSubscriptions(): int;
}
