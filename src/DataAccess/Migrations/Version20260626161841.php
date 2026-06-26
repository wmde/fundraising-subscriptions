<?php

declare(strict_types=1);

namespace WMDE\Fundraising\SubscriptionContext\DataAccess\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260626161841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove `full_name` field from subscription table';
    }

    public function up(Schema $schema): void
    {
        $schema->getTable( 'subscription' )->dropColumn( 'full_name' );

    }

    public function down(Schema $schema): void
    {
		$schema->getTable( 'subscription' )->addColumn(
			name: 'full_name',
			typeName: 'string',
			options: [ 'notnull' => true ]
		);

    }
}
