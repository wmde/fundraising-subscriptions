<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\SubscriptionContext;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Gedmo\Timestampable\TimestampableListener;

class SubscriptionContextFactory {

	private const DOCTRINE_CLASS_MAPPING_DIRECTORY = __DIR__ . '/../config/DoctrineClassMapping';

	/**
	 * @return string[]
	 */
	public function getDoctrineMappingPaths(): array {
		return [ self::DOCTRINE_CLASS_MAPPING_DIRECTORY ];
	}

	/**
	 * @return array<class-string,EventSubscriber>
	 */
	public function newEventSubscribers(): array {
		$timestampableListener = new TimestampableListener();
		$timestampableListener->setAnnotationReader( new AnnotationReader() );
		return [
			TimestampableListener::class => $timestampableListener
		];
	}

}
