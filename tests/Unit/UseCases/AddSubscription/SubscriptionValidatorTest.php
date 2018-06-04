<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests\Unit\UseCases\AddSubscription;

use PHPUnit\Framework\TestCase;

use WMDE\Fundraising\Entities\Address;
use WMDE\Fundraising\Entities\Subscription;
use WMDE\Fundraising\SubscriptionContext\Validation\SubscriptionDuplicateValidator;
use WMDE\Fundraising\SubscriptionContext\Validation\SubscriptionValidator;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;
use WMDE\FunValidators\Validators\AllowedValuesValidator;
use WMDE\FunValidators\Validators\EmailValidator;
use WMDE\FunValidators\Validators\TextPolicyValidator;

/**
 * @covers \WMDE\Fundraising\SubscriptionContext\Validation\SubscriptionValidator
 *
 * @license GNU GPL v2+
 */
class SubscriptionValidatorTest extends TestCase {

	private function newPassingTextPolicyValidator(): TextPolicyValidator {
		$mock = $this->createMock( TextPolicyValidator::class );
		$mock->method( 'textIsHarmless' )
			->willReturn( true );
		return $mock;
	}

	private function newPassingDuplicateValidator(): SubscriptionDuplicateValidator {
		$mock = $this->getMockBuilder( SubscriptionDuplicateValidator::class )
			->disableOriginalConstructor()->getMock();

		$mock->method( 'validate' )->willReturn( new ValidationResult() );
		return $mock;
	}

	private function newPassingEmailValidator(): EmailValidator {
		$mock = $this->createMock( EmailValidator::class );
		$mock->method( 'validate' )->willReturn( new ValidationResult() );
		return $mock;
	}

	private function newPassingTitleValidator(): AllowedValuesValidator {
		$mock = $this->createMock( AllowedValuesValidator::class );
		$mock->method( 'validate' )->willReturn( new ValidationResult() );
		return $mock;
	}

	private function createValidAddress( string $saluation, string $firstName, string $lastName ): Address {
		$address = new Address();
		$address->setSalutation( $saluation );
		$address->setFirstName( $firstName );
		$address->setLastName( $lastName );
		$address->setTitle( '' );
		$address->setCompany( '' );
		$address->setAddress( '' );
		$address->setCity( '' );
		$address->setPostcode( '' );
		return $address;
	}

	public function testGivenFailingEmailValidation_subscriptionValidationFails(): void {
		$mailValidator = $this->createMock( EmailValidator::class );
		$mailValidator->method( 'validate' )->willReturn( new ValidationResult(
			new ConstraintViolation( 'this is not a mail addess', 'invalid_format' ) )
		);
		$subscriptionValidator = new SubscriptionValidator(
			$mailValidator,
			$this->newPassingTextPolicyValidator(),
			$this->newPassingDuplicateValidator(),
			$this->newPassingTitleValidator()
		);
		$subscription = new Subscription();
		$subscription->setAddress( $this->createValidAddress( 'Herr', 'Nyan', 'Cat' ) );
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

	public function testGivenBadWords_subscriptionIsStillValid(): void {
		$policyValidator = $this->createMock( TextPolicyValidator::class );
		$policyValidator->method( 'hasHarmlessContent' )
			->willReturn( false );
		$subscriptionValidator = new SubscriptionValidator(
			$this->newPassingEmailValidator(),
			$policyValidator,
			$this->newPassingDuplicateValidator(),
			$this->newPassingTitleValidator()
		);
		$this->assertTrue( $subscriptionValidator->validate( $this->newSubscription() )->isSuccessful() );
	}

	public function testGivenHarmlessContent_needsModerationIsFalse(): void {
		$subscriptionValidator = new SubscriptionValidator(
			$this->newPassingEmailValidator(),
			$this->newPassingTextPolicyValidator(),
			$this->newPassingDuplicateValidator(),
			$this->newPassingTitleValidator()
		);

		$this->assertFalse( $subscriptionValidator->needsModeration( $this->newSubscription() ) );
	}

	public function testGivenBadWords_needsModerationIsTrue(): void {
		$policyValidator = $this->createMock( TextPolicyValidator::class );
		$policyValidator->method( 'textIsHarmless' )
			->willReturn( false );
		$subscriptionValidator = new SubscriptionValidator(
			$this->newPassingEmailValidator(),
			$policyValidator,
			$this->newPassingDuplicateValidator(),
			$this->newPassingTitleValidator()
		);

		$this->assertTrue( $subscriptionValidator->needsModeration( $this->newSubscription() ) );
	}

	public function testGivenDuplicateSubscription_newSubscriptionIsInvalid(): void {
		$failingDuplicateValidator = $this->createMock( SubscriptionDuplicateValidator::class );

		$failingDuplicateValidator->method( 'validate' )->willReturn( new ValidationResult(
			new ConstraintViolation( '', 'duplicate_subscription', SubscriptionDuplicateValidator::SOURCE_NAME )
		) );
		$subscriptionValidator = new SubscriptionValidator(
			$this->newPassingEmailValidator(),
			$this->newPassingTextPolicyValidator(),
			$failingDuplicateValidator,
			$this->newPassingTitleValidator()
		);
		$this->assertConstraintWasViolated(
			$subscriptionValidator->validate( $this->newSubscription() ),
			SubscriptionDuplicateValidator::SOURCE_NAME
		);
	}

	public function testOnlyEmailIsARequiredField() {
		$subscriptionValidator = new SubscriptionValidator(
			$this->newPassingEmailValidator(),
			$this->newPassingTextPolicyValidator(),
			$this->newPassingDuplicateValidator(),
			$this->newPassingTitleValidator()
		);

		$subscription = new Subscription();
		$subscription->setAddress( $this->createValidAddress( '', '', '' ) );
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

	public function testGivenAFailingTitleValidator_subscriptionValidationFails(): void {
		$failingTitleValidator = $this->createMock( AllowedValuesValidator::class );
		$failingTitleValidator->method( 'validate' )->willReturn( new ValidationResult(
			new ConstraintViolation( '', 'not_allowed' )
		) );

		$subscriptionValidator = new SubscriptionValidator(
			$this->newPassingEmailValidator(),
			$this->newPassingTextPolicyValidator(),
			$this->newPassingDuplicateValidator(),
			$failingTitleValidator
		);
		$this->assertConstraintWasViolated(
			$subscriptionValidator->validate( $this->newSubscription() ),
			SubscriptionValidator::SOURCE_TITLE
		);
	}

	private function newSubscription(): Subscription {
		$subscription = new Subscription();
		$subscription->setAddress( $this->createValidAddress( 'Herr', 'Nyan', 'Cat' ) );
		$subscription->setEmail( 'nyan@meow.com' );
		return $subscription;
	}
}
