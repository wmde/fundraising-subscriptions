<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests\Integration\UseCases\ConfirmSubscription;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\Entities\Address;
use WMDE\Fundraising\Entities\Subscription;
use WMDE\Fundraising\SubscriptionContext\Infrastructure\TemplateMailerInterface;
use WMDE\Fundraising\SubscriptionContext\Tests\Fixtures\InMemorySubscriptionRepository;
use WMDE\Fundraising\SubscriptionContext\UseCases\ConfirmSubscription\ConfirmSubscriptionUseCase;

/**
 * @covers \WMDE\Fundraising\SubscriptionContext\UseCases\ConfirmSubscription\ConfirmSubscriptionUseCase
 *
 * @license GNU GPL v2+
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
class ConfirmSubscriptionUseCaseTest extends TestCase {

	const CONFIRMATION_CODE = 'deadbeef';

	private function newSubscriptionAddress(): Address {
		$address = new Address();

		$address->setSalutation( 'Herr' );
		$address->setFirstName( 'Nyan' );
		$address->setLastName( 'Cat' );
		$address->setTitle( 'Dr.' );

		return $address;
	}

	private function newSubscription(): Subscription {
		$subscription = new Subscription();

		$subscription->setConfirmationCode( self::CONFIRMATION_CODE );
		$subscription->setEmail( 'nyan@awesomecats.com' );
		$subscription->setAddress( $this->newSubscriptionAddress() );

		return $subscription;
	}

	/**
	 * @return TemplateMailerInterface&MockObject
	 */
	private function newMailer() {
		return $this->createMock( TemplateMailerInterface::class );
	}

	public function testGivenNoSubscriptions_anErrorResponseIsCreated(): void {
		$mailer = $this->newMailer();
		$mailer->expects( $this->never() )->method( 'sendMail' );
		$useCase = new ConfirmSubscriptionUseCase( new InMemorySubscriptionRepository(), $mailer );
		$result = $useCase->confirmSubscription( self::CONFIRMATION_CODE );
		$this->assertFalse( $result->isSuccessful() );
	}

	public function testGivenASubscriptionWithWrongStatus_anErrorResponseIsCreated(): void {
		$subscription = $this->newSubscription();
		$subscription->markAsConfirmed();

		$repo = new InMemorySubscriptionRepository();
		$repo->storeSubscription( $subscription );

		$mailer = $this->newMailer();
		$mailer->expects( $this->never() )->method( 'sendMail' );

		$useCase = new ConfirmSubscriptionUseCase( $repo, $mailer );

		$this->assertFalse( $useCase->confirmSubscription( self::CONFIRMATION_CODE )->isSuccessful() );
	}

	public function testGivenASubscription_aSuccessIsCreated(): void {
		$repo = new InMemorySubscriptionRepository();
		$repo->storeSubscription( $this->newSubscription() );

		$useCase = new ConfirmSubscriptionUseCase( $repo, $this->newMailer() );

		$this->assertTrue( $useCase->confirmSubscription( self::CONFIRMATION_CODE )->isSuccessful() );
	}

	public function testGivenASubscription_statusIsSetToConfirmed(): void {
		$repo = new InMemorySubscriptionRepository();
		$repo->storeSubscription( $this->newSubscription() );

		$useCase = new ConfirmSubscriptionUseCase( $repo, $this->newMailer() );
		$useCase->confirmSubscription( self::CONFIRMATION_CODE );

		$this->assertFalse( $repo->getSubscriptions()[0]->isUnconfirmed(), 'Status needs to be set to confirmed' );
	}

	public function testGivenASubscription_aConfirmationMailIsSent(): void {
		$repo = new InMemorySubscriptionRepository();
		$repo->storeSubscription( $this->newSubscription() );

		$mailer = $this->newMailer();
		$mailer->expects( $this->once() )->method( 'sendMail' );

		$useCase = new ConfirmSubscriptionUseCase( $repo, $mailer );

		$this->assertTrue( $useCase->confirmSubscription( self::CONFIRMATION_CODE )->isSuccessful() );
	}
}
