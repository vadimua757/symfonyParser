<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200127140306 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD190BE4C5');
        $this->addSql('DROP INDEX IDX_D34A04AD190BE4C5 ON product');
        $this->addSql('ALTER TABLE product ADD updated_at DATETIME NOT NULL, DROP user_client_id');
        $this->addSql('ALTER TABLE user CHANGE confirmation_code confirmation_code VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE product ADD user_client_id INT DEFAULT NULL, DROP updated_at');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD190BE4C5 FOREIGN KEY (user_client_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_D34A04AD190BE4C5 ON product (user_client_id)');
        $this->addSql('ALTER TABLE user CHANGE confirmation_code confirmation_code VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
    }
}
