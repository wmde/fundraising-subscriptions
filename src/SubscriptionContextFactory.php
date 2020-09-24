<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Gedmo\Timestampable\TimestampableListener;

class SubscriptionContextFactory {

	/**
	 * Use this constant for MappingDriverChain::addDriver
	 */
	public const ENTITY_NAMESPACE = 'WMDE\Fundraising\SubscriptionContext\Domain\Model';

	private const DOCTRINE_CLASS_MAPPING_DIRECTORY = __DIR__ . '/../config/DoctrineClassMapping';

	public function newMappingDriver(): MappingDriver {
		// We're only calling this for the side effect of adding Mapping/Driver/DoctrineAnnotations.php
		// to the AnnotationRegistry. When AnnotationRegistry is deprecated with Doctrine Annotations 2.0,
		// use $this->annotationReader instead
		return new XmlDriver( self::DOCTRINE_CLASS_MAPPING_DIRECTORY );
	}

	public function newEventSubscribers(): array {
		$timestampableListener = new TimestampableListener();
		$timestampableListener->setAnnotationReader( new AnnotationReader() );
		return [
			TimestampableListener::class => $timestampableListener
		];
	}

}
