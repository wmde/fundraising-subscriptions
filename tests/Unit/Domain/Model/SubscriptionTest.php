<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;

/**
 * @covers WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription
 *
 * @licence GNU GPL v2+
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SubscriptionTest extends TestCase {

	public function testSetAndGetSource() {
		$subscription = new Subscription();
		$subscription->setSource( 'foobar' );
		$this->assertSame( 'foobar', $subscription->getSource() );
	}

	public function testWhenSubscriptionIsNew_isUnconfirmedReturnsTrue() {
		$this->assertTrue( ( new Subscription() )->isUnconfirmed() );
	}

	public function testWhenConfirmed_isUnconfirmedReturnsFalse() {
		$subscription = new Subscription();
		$subscription->markAsConfirmed();

		$this->assertFalse( $subscription->isUnconfirmed() );
	}

}
