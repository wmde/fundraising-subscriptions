<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests\Integration\DataAccess;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Clock\SystemClock;
use WMDE\Fundraising\SubscriptionContext\DataAccess\DatabaseSubscriptionAnonymizationMonitor;
use WMDE\Fundraising\SubscriptionContext\Tests\TestEnvironment;

#[CoversClass( DatabaseSubscriptionAnonymizationMonitor::class )]
class DatabaseSubscriptionAnonymizationMonitorTest extends TestCase {

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

	public function testCountOldUnscrubbedSubscriptions_ExcludesRecentEntries(): void {
		// create older subscription
		// create recent subscription within grace period
		$this->assertSame( 1, $this->monitor->countUnscrubbedSubscriptions() );
	}

	public function testCountOldUnscrubbedSubscriptions_OnlyIncludesEntriesStillContainingPersonalData(): void {
		// create older subscription with personal data
		// create recent subscription within grace period
		$this->assertSame( 1, $this->monitor->countUnscrubbedSubscriptions() );
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
