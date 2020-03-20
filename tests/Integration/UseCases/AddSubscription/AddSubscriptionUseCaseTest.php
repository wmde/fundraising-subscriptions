<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests\Integration\UseCases\AddSubscription;

use PHPUnit\Framework\MockObject\MockObject;
use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\Entities\Subscription;
use WMDE\Fundraising\SubscriptionContext\Infrastructure\TemplateMailerInterface;
use WMDE\Fundraising\SubscriptionContext\Tests\Fixtures\SubscriptionRepositorySpy;
use WMDE\Fundraising\SubscriptionContext\UseCases\AddSubscription\AddSubscriptionUseCase;
use WMDE\Fundraising\SubscriptionContext\UseCases\AddSubscription\SubscriptionRequest;
use WMDE\Fundraising\SubscriptionContext\Validation\SubscriptionValidator;
use WMDE\Fundraising\SubscriptionContext\Tests\Fixtures\FailedValidationResult;
use WMDE\FunValidators\ValidationResult;
use PHPUnit\Framework\TestCase;

/**
 * @covers \WMDE\Fundraising\SubscriptionContext\UseCases\AddSubscription\AddSubscriptionUseCase
 *
 * @license GNU GPL v2+
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
class AddSubscriptionUseCaseTest extends TestCase {

	private const A_SPECIFIC_EMAIL_ADDRESS = 'curious@nyancat.com';

	/**
	 * @var SubscriptionRepositorySpy
	 */
	private $repo;

	/**
	 * @var SubscriptionValidator&MockObject
	 */
	private $validator;

	/**
	 * @var TemplateMailerInterface&MockObject
	 */
	private $mailer;

	public function setUp(): void {
		$this->repo = new SubscriptionRepositorySpy();

		$this->validator = $this->createMock( SubscriptionValidator::class );

		$this->mailer = $this->createMock( TemplateMailerInterface::class );
	}

	private function createValidSubscriptionRequest(): SubscriptionRequest {
		$request = new SubscriptionRequest();
		$request->setEmail( self::A_SPECIFIC_EMAIL_ADDRESS );
		return $request;
	}

	public function testGivenValidData_aSuccessResponseIsCreated(): void {
		$this->validator->method( 'validate' )->willReturn( new ValidationResult() );
		$useCase = new AddSubscriptionUseCase( $this->repo, $this->validator, $this->mailer );
		$result = $useCase->addSubscription( $this->createValidSubscriptionRequest() );

		$this->assertTrue( $result->isSuccessful() );
	}

	public function testGivenInvalidData_anErrorResponseTypeIsCreated(): void {
		$this->validator->method( 'validate' )->willReturn( new FailedValidationResult() );
		$useCase = new AddSubscriptionUseCase( $this->repo, $this->validator, $this->mailer );
		$request = $this->createMock( SubscriptionRequest::class );

		$result = $useCase->addSubscription( $request );

		$this->assertFalse( $result->isSuccessful() );
	}

	public function testGivenValidData_requestWillBeStored(): void {
		$this->validator->method( 'validate' )->willReturn( new ValidationResult() );
		$useCase = new AddSubscriptionUseCase( $this->repo, $this->validator, $this->mailer );

		$useCase->addSubscription( $this->createValidSubscriptionRequest() );

		$this->assertTrue( $this->repo->subscriptionsWereStored() );
	}

	public function testGivenValidData_subscriptionContainsEmptyCompanyName(): void {
		$this->validator->method( 'validate' )->willReturn( new ValidationResult() );
		$useCase = new AddSubscriptionUseCase( $this->repo, $this->validator, $this->mailer );

		$useCase->addSubscription( $this->createValidSubscriptionRequest() );

		$this->assertSame( '', $this->repo->getFirstSubscription()->getAddress()->getCompany() );
	}

	public function testGivenDataThatNeedsToBeModerated_requestWillBeStored(): void {
		$this->validator->method( 'validate' )->willReturn( new ValidationResult() );
		$this->validator->method( 'needsModeration' )->willReturn( true );

		$useCase = new AddSubscriptionUseCase( $this->repo, $this->validator, $this->mailer );
		$useCase->addSubscription( $this->createValidSubscriptionRequest() );

		$this->assertTrue( $this->repo->getFirstSubscription()->needsModeration() );
	}

	public function testGivenInvalidData_requestWillNotBeStored(): void {
		$this->validator->method( 'validate' )->willReturn( new FailedValidationResult() );
		$useCase = new AddSubscriptionUseCase( $this->repo, $this->validator, $this->mailer );
		$request = $this->createMock( SubscriptionRequest::class );

		$useCase->addSubscription( $request );

		$this->assertFalse( $this->repo->subscriptionsWereStored() );
	}

	public function testGivenValidData_requestWillBeMailed(): void {
		$this->validator->method( 'validate' )->willReturn( new ValidationResult() );
		$this->mailer->expects( $this->once() )
			->method( 'sendMail' )
			->with(
				$this->equalTo( new EmailAddress( self::A_SPECIFIC_EMAIL_ADDRESS ) ),
				$this->callback( function ( $value ) {
					$this->assertIsArray( $value );
					$this->assertArrayHasKey( 'subscription', $value );
					$this->assertInstanceOf( Subscription::class, $value['subscription'] );

					// FIXME: actual template params are not tested
					// (and some of the used data is not even in the test request model)

					return true;
				} )
			);
		$useCase = new AddSubscriptionUseCase( $this->repo, $this->validator, $this->mailer );
		$useCase->addSubscription( $this->createValidSubscriptionRequest() );
	}

	public function testGivenInvalidData_requestWillNotBeMailed(): void {
		$this->validator->method( 'validate' )->willReturn( new FailedValidationResult() );
		$this->mailer->expects( $this->never() )->method( 'sendMail' );
		$useCase = new AddSubscriptionUseCase( $this->repo, $this->validator, $this->mailer );
		$request = $this->createMock( SubscriptionRequest::class );
		$useCase->addSubscription( $request );
	}

	public function testGivenDataThatNeedsToBeModerated_requestNotBeMailed(): void {
		$this->validator->method( 'validate' )->willReturn( new ValidationResult() );
		$this->validator->method( 'needsModeration' )->willReturn( true );
		$this->mailer->expects( $this->never() )->method( 'sendMail' );
		$useCase = new AddSubscriptionUseCase( $this->repo, $this->validator, $this->mailer );
		$request = $this->createMock( SubscriptionRequest::class );
		$useCase->addSubscription( $request );
	}

}
