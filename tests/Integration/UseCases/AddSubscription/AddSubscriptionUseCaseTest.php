<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests\Integration\UseCases\AddSubscription;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;
use WMDE\Fundraising\SubscriptionContext\Infrastructure\TemplateMailerInterface;
use WMDE\Fundraising\SubscriptionContext\Tests\Fixtures\FailedValidationResult;
use WMDE\Fundraising\SubscriptionContext\Tests\Fixtures\SubscriptionRepositorySpy;
use WMDE\Fundraising\SubscriptionContext\UseCases\AddSubscription\AddSubscriptionUseCase;
use WMDE\Fundraising\SubscriptionContext\UseCases\AddSubscription\SubscriptionRequest;
use WMDE\Fundraising\SubscriptionContext\Validation\SubscriptionValidator;
use WMDE\FunValidators\ValidationResult;

#[CoversClass( AddSubscriptionUseCase::class )]
class AddSubscriptionUseCaseTest extends TestCase {

	private const A_SPECIFIC_EMAIL_ADDRESS = 'curious@nyancat.com';

	/**
	 * @var SubscriptionRepositorySpy
	 */
	private $repo;

	/**
	 * @var SubscriptionValidator&Stub
	 */
	private $validator;

	/**
	 * @var TemplateMailerInterface&Stub
	 */
	private $mailer;

	public function setUp(): void {
		$this->repo = new SubscriptionRepositorySpy();

		$this->validator = $this->createStub( SubscriptionValidator::class );

		$this->mailer = $this->createStub( TemplateMailerInterface::class );
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
		$request = $this->createStub( SubscriptionRequest::class );

		$result = $useCase->addSubscription( $request );

		$this->assertFalse( $result->isSuccessful() );
	}

	public function testGivenValidData_requestWillBeStored(): void {
		$this->validator->method( 'validate' )->willReturn( new ValidationResult() );
		$useCase = new AddSubscriptionUseCase( $this->repo, $this->validator, $this->mailer );

		$useCase->addSubscription( $this->createValidSubscriptionRequest() );

		$this->assertTrue( $this->repo->subscriptionsWereStored() );
	}

	public function testGivenInvalidData_requestWillNotBeStored(): void {
		$this->validator->method( 'validate' )->willReturn( new FailedValidationResult() );
		$useCase = new AddSubscriptionUseCase( $this->repo, $this->validator, $this->mailer );
		$request = $this->createStub( SubscriptionRequest::class );

		$useCase->addSubscription( $request );

		$this->assertFalse( $this->repo->subscriptionsWereStored() );
	}

	public function testGivenValidData_requestWillBeMailed(): void {
		$this->validator->method( 'validate' )->willReturn( new ValidationResult() );
		$this->mailer = $this->createMock( TemplateMailerInterface::class );
		$this->mailer->expects( $this->once() )
			->method( 'sendMail' )
			->with(
				new EmailAddress( self::A_SPECIFIC_EMAIL_ADDRESS ),
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
		$this->mailer = $this->createMock( TemplateMailerInterface::class );
		$this->mailer->expects( $this->never() )->method( 'sendMail' );
		$useCase = new AddSubscriptionUseCase( $this->repo, $this->validator, $this->mailer );
		$request = $this->createStub( SubscriptionRequest::class );
		$useCase->addSubscription( $request );
	}

}
