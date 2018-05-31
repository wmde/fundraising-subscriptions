<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\Store\Factory as StoreFactory;
use WMDE\Fundraising\Store\Installer;

/**
 * @licence GNU GPL v2+
 * @author Gabriel Birke <gabriel.birke@wikimedia.de>
 */
class TestEnvironment {

	private $config;
	private $container;

	public static function newInstance(): self {
		$environment = new self(
			[
				'db' => [
					'driver' => 'pdo_sqlite',
					'memory' => true,
				],
				'var-path' => '/tmp'
			]
		);
		$environment->install();
		return $environment;
	}

	private function __construct( array $config ) {
		$this->config = $config;
		$this->container = [];
	}

	public function getEntityManager(): EntityManager {
		return $this->getStoreFactory()->getEntityManager();
	}

	private function getStoreFactory(): StoreFactory {
		return $this->getSharedObject( EntityManager::class, function () {
			return new StoreFactory( $this->getDatabaseConnection() );
		} );
	}

	private function getDatabaseConnection(): Connection {
		return $this->getSharedObject( Connection::class, function () {
			return DriverManager::getConnection( $this->config['db'] );
		} );
	}

	private function newInstaller(): Installer {
		return new Installer( $this->getEntityManager() );
	}

	// phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
	private function getSharedObject( string $className, callable $initialization ) {
		// phpcs:enable
		if ( !isset( $this->container[$className] ) ) {
			$this->container[$className] = $initialization();
		}
		return $this->container[$className];
	}

	private function install(): void {
		$installer = $this->newInstaller();

		try {
			$installer->uninstall();
		}
		catch ( \Exception $ex ) {
		}

		$installer->install();
	}

}