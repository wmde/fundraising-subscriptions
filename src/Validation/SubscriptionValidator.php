<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Validation;

use WMDE\Fundraising\Entities\Subscription;
use WMDE\Fundraising\SubscriptionContext\Domain\Repositories\SubscriptionRepositoryException;
use WMDE\FunValidators\CanValidateField;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;
use WMDE\FunValidators\Validators\AllowedValuesValidator;
use WMDE\FunValidators\Validators\EmailValidator;
use WMDE\FunValidators\Validators\RequiredFieldValidator;
use WMDE\FunValidators\Validators\TextPolicyValidator;

/**
 * @license GNU GPL v2+
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
class SubscriptionValidator {
	use CanValidateField;

	public const SOURCE_EMAIL = 'email';
	public const SOURCE_TITLE = 'title';

	private $mailValidator;
	private $duplicateValidator;
	private $textPolicyValidator;
	private $textPolicyViolations;
	private $titleValidator;

	public function __construct( EmailValidator $mailValidator, TextPolicyValidator $textPolicyValidator,
								 SubscriptionDuplicateValidator $duplicateValidator,
								 AllowedValuesValidator $titleValidator ) {
		$this->mailValidator = $mailValidator;
		$this->textPolicyValidator = $textPolicyValidator;
		$this->duplicateValidator = $duplicateValidator;
		$this->titleValidator = $titleValidator;
		$this->textPolicyViolations = [];
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
			[ $this->getFieldViolation(
				$this->titleValidator->validate( $subscription->getAddress()->getTitle() ),
				self::SOURCE_TITLE
			) ],
			$this->duplicateValidator->validate( $subscription )->getViolations() )
		) );
	}

	public function needsModeration( Subscription $subscription ): bool {
		$allWordsAreHarmless = array_reduce(
			$this->getBadWordViolations( $subscription ),
			function ( $previousWordsWereHarmless, $currentWordIsHarmless ) {
				return $previousWordsWereHarmless && $currentWordIsHarmless;
			},
			true
		);
		return !$allWordsAreHarmless;
	}

	private function getRequiredFieldViolations( Subscription $subscription ): array {
		$validator = new RequiredFieldValidator();

		return [
			$this->getFieldViolation( $validator->validate( $subscription->getEmail() ), self::SOURCE_EMAIL )
		];
	}

	private function getBadWordViolations( Subscription $subscription ): array {
		$address = $subscription->getAddress();
		return [
			$this->textPolicyValidator->textIsHarmless( $address->getFirstName() ),
			$this->textPolicyValidator->textIsHarmless( $address->getLastName() ),
			$this->textPolicyValidator->textIsHarmless( $address->getCompany() ),
			$this->textPolicyValidator->textIsHarmless( $address->getAddress() ),
			$this->textPolicyValidator->textIsHarmless( $address->getPostcode() ),
			$this->textPolicyValidator->textIsHarmless( $address->getCity() )
		];
	}

	/**
	 * @return ConstraintViolation[]
	 */
	public function getTextPolicyViolations(): array {
		return $this->textPolicyViolations;
	}

}