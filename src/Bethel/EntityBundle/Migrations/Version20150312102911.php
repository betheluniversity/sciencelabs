<?php

namespace Bethel\EntityBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20150312102911 extends AbstractMigration {
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) {
        $this->addSql("UPDATE Role SET sort = 6 WHERE role = 'ROLE_ADMIN'");
        $this->addSql("INSERT INTO Role (name, role, sort) VALUES ('Viewer', 'ROLE_VIEWER', 5)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) {
        $this->addSql("DELETE FROM Role WHERE role = 'ROLE_VIEWER'");
        $this->addSql("UPDATE Role SET sort = 5 WHERE role = 'ROLE_ADMIN'");
    }
}