<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests\Unit\UseCases\AddSubscription;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;
use WMDE\Fundraising\SubscriptionContext\Validation\SubscriptionDuplicateValidator;
use WMDE\Fundraising\SubscriptionContext\Validation\SubscriptionValidator;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;
use WMDE\FunValidators\Validators\EmailValidator;

/**
 * @covers \WMDE\Fundraising\SubscriptionContext\Validation\SubscriptionValidator
 *
 * @license GPL-2.0-or-later
 */
class SubscriptionValidatorTest extends TestCase {

	/**
	 * @return SubscriptionDuplicateValidator&MockObject
	 */
	private function newPassingDuplicateValidator(): SubscriptionDuplicateValidator {
		$mock = $this->createMock( SubscriptionDuplicateValidator::class );

		$mock->method( 'validate' )->willReturn( new ValidationResult() );
		return $mock;
	}

	private function newPassingEmailValidator(): EmailValidator {
		$mock = $this->createMock( EmailValidator::class );
		$mock->method( 'validate' )->willReturn( new ValidationResult() );
		return $mock;
	}

	public function testGivenFailingEmailValidation_subscriptionValidationFails(): void {
		$mailValidator = $this->createMock( EmailValidator::class );
		$mailValidator->method( 'validate' )->willReturn( new ValidationResult(
			new ConstraintViolation( 'this is not a mail addess', 'invalid_format' ) )
		);
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
		$this->assertContainsOnlyInstancesOf( ConstraintViolation::class, $result->getViolations() );
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
		$failingDuplicateValidator = $this->createMock( SubscriptionDuplicateValidator::class );

		$failingDuplicateValidator->method( 'validate' )->willReturn( new ValidationResult(
			new ConstraintViolation( '', 'duplicate_subscription', SubscriptionDuplicateValidator::SOURCE_NAME )
		) );
		$subscriptionValidator = new SubscriptionValidator(
			$this->newPassingEmailValidator(),
			$failingDuplicateValidator
		);
		$this->assertConstraintWasViolated(
			$subscriptionValidator->validate( $this->newSubscription() ),
			SubscriptionDuplicateValidator::SOURCE_NAME
		);
	}

	public function testOnlyEmailIsARequiredField() {
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
