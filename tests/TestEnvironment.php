<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use WMDE\Fundraising\SubscriptionContext\SubscriptionContextFactory;

class TestEnvironment {

	private TestSubscriptionContextFactory $factory;

	private function __construct( Configuration $doctrineConfig ) {
		$this->factory = new TestSubscriptionContextFactory( $doctrineConfig );
	}

	public static function newInstance(): self {
		$subscriptionContextFactory = new SubscriptionContextFactory();
		$config = ORMSetup::createXMLMetadataConfiguration( $subscriptionContextFactory->getDoctrineMappingPaths() );
		$config->enableNativeLazyObjects( true );
		$environment = new self( $config );
		$environment->install();
		return $environment;
	}

	private function install(): void {
		$schemaCreator = new SchemaCreator( $this->getEntityManager() );

		try {
			$schemaCreator->dropSchema();
		} catch ( \Exception ) {
		}

		$schemaCreator->createSchema();
	}

	public function getEntityManager(): EntityManager {
		return $this->factory->getEntityManager();
	}
}
