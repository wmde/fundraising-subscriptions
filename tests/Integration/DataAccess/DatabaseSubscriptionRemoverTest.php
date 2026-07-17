<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests\Integration\DataAccess;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Clock\SystemClock;
use WMDE\Fundraising\SubscriptionContext\DataAccess\DatabaseSubscriptionRemover;
use WMDE\Fundraising\SubscriptionContext\DataAccess\DoctrineSubscriptionRepository;
use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;
use WMDE\Fundraising\SubscriptionContext\Domain\Repositories\SubscriptionRepository;
use WMDE\Fundraising\SubscriptionContext\Tests\TestEnvironment;

#[CoversClass( DatabaseSubscriptionRemover::class )]
class DatabaseSubscriptionRemoverTest extends TestCase {

	private SubscriptionRepository $subscriptionRepo;
	private EntityManager $entityManager;

	private SystemClock $clock;
	private \DateInterval $exportGracePeriod;

	private DatabaseSubscriptionRemover $subscriptionRemover;

	public function setUp(): void {
		$this->entityManager = TestEnvironment::newInstance()->getEntityManager();
		$this->subscriptionRepo = new DoctrineSubscriptionRepository( $this->entityManager );

		$this->clock = new SystemClock();
		$this->exportGracePeriod = new \DateInterval( 'P2D' );

		$this->subscriptionRemover = new DatabaseSubscriptionRemover(
			entityManager: $this->entityManager,
			clock: $this->clock,
			exportGracePeriod: $this->exportGracePeriod
		);
	}

	public function testRemoveAllReturns0ForEmptyTable(): void {
		// no entries in the database

		$this->assertSame( 0, $this->subscriptionRemover->removeAll() );
	}

	public function testRemoveAllDeletesExportedSubscriptions(): void {
		// id 1
		$unexportedSub = $this->insertUnExportedRecentSubscription();
		// id 2
		$exportedSub = $this->insertExportedRecentSubscription();

		$affectedRows = $this->subscriptionRemover->removeAll();

		$this->assertSame( 1, $affectedRows );
		$this->assertNull( $this->subscriptionRepo->getSubscriptionById( $exportedSub->getId() ) );
		$this->assertNotNull( $this->subscriptionRepo->getSubscriptionById( $unexportedSub->getId() ) );
	}

	public function testRemoveAllDeletesSubscriptionsOlderThanGracePeriod(): void {
		$this->insertSubscriptionOlderThanGracePeriod();

		$this->assertSame( 1, $this->subscriptionRemover->removeAll() );
	}

	public function testRemoveAllDoesNotDeleteUnexportedSubscriptionsWithinGracePeriod(): void {
		$this->insertUnExportedRecentSubscription();

		$this->assertSame( 0, $this->subscriptionRemover->removeAll() );
	}

	public function testRemoveByIdsReturns0ForEmptyTable(): void {
		// no entries in the database

		$this->assertSame( 0, $this->subscriptionRemover->removeByIds( 0, 1, 2, 44 ) );
	}

	public function testRemoveByIdsDeletesExportedSubscriptions(): void {
		// id 1 (shouldn't get deleted)
		$unexportedSub = $this->insertUnExportedRecentSubscription();
		// id 2
		$exportedSub1 = $this->insertExportedRecentSubscription();
		// id 3
		$exportedSub2 = $this->insertExportedRecentSubscription();

		$affectedRows = $this->subscriptionRemover->removeByIds( 1, 2 );

		$this->assertSame( 1, $affectedRows );
		$this->assertNotNull( $this->subscriptionRepo->getSubscriptionById( $unexportedSub->getId() ) );
		$this->assertNull( $this->subscriptionRepo->getSubscriptionById( $exportedSub1->getId() ) );
		$this->assertNotNull( $this->subscriptionRepo->getSubscriptionById( $exportedSub2->getId() ) );
	}

	public function testRemoveByIdsDeletesSubscriptionsOlderThanGracePeriod(): void {
		$subscription = $this->insertSubscriptionOlderThanGracePeriod();

		$affectedRows = $this->subscriptionRemover->removeByIds( $subscription->getId() );

		$this->assertSame( 1, $affectedRows );
		$this->assertNull( $this->subscriptionRepo->getSubscriptionById( $subscription->getId() ) );
	}

	private function insertSubscriptionOlderThanGracePeriod(): Subscription {
		$subscription = new Subscription();
		$subscription->setCreatedAt(
			\DateTime::createFromImmutable(
				$this->clock->now()->modify( '-2 months' )
			)
		);

		$subscription->setExport( null );

		$this->entityManager->persist( $subscription );
		$this->entityManager->flush();
		return $subscription;
	}

	private function insertUnExportedRecentSubscription(): Subscription {
		$subscription = new Subscription();
		$recentDate = \DateTime::createFromImmutable(
			$this->clock->now()->modify( '-1 day' )
		);
		$subscription->setCreatedAt( $recentDate );

		$subscription->setExport( null );

		$this->entityManager->persist( $subscription );
		$this->entityManager->flush();
		return $subscription;
	}

	private function insertExportedRecentSubscription(): Subscription {
		$subscription = new Subscription();
		$recentDate = \DateTime::createFromImmutable(
			$this->clock->now()->modify( '-1 day' )
		);
		$subscription->setCreatedAt( $recentDate );

		$subscription->setExport(
			\DateTime::createFromImmutable(
				$this->clock->now()->modify( '-1 day' )
			)
		);

		$this->entityManager->persist( $subscription );
		$this->entityManager->flush();
		return $subscription;
	}

}
