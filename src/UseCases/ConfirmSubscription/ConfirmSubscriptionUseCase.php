<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\UseCases\ConfirmSubscription;

use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\SubscriptionContext\Domain\Repositories\SubscriptionRepository;
use WMDE\Fundraising\SubscriptionContext\Infrastructure\TemplateMailerInterface;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResponse;

/**
 * @license GPL-2.0-or-later
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
class ConfirmSubscriptionUseCase {

	private $subscriptionRepository;

	private $mailer;

	public function __construct( SubscriptionRepository $subscriptionRepository, TemplateMailerInterface $mailer ) {
		$this->subscriptionRepository = $subscriptionRepository;
		$this->mailer = $mailer;
	}

	public function confirmSubscription( string $confirmationCode ): ValidationResponse {
		$subscription = $this->subscriptionRepository->findByConfirmationCode( $confirmationCode );

		if ( $subscription === null ) {
			return ValidationResponse::newFailureResponse( [
				new ConstraintViolation( $confirmationCode, 'subscription_confirmation_code_not_found' )
			] );
		}

		if ( $subscription->isUnconfirmed() ) {
			$subscription->markAsConfirmed();
			$this->subscriptionRepository->storeSubscription( $subscription );
			$this->mailer->sendMail( new EmailAddress( $subscription->getEmail() ), [ 'subscription' => $subscription ] );
			return ValidationResponse::newSuccessResponse();
		}

		return ValidationResponse::newFailureResponse( [
			new ConstraintViolation( $confirmationCode, 'subscription_already_confirmed' )
		] );
	}

}
