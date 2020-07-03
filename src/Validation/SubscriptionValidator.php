<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Validation;

use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;
use WMDE\Fundraising\SubscriptionContext\Domain\Repositories\SubscriptionRepositoryException;
use WMDE\FunValidators\CanValidateField;
use WMDE\FunValidators\ValidationResult;
use WMDE\FunValidators\Validators\EmailValidator;
use WMDE\FunValidators\Validators\RequiredFieldValidator;

/**
 * @license GPL-2.0-or-later
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
class SubscriptionValidator {
	use CanValidateField;

	public const SOURCE_EMAIL = 'email';

	private EmailValidator $mailValidator;
	private SubscriptionDuplicateValidator $duplicateValidator;

	public function __construct( EmailValidator $mailValidator, SubscriptionDuplicateValidator $duplicateValidator ) {
		$this->mailValidator = $mailValidator;
		$this->duplicateValidator = $duplicateValidator;
	}

	/**
	 * @param Subscription $subscription
	 * @return ValidationResult
	 * @throws SubscriptionRepositoryException
	 */
	public function validate( Subscription $subscription ): ValidationResult {
		return new ValidationResult( ...array_filter( array_merge(
			$this->getRequiredFieldViolations( $subscription ),
			[ $this->getFieldViolation( $this->mailValidator->validate( $subscription->getEmail() ), self::SOURCE_EMAIL ) ],
			$this->duplicateValidator->validate( $subscription )->getViolations() )
		) );
	}

	private function getRequiredFieldViolations( Subscription $subscription ): array {
		$validator = new RequiredFieldValidator();

		return [
			$this->getFieldViolation( $validator->validate( $subscription->getEmail() ), self::SOURCE_EMAIL )
		];
	}

}
