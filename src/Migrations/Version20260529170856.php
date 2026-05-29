<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260529170856 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE migration_versions');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE image_post');
        $this->addSql('ALTER TABLE article_reference DROP position');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL, CHANGE subscribe_to_newsletter subscribe_to_newsletter TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE migration_versions (version VARCHAR(14) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, executed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(version)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, headers LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, queue_name VARCHAR(190) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E016BA31DB (delivered_at), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E0FB7336F0 (queue_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE image_post (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, original_filename VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ponka_added_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL, CHANGE subscribe_to_newsletter subscribe_to_newsletter TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE article_reference ADD position INT NOT NULL');
    }
}
/*
    And... done! Next run:
    php bin/console make:migration
    Let's go make sure the migration file doesn't contain any surprises... yep!
    “CREATE TABLE article_reference” with a foreign key back to article.
    Run that with:
    php bin/console doctrine:migrations:migrate
     WARNING! You are about to execute a migration in database "main" that could result in schema changes and data loss. Are you sure you wish to continue? (yes/no) [yes]:
     >
     [WARNING] You have 3 previously executed migrations in the database that are not registered migrations.
     >> 2026-03-16 14:26:55 (DoctrineMigrations\Version20190214204726)
     >> 2026-03-16 14:26:55 (DoctrineMigrations\Version20190219195937)
     >> 2026-03-16 14:26:55 (DoctrineMigrations\Version20190926151031)

     Are you sure you wish to continue? (yes/no) [yes]:
     >
     [OK] Already at the latest version ("DoctrineMigrations\Version20260529170856")
 */