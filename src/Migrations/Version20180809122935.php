<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180809122935 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE subject ADD created_at DATETIME NOT NULL, ADD changed_at DATETIME DEFAULT NULL, CHANGE schoolyear_id schoolyear_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE mark CHANGE schoolyear_id schoolyear_id INT DEFAULT NULL, CHANGE changed_at changed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE school_year CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE mark_range mark_range VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mark CHANGE schoolyear_id schoolyear_id INT DEFAULT NULL, CHANGE changed_at changed_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE school_year CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE subject DROP created_at, DROP changed_at, CHANGE schoolyear_id schoolyear_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE mark_range mark_range VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
    }
}
