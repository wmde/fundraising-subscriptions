<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\SubscriptionContext\SubscriptionContextFactory;

class TestSubscriptionContextFactory {

	private Configuration $doctrineConfig;
	private SubscriptionContextFactory $factory;
	private Connection $connection;
	private ?EntityManager $entityManager;

	public function __construct( array $config, Configuration $doctrineConfig ) {
		$this->doctrineConfig = $doctrineConfig;

		$this->connection = DriverManager::getConnection( $config['db'] );
		$this->factory = new SubscriptionContextFactory();
		$this->entityManager = null;
	}

	public function getEntityManager(): EntityManager {
		if ( $this->entityManager === null ) {
			$eventManager = $this->setupEventSubscribers( $this->factory->newEventSubscribers() );

			$this->entityManager = new EntityManager(
				$this->connection,
				$this->doctrineConfig,
				$eventManager
			);
		}

		return $this->entityManager;
	}

	private function setupEventSubscribers( array $eventSubscribers ): EventManager {
		$eventManager = new EventManager();
		foreach ( $eventSubscribers as $eventSubscriber ) {
			$eventManager->addEventSubscriber( $eventSubscriber );
		}
		return $eventManager;
	}
}
