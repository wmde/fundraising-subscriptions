<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Domain\Model;

use DateTime;

class Subscription {

	private string $email = '';

	private ?DateTime $export;

	private ?DateTime $backup;

	private int $status = 0;

	private string $confirmationCode;

	/**
	 * @var int
	 * @phpstan-ignore-next-line
	 */
	private int $id;

	private string $tracking;

	private string $source;

	private DateTime $createdAt;

	private const STATUS_NEW = 0;
	private const STATUS_CONFIRMED = 1;

	public function __construct() {
		$this->setStatus( self::STATUS_NEW );
	}

	public function setEmail( string $email ): self {
		$this->email = $email;

		return $this;
	}

	public function getEmail(): string {
		return $this->email;
	}

	public function setExport( DateTime $export = null ): self {
		$this->export = $export;

		return $this;
	}

	public function getExport(): ?DateTime {
		return $this->export;
	}

	public function setBackup( DateTime $backup = null ): self {
		$this->backup = $backup;

		return $this;
	}

	public function getBackup(): ?DateTime {
		return $this->backup;
	}

	/**
	 * Usage of this method is discouraged, it's only for initialization with Doctrine.
	 *
	 * @see Subscription::markAsConfirmed()
	 * @see Subscription::markForModeration()
	 * @param int $status
	 * @return Subscription
	 */
	public function setStatus( int $status ): self {
		$this->status = $status;

		return $this;
	}

	/**
	 * Usage of this method is discouraged. Try using something like @see isUnconfirmed
	 *
	 * @return int
	 */
	public function getStatus(): int {
		return $this->status;
	}

	public function setConfirmationCode( string $confirmationCode ): self {
		$this->confirmationCode = $confirmationCode;

		return $this;
	}

	public function getConfirmationCode(): string {
		return $this->confirmationCode;
	}

	public function getId(): int {
		return $this->id;
	}

	public function getCreatedAt(): DateTime {
		return $this->createdAt;
	}

	public function setCreatedAt( DateTime $createdAt ): self {
		$this->createdAt = $createdAt;
		return $this;
	}

	public function getTracking(): string {
		return $this->tracking;
	}

	public function setTracking( string $tracking ): void {
		$this->tracking = $tracking;
	}

	public function setSource( string $source ): self {
		$this->source = $source;

		return $this;
	}

	public function getSource(): string {
		return $this->source;
	}

	public function markAsConfirmed(): void {
		$this->status = self::STATUS_CONFIRMED;
	}

	public function isConfirmed(): bool {
		return $this->status === self::STATUS_CONFIRMED;
	}

	public function isUnconfirmed(): bool {
		return $this->status === self::STATUS_NEW;
	}

}
