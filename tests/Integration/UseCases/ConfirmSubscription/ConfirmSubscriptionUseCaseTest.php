<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests\Integration\UseCases\ConfirmSubscription;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;
use WMDE\Fundraising\SubscriptionContext\Infrastructure\TemplateMailerInterface;
use WMDE\Fundraising\SubscriptionContext\Tests\Fixtures\InMemorySubscriptionRepository;
use WMDE\Fundraising\SubscriptionContext\UseCases\ConfirmSubscription\ConfirmSubscriptionUseCase;

/**
 *
 * @license GPL-2.0-or-later
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
#[CoversClass( ConfirmSubscriptionUseCase::class )]
class ConfirmSubscriptionUseCaseTest extends TestCase {

	private const CONFIRMATION_CODE = 'deadbeef';

	private function newSubscription(): Subscription {
		$subscription = new Subscription();

		$subscription->setConfirmationCode( self::CONFIRMATION_CODE );
		$subscription->setEmail( 'nyan@awesomecats.com' );

		return $subscription;
	}

	/**
	 * @return TemplateMailerInterface&MockObject
	 */
	private function newMailer(): TemplateMailerInterface {
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
