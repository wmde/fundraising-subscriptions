<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;

class TestSubscriptionContextFactory {

	private Configuration $doctrineConfig;
	private Connection $connection;
	private ?EntityManager $entityManager;

	public function __construct( Configuration $doctrineConfig ) {
		$this->doctrineConfig = $doctrineConfig;

		$this->connection = DriverManager::getConnection( [
			'driver' => 'pdo_sqlite',
			'memory' => true,
		] );
		$this->entityManager = null;
	}

	public function getEntityManager(): EntityManager {
		if ( $this->entityManager === null ) {
			$this->entityManager = new EntityManager(
				$this->connection,
				$this->doctrineConfig
			);
		}

		return $this->entityManager;
	}
}
