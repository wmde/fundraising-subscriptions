<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests\Unit\UseCases\AddSubscription;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;
use WMDE\Fundraising\SubscriptionContext\Validation\SubscriptionDuplicateValidator;
use WMDE\Fundraising\SubscriptionContext\Validation\SubscriptionValidator;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;
use WMDE\FunValidators\Validators\EmailValidator;

/**
 * @license GPL-2.0-or-later
 */
#[CoversClass( SubscriptionValidator::class )]
class SubscriptionValidatorTest extends TestCase {

	/**
	 * @return SubscriptionDuplicateValidator&Stub
	 */
	private function newPassingDuplicateValidator(): SubscriptionDuplicateValidator {
		return $this->createConfiguredStub( SubscriptionDuplicateValidator::class, [
			'validate' => new ValidationResult(),
		] );
	}

	private function newPassingEmailValidator(): EmailValidator {
		return $this->createConfiguredStub( EmailValidator::class, [
			'validate' => new ValidationResult(),
		] );
	}

	public function testGivenFailingEmailValidation_subscriptionValidationFails(): void {
		$mailValidator = $this->createConfiguredStub( EmailValidator::class, [
			'validate' => new ValidationResult(
				new ConstraintViolation( 'this is not a mail addess', 'invalid_format' )
			),
		] );
		$subscriptionValidator = new SubscriptionValidator(
			$mailValidator,
			$this->newPassingDuplicateValidator(),
		);
		$subscription = new Subscription();
		$subscription->setEmail( 'this is not a mail addess' );
		$this->assertConstraintWasViolated(
			$subscriptionValidator->validate( $subscription ),
			SubscriptionValidator::SOURCE_EMAIL
		);
	}

	private function assertConstraintWasViolated( ValidationResult $result, string $fieldName ): void {
		$this->assertTrue( $result->hasViolations() );

		$violated = false;
		foreach ( $result->getViolations() as $violation ) {
			if ( $violation->getSource() === $fieldName ) {
				$violated = true;
			}
		}

		$this->assertTrue(
			$violated,
			'Failed asserting that constraint for field "' . $fieldName . '"" was violated.'
		);
	}

	public function testGivenDuplicateSubscription_newSubscriptionIsInvalid(): void {
		$failingDuplicateValidator = $this->createConfiguredStub( SubscriptionDuplicateValidator::class, [
			'validate' => new ValidationResult(
				new ConstraintViolation( '', 'duplicate_subscription', SubscriptionDuplicateValidator::SOURCE_NAME )
			),
		] );
		$subscriptionValidator = new SubscriptionValidator(
			$this->newPassingEmailValidator(),
			$failingDuplicateValidator
		);
		$this->assertConstraintWasViolated(
			$subscriptionValidator->validate( $this->newSubscription() ),
			SubscriptionDuplicateValidator::SOURCE_NAME
		);
	}

	public function testOnlyEmailIsARequiredField(): void {
		$subscriptionValidator = new SubscriptionValidator(
			$this->newPassingEmailValidator(),
			$this->newPassingDuplicateValidator()
		);

		$subscription = new Subscription();
		$subscription->setEmail( 'nyan@meow.com' );

		$this->assertTrue(
			$subscriptionValidator->validate( $subscription )->isSuccessful()
		);

		$subscription->setEmail( '' );

		$this->assertConstraintWasViolated(
			$subscriptionValidator->validate( $subscription ),
			SubscriptionValidator::SOURCE_EMAIL
		);
	}

	private function newSubscription(): Subscription {
		$subscription = new Subscription();
		$subscription->setEmail( 'nyan@meow.com' );
		return $subscription;
	}
}
