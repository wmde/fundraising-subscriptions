<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;

/**
 * @covers \WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription
 */
class SubscriptionTest extends TestCase {

	public function testSetAndGetSource(): void {
		$subscription = new Subscription();
		$subscription->setSource( 'foobar' );
		$this->assertSame( 'foobar', $subscription->getSource() );
	}

	public function testWhenSubscriptionIsNew_isUnconfirmedReturnsTrue(): void {
		$this->assertTrue( ( new Subscription() )->isUnconfirmed() );
	}

	public function testWhenConfirmed_isUnconfirmedReturnsFalse(): void {
		$subscription = new Subscription();
		$subscription->markAsConfirmed();

		$this->assertFalse( $subscription->isUnconfirmed() );
	}

}
