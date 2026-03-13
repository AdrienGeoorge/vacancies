<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add receiveReminderEmails and receiveSummaryEmails fields to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD receive_reminder_emails TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE `user` ADD receive_summary_emails TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP COLUMN receive_reminder_emails');
        $this->addSql('ALTER TABLE `user` DROP COLUMN receive_summary_emails');
    }
}
