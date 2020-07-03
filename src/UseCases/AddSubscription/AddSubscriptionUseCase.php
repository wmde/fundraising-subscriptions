<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\UseCases\AddSubscription;

use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;
use WMDE\Fundraising\SubscriptionContext\Domain\Repositories\SubscriptionRepository;
use WMDE\Fundraising\SubscriptionContext\Domain\Repositories\SubscriptionRepositoryException;
use WMDE\Fundraising\SubscriptionContext\Infrastructure\TemplateMailerInterface;
use WMDE\Fundraising\SubscriptionContext\Validation\SubscriptionValidator;
use WMDE\FunValidators\ValidationResponse;

/**
 * @license GPL-2.0-or-later
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
class AddSubscriptionUseCase {

	private const CONFIRMATION_CODE_LENGTH_BYTES = 16;

	private $subscriptionRepository;
	private $subscriptionValidator;
	private $mailer;

	public function __construct( SubscriptionRepository $subscriptionRepository,
		SubscriptionValidator $subscriptionValidator, TemplateMailerInterface $mailer ) {
		$this->subscriptionRepository = $subscriptionRepository;
		$this->subscriptionValidator = $subscriptionValidator;
		$this->mailer = $mailer;
	}

	/**
	 * @param SubscriptionRequest $subscriptionRequest
	 * @return ValidationResponse
	 * @throws SubscriptionRepositoryException
	 */
	public function addSubscription( SubscriptionRequest $subscriptionRequest ): ValidationResponse {
		$subscription = $this->createSubscriptionFromRequest( $subscriptionRequest );

		$validationResult = $this->subscriptionValidator->validate( $subscription );

		if ( $validationResult->hasViolations() ) {
			return ValidationResponse::newFailureResponse( $validationResult->getViolations() );
		}

		$this->subscriptionRepository->storeSubscription( $subscription );

		$this->sendSubscriptionNotification( $subscription );

		return ValidationResponse::newSuccessResponse();
	}

	private function sendSubscriptionNotification( Subscription $subscription ): void {
		$this->mailer->sendMail(
			$this->newMailAddressFromSubscription( $subscription ),
			// FIXME: this is an output similar to the main response model and should similarly not be an entity
			[ 'subscription' => $subscription ]
		);
	}

	private function newMailAddressFromSubscription( Subscription $subscription ): EmailAddress {
		return new EmailAddress( $subscription->getEmail() );
	}

	private function createSubscriptionFromRequest( SubscriptionRequest $subscriptionRequest ): Subscription {
		$subscription = new Subscription();

		$subscription->setEmail( $subscriptionRequest->getEmail() );
		$subscription->setTracking( $subscriptionRequest->getTrackingString() );
		$subscription->setSource( $subscriptionRequest->getSource() );
		$subscription->setConfirmationCode( $this->generateConfirmationCode() );

		return $subscription;
	}

	private function generateConfirmationCode(): string {
		return bin2hex( random_bytes( self::CONFIRMATION_CODE_LENGTH_BYTES ) );
	}

}
