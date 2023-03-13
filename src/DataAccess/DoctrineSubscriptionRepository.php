<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\DataAccess;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;
use WMDE\Fundraising\SubscriptionContext\Domain\Repositories\SubscriptionRepository;
use WMDE\Fundraising\SubscriptionContext\Domain\Repositories\SubscriptionRepositoryException;

/**
 * @license GPL-2.0-or-later
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
class DoctrineSubscriptionRepository implements SubscriptionRepository {

	private EntityManager $entityManager;

	public function __construct( EntityManager $entityManager ) {
		$this->entityManager = $entityManager;
	}

	/**
	 * @param Subscription $subscription
	 * @throws SubscriptionRepositoryException
	 */
	public function storeSubscription( Subscription $subscription ): void {
		try {
			$this->entityManager->persist( $subscription );
			$this->entityManager->flush();
		} catch ( ORMException $e ) {
			throw new SubscriptionRepositoryException( 'Could not store subscription.', $e );
		}
	}

	public function countSimilar( Subscription $subscription, \DateTime $cutoffDateTime ): int {
		$qb = $this->entityManager->createQueryBuilder();
		$query = $qb->select( 'COUNT( s.id )' )
			->from( Subscription::class, 's' )
			->where( $qb->expr()->eq( 's.email', ':email' ) )
			->andWhere( $qb->expr()->gt( 's.createdAt', ':cutoffDate' ) )
			->setParameter( 'email', $subscription->getEmail() )
			->setParameter( 'cutoffDate', $cutoffDateTime, Types::DATETIME_MUTABLE )
			->getQuery();
		try {
			return (int)$query->getSingleScalarResult();
		} catch ( ORMException $e ) {
			throw new SubscriptionRepositoryException( 'Could not count subscriptions, check your query and its parameters.', $e );
		}
	}

	/**
	 */

	/**
	 * @param string $confirmationCode
	 *
	 * @return Subscription|null
	 *
	 * @throws SubscriptionRepositoryException
	 */
	public function findByConfirmationCode( string $confirmationCode ): ?Subscription {
		try {
			return $this->entityManager->getRepository( Subscription::class )->findOneBy( [
				'confirmationCode' => $confirmationCode
			] );
		} catch ( ORMException $e ) {
			throw new SubscriptionRepositoryException( 'Could not find subscriptions by confirmation code.', $e );
		}
	}

}
