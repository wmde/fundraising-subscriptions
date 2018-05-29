<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext\Tests;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\XmlDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use WMDE\Fundraising\Store\Installer;

/**
 * @licence GNU GPL v2+
 * @author Gabriel Birke <gabriel.birke@wikimedia.de>
 */
class TestEnvironment {

	private $config;
	private $container;

	public static function newInstance(): self {
		$environment = new self( [] );
		$environment->install();
		return $environment;
	}

	private function __construct( array $config ) {
		$this->config = $config;

		$container = new ContainerBuilder();
		foreach ( $config as $key => $value ) {
			$container->setParameter( $key, $value );
		}
		$loader = new YamlFileLoader( $container, new FileLocator( __DIR__ ) );
		$loader->load( 'services.yml' );

		$container->compile();
		$this->container = $container;
	}

	private function install(): void {
		/** @var Installer $installer */
		$installer = $this->container->get( 'db.installer' );

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