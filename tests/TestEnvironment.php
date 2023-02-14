<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use WMDE\Fundraising\SubscriptionContext\SubscriptionContextFactory;

/**
 * @license GPL-2.0-or-later
 * @author Gabriel Birke <gabriel.birke@wikimedia.de>
 */
class TestEnvironment {

	private TestSubscriptionContextFactory $factory;

	private function __construct( array $config, Configuration $doctrineConfig ) {
		$this->factory = new TestSubscriptionContextFactory( $config, $doctrineConfig );
	}

	public static function newInstance(): self {
		$subscriptionContextFactory = new SubscriptionContextFactory();
		$environment = new self(
			[
				'db' => [
					'driver' => 'pdo_sqlite',
					'memory' => true,
				],
				'var-path' => '/tmp'
			],
			ORMSetup::createXMLMetadataConfiguration( $subscriptionContextFactory->getDoctrineMappingPaths() )
		);
		$environment->install();
		return $environment;
	}

	private function install(): void {
		$schemaCreator = new SchemaCreator( $this->getEntityManager() );

		try {
			$schemaCreator->dropSchema();
		}
		catch ( \Exception $ex ) {
		}

		$schemaCreator->createSchema();
	}

	public function getEntityManager(): EntityManager {
		return $this->factory->getEntityManager();
	}
}
