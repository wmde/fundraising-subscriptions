<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Infrastructure;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;
use WMDE\Fundraising\SubscriptionContext\Domain\Repositories\SubscriptionRepository;
use WMDE\Fundraising\SubscriptionContext\Domain\Repositories\SubscriptionRepositoryException;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LoggingSubscriptionRepository implements SubscriptionRepository {

	private const CONTEXT_EXCEPTION_KEY = 'exception';

	private SubscriptionRepository $repository;
	private LoggerInterface $logger;
	private string $logLevel;

	public function __construct( SubscriptionRepository $repository, LoggerInterface $logger ) {
		$this->repository = $repository;
		$this->logger = $logger;
		$this->logLevel = LogLevel::CRITICAL;
	}

	/**
	 * @see SubscriptionRepository::storeSubscription
	 *
	 * @param Subscription $subscription
	 *
	 * @throws SubscriptionRepositoryException
	 */
	public function storeSubscription( Subscription $subscription ): void {
		try {
			$this->repository->storeSubscription( $subscription );
		} catch ( SubscriptionRepositoryException $ex ) {
			$this->logger->log( $this->logLevel, $ex->getMessage(), [ self::CONTEXT_EXCEPTION_KEY => $ex ] );
			throw $ex;
		}
	}

	/**
	 * @see SubscriptionRepository::countSimilar
	 *
	 * @param Subscription $subscription
	 * @param \DateTime $cutoffDateTime
	 *
	 * @return int
	 * @throws SubscriptionRepositoryException
	 */
	public function countSimilar( Subscription $subscription, \DateTime $cutoffDateTime ): int {
		try {
			return $this->repository->countSimilar( $subscription, $cutoffDateTime );
		} catch ( SubscriptionRepositoryException $ex ) {
			$this->logger->log( $this->logLevel, $ex->getMessage(), [ self::CONTEXT_EXCEPTION_KEY => $ex ] );
			throw $ex;
		}
	}

	/**
	 * @see SubscriptionRepository::findByConfirmationCode
	 *
	 * @param string $confirmationCode
	 *
	 * @return Subscription|null
	 * @throws SubscriptionRepositoryException
	 */
	public function findByConfirmationCode( string $confirmationCode ): ?Subscription {
		try {
			return $this->repository->findByConfirmationCode( $confirmationCode );
		} catch ( SubscriptionRepositoryException $ex ) {
			$this->logger->log( $this->logLevel, $ex->getMessage(), [ self::CONTEXT_EXCEPTION_KEY => $ex ] );
			throw $ex;
		}
	}

}
