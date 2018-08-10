<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180810081248 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE subject CHANGE schoolyear_id schoolyear_id INT DEFAULT NULL, CHANGE name name VARCHAR(70) NOT NULL, CHANGE changed_at changed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE mark CHANGE changed_at changed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE school_year CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE mark_range mark_range VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mark CHANGE changed_at changed_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE school_year CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE subject CHANGE schoolyear_id schoolyear_id INT DEFAULT NULL, CHANGE name name VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, CHANGE changed_at changed_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user CHANGE mark_range mark_range VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
    }
}
