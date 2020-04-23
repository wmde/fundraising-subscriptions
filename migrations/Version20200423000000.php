<?php

declare( strict_types = 1 );

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200423000000 extends AbstractMigration {

	public function up( Schema $schema ): void {
		$this->addSql( 'ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3F5B7AF75' );
		$this->addSql( 'ALTER TABLE subscription DROP COLUMN address_id' );
	}

	public function postUp( Schema $schema ) {
		$this->addSql( 'DELETE FROM address WHERE id NOT IN (SELECT address_id FROM address_change)' );
		$this->addSql( 'TRUNCATE TABLE subscription' );
	}

	public function down( Schema $schema ): void {
		$this->addSql( 'ALTER TABLE subscription ADD address_id INT DEFAULT NULL' );
	}

}
