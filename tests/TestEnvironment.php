<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
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
				'var_path' => '/tmp'
			]
		);

		$environment->install();

		return $environment;
	}

	private function __construct( array $config ) {
		$this->config = $config;

		$container = new ContainerBuilder();
		foreach ( $config as $key => $value ) {
			$container->setParameter( $key, $value );
		}
		$container->register( Connection::class )
			->setFactory( [ DriverManager::class, 'getConnection' ] )
			->addArgument( new Parameter( 'db' ) );
		$container->register( StoreFactory::class, StoreFactory::class )
			->addArgument( new Reference( Connection::class ) )
			->addArgument( new Parameter( 'var_path' ) );
		$container->register( EntityManager::class )
			->setFactory( [ new Reference( StoreFactory::class ), 'getEntityManager' ] )
			->setPublic( true );
		$container->register( Installer::class )
			->setFactory( [ new Reference( StoreFactory::class ), 'newInstaller' ] )
			->setPublic( true );
		$container->compile();
		$this->container = $container;
	}

	private function install(): void {
		/** @var Installer $installer */
		$installer = $this->container->get( Installer::class );

		try {
			$installer->uninstall();
		}
		catch ( \Exception $ex ) {
		}

		$installer->install();
	}

	public function getContainer(): ContainerBuilder {
		return $this->container;
	}

}