<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180809102747 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE school_year (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, changed_at DATETIME NOT NULL, INDEX IDX_FAAAACDAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE school_year ADD CONSTRAINT FK_FAAAACDAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE subject ADD schoolyear_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE subject ADD CONSTRAINT FK_FBCE3E7A444E1AE8 FOREIGN KEY (schoolyear_id) REFERENCES school_year (id)');
        $this->addSql('CREATE INDEX IDX_FBCE3E7A444E1AE8 ON subject (schoolyear_id)');
        $this->addSql('ALTER TABLE mark ADD schoolyear_id INT DEFAULT NULL, CHANGE changed_at changed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE mark ADD CONSTRAINT FK_6674F271444E1AE8 FOREIGN KEY (schoolyear_id) REFERENCES school_year (id)');
        $this->addSql('CREATE INDEX IDX_6674F271444E1AE8 ON mark (schoolyear_id)');
        $this->addSql('ALTER TABLE user CHANGE mark_range mark_range VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE subject DROP FOREIGN KEY FK_FBCE3E7A444E1AE8');
        $this->addSql('ALTER TABLE mark DROP FOREIGN KEY FK_6674F271444E1AE8');
        $this->addSql('DROP TABLE school_year');
        $this->addSql('DROP INDEX IDX_6674F271444E1AE8 ON mark');
        $this->addSql('ALTER TABLE mark DROP schoolyear_id, CHANGE changed_at changed_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('DROP INDEX IDX_FBCE3E7A444E1AE8 ON subject');
        $this->addSql('ALTER TABLE subject DROP schoolyear_id');
        $this->addSql('ALTER TABLE user CHANGE mark_range mark_range VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
    }
}
