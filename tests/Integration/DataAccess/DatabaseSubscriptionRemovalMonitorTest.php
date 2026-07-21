<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests\Integration\DataAccess;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Clock\SystemClock;
use WMDE\Fundraising\SubscriptionContext\DataAccess\DatabaseSubscriptionRemovalMonitor;
use WMDE\Fundraising\SubscriptionContext\DataAccess\SubscriptionRemovalMonitor;
use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;
use WMDE\Fundraising\SubscriptionContext\Tests\TestEnvironment;

#[CoversClass( DatabaseSubscriptionRemovalMonitor::class )]
class DatabaseSubscriptionRemovalMonitorTest extends TestCase {

	private EntityManager $entityManager;
	private SystemClock $clock;
	private Connection $conn;
	private \DateInterval $exportGracePeriod;
	private SubscriptionRemovalMonitor $monitor;

	public function setUp(): void {
		$this->clock = new SystemClock();
		$this->entityManager = TestEnvironment::newInstance()->getEntityManager();
		$this->conn = $this->entityManager->getConnection();
		$this->exportGracePeriod = new \DateInterval( 'P1M' );
		$this->monitor = new DatabaseSubscriptionRemovalMonitor( $this->conn, $this->clock, $this->exportGracePeriod );
	}

	public function testCountUnremovedSubscriptions_ReturnsMinusOneOnError(): void {
		$throwingMonitor = new DatabaseSubscriptionRemovalMonitor( $this->givenThrowingDatabaseConnection(), $this->clock, $this->exportGracePeriod );

		$this->assertEquals( -1, $throwingMonitor->countUnremovedSubscriptions() );
	}

	public function testCountUnremovedSubscriptions_ExcludesRecentEntriesWithinGracePeriod(): void {
		// old subscription that should get detected
		$this->insertSubscription(
			createdAt: \DateTime::createFromImmutable( $this->clock->now()->sub( new \DateInterval( 'P5M' ) ) ),
			exportDate: \DateTime::createFromImmutable( $this->clock->now()->sub( new \DateInterval( 'P4M' ) ) ),
		);
		// recent unexported subscription (should NOT get detected)
		$this->insertSubscription(
			createdAt: \DateTime::createFromImmutable( $this->clock->now()->sub( new \DateInterval( 'PT1H' ) ) ),
			exportDate: null
		);
		$this->assertSame( 1, $this->monitor->countUnremovedSubscriptions() );
	}

	public function testCountUnremovedSubscriptions_IncludesRecentEntriesAlreadyMarkedAsExported(): void {
		// old subscription that should get detected
		$this->insertSubscription(
			createdAt: \DateTime::createFromImmutable( $this->clock->now()->sub( new \DateInterval( 'P5M' ) ) ),
			exportDate: \DateTime::createFromImmutable( $this->clock->now()->sub( new \DateInterval( 'P4M' ) ) ),
		);
		// recent exported subscription should also get detected
		$this->insertSubscription(
			createdAt: \DateTime::createFromImmutable( $this->clock->now()->sub( new \DateInterval( 'P1D' ) ) ),
			exportDate: \DateTime::createFromImmutable( $this->clock->now()->sub( new \DateInterval( 'PT12H' ) ) ),
		);
		$this->assertSame( 2, $this->monitor->countUnremovedSubscriptions() );
	}

	private function insertSubscription( \DateTime $createdAt, ?\DateTime $exportDate, string $email = 'personal-data@test.de' ): void {
		$subscription = new Subscription();
		$subscription->setEmail( $email );
		$subscription->setCreatedAt( $createdAt );
		$subscription->setExport( $exportDate );

		$this->entityManager->persist( $subscription );
		$this->entityManager->flush();
	}

	private function givenThrowingDatabaseConnection(): Connection {
		$queryBuilderStub = $this->createStub( QueryBuilder::class );
		$queryBuilderStub->method( 'executeStatement' )
			->willThrowException( new \RuntimeException( 'Database Exception, thrown by test double' ) );

		return $this->createConfiguredStub(
			Connection::class,
			[ 'createQueryBuilder' => $queryBuilderStub ]
		);
	}

}
