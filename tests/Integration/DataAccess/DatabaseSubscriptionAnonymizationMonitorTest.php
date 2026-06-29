<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests\Integration\DataAccess;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Clock\SystemClock;
use WMDE\Fundraising\SubscriptionContext\DataAccess\DatabaseSubscriptionAnonymizationMonitor;
use WMDE\Fundraising\SubscriptionContext\DataAccess\SubscriptionAnonymizationMonitor;
use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;
use WMDE\Fundraising\SubscriptionContext\Tests\TestEnvironment;

#[CoversClass( DatabaseSubscriptionAnonymizationMonitor::class )]
class DatabaseSubscriptionAnonymizationMonitorTest extends TestCase {

	private EntityManager $entityManager;
	private SystemClock $clock;
	private Connection $conn;
	private SubscriptionAnonymizationMonitor $monitor;

	public function setUp(): void {
		$this->clock = new SystemClock();
		$this->entityManager = TestEnvironment::newInstance()->getEntityManager();
		$this->conn = $this->entityManager->getConnection();
		$this->monitor = new DatabaseSubscriptionAnonymizationMonitor( $this->conn, $this->clock );
	}

	public function testCountOldUnscrubbedSubscriptions_ReturnsMinusOneOnError(): void {
		$throwingMonitor = new DatabaseSubscriptionAnonymizationMonitor( $this->givenThrowingDatabaseConnection(), $this->clock );

		$this->assertEquals( -1, $throwingMonitor->countUnscrubbedSubscriptions() );
	}

	public function testCountOldUnscrubbedSubscriptions_ExcludesRecentEntriesWithinGracePeriod(): void {
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
		$this->assertSame( 1, $this->monitor->countUnscrubbedSubscriptions() );
	}

	public function testCountOldUnscrubbedSubscriptions_IncludesRecentEntriesAlreadyMarkedAsExported(): void {
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
		$this->assertSame( 2, $this->monitor->countUnscrubbedSubscriptions() );
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
