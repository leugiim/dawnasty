<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260907171831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create villages table (Village module).';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE villages (buildings CLOB NOT NULL, resources CLOB NOT NULL, population_total INTEGER NOT NULL, population_unemployed INTEGER NOT NULL, last_calculated_at DATETIME NOT NULL, id CHAR(36) NOT NULL, PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE villages');
    }
}
