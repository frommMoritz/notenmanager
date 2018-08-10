<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180809141954 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mark DROP FOREIGN KEY FK_6674F271A76ED395');
        $this->addSql('DROP INDEX IDX_6674F271A76ED395 ON mark');
        $this->addSql('ALTER TABLE mark DROP user_id, CHANGE schoolyear_id schoolyear_id INT DEFAULT NULL, CHANGE changed_at changed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE subject CHANGE schoolyear_id schoolyear_id INT DEFAULT NULL, CHANGE changed_at changed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE school_year CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE mark_range mark_range VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mark ADD user_id INT NOT NULL, CHANGE schoolyear_id schoolyear_id INT DEFAULT NULL, CHANGE changed_at changed_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE mark ADD CONSTRAINT FK_6674F271A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6674F271A76ED395 ON mark (user_id)');
        $this->addSql('ALTER TABLE school_year CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE subject CHANGE schoolyear_id schoolyear_id INT DEFAULT NULL, CHANGE changed_at changed_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user CHANGE mark_range mark_range VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
    }
}
