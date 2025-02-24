<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Validation;

use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;
use WMDE\Fundraising\SubscriptionContext\Domain\Repositories\SubscriptionRepositoryException;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;
use WMDE\FunValidators\Validators\EmailValidator;
use WMDE\FunValidators\Validators\RequiredFieldValidator;

/**
 * @license GPL-2.0-or-later
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
class SubscriptionValidator {

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
			$this->mailValidator->validate( $subscription->getEmail() )->setSourceForAllViolations( self::SOURCE_EMAIL )->getViolations(),
			$this->duplicateValidator->validate( $subscription )->getViolations() )
		) );
	}

	/**
	 * @param Subscription $subscription
	 * @return array<ConstraintViolation|null>
	 */
	private function getRequiredFieldViolations( Subscription $subscription ): array {
		$validator = new RequiredFieldValidator();

		return $validator->validate( $subscription->getEmail() )->setSourceForAllViolations( self::SOURCE_EMAIL )->getViolations();
	}

}
