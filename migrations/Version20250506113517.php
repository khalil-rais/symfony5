<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250506113517 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE question_tag DROP FOREIGN KEY FK_339D56FB1E27F6BF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_tag DROP FOREIGN KEY FK_339D56FBBAD26311
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_tag ADD id INT AUTO_INCREMENT NOT NULL, ADD tagged_at DATETIME DEFAULT NOW() COMMENT '(DC2Type:datetime_immutable)', DROP PRIMARY KEY, ADD PRIMARY KEY (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_tag ADD CONSTRAINT FK_339D56FB1E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_tag ADD CONSTRAINT FK_339D56FBBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE question_tag MODIFY id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_tag DROP FOREIGN KEY FK_339D56FB1E27F6BF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_tag DROP FOREIGN KEY FK_339D56FBBAD26311
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX `PRIMARY` ON question_tag
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_tag DROP id, DROP tagged_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_tag ADD CONSTRAINT FK_339D56FB1E27F6BF FOREIGN KEY (question_id) REFERENCES question (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_tag ADD CONSTRAINT FK_339D56FBBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_tag ADD PRIMARY KEY (question_id, tag_id)
        SQL);
    }
}
