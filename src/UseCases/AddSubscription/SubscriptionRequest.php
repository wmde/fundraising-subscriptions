<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\UseCases\AddSubscription;

/**
 * @license GNU GPL v2+
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
class SubscriptionRequest {

	private string $email = '';
	private string $trackingString = '';
	private string $source = '';

	public function getEmail(): string {
		return $this->email;
	}

	public function setEmail( string $email ): void {
		$this->email = $email;
	}

	public function getTrackingString(): string {
		return $this->trackingString;
	}

	public function setTrackingString( string $trackingString ): void {
		$this->trackingString = $trackingString;
	}

	public function getSource(): string {
		return $this->source;
	}

	public function setSource( string $source ): void {
		$this->source = $source;
	}

}