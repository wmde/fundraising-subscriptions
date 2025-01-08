<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use WMDE\Fundraising\SubscriptionContext\Domain\Model\Subscription;

class SchemaCreator {
	private EntityManager $entityManager;
	private SchemaTool $schemaTool;

	public function __construct( EntityManager $entityManager ) {
		$this->entityManager = $entityManager;
		$this->schemaTool = new SchemaTool( $this->entityManager );
	}

	public function createSchema(): void {
		$this->getSchemaTool()->createSchema( $this->getClassMetaData() );
	}

	public function dropSchema(): void {
		$this->getSchemaTool()->dropSchema( $this->getClassMetaData() );
	}

	private function getSchemaTool(): SchemaTool {
		return $this->schemaTool;
	}

	/**
	 * @phpstan-return list<ClassMetadata<Subscription>>
	 * @return ClassMetadata<Subscription>[]
	 */
	private function getClassMetaData(): array {
		return $this->entityManager->getMetadataFactory()->getAllMetadata();
	}

}
