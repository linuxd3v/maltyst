<?php

namespace Maltyst;

use wpdb;
use Exception;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Database
{
    private string $tableOptins;
    private string $tableConfTokens;

    public function __construct()
    {
        global $wpdb;
        $this->tableOptins = $wpdb->prefix . 'maltyst_optins';
        $this->tableConfTokens = $wpdb->prefix . 'maltyst_email_confirmation_tokens';
    }

    /**
     * Remove token records by opt-in ID.
     */
    public function removeTokenRecordsByOptinId(int $optinId): bool
    {
        return $this->deleteRecord($this->tableConfTokens, ['fk_optins_id' => $optinId]);
    }

    /**
     * Remove a token record by ID.
     */
    public function removeTokenRecordById(int $tokenId): bool
    {
        return $this->deleteRecord($this->tableConfTokens, ['id' => $tokenId]);
    }

    /**
     * Remove an email record by ID.
     */
    public function removeEmailRecordById(int $optinId): bool
    {
        return $this->deleteRecord($this->tableOptins, ['id' => $optinId]);
    }

    /**
     * Create an email confirmation token.
     */
    public function createEmailConfirmationToken(int $emailId, string $token, string $algo): ?int
    {
        return $this->insertRecord($this->tableConfTokens, [
            'fk_optins_id' => $emailId,
            'hashed_token' => $token,
            'hash_algo' => $algo,
        ]);
    }

    /**
     * Create an email opt-in record.
     */
    public function createEmailOptin(string $email): ?int
    {
        return $this->insertRecord($this->tableOptins, ['email' => $email]);
    }

    /**
     * Get email opt-in record by ID.
     */
    public function getEmailOptinRecordById(int $emailId): ?array
    {
        return $this->fetchRecord($this->tableOptins, 'id', $emailId);
    }

    /**
     * Get a confirmation token record.
     */
    public function getConfirmationTokenRecord(string $token): ?array
    {
        return $this->fetchRecord($this->tableConfTokens, 'hashed_token', $token);
    }

    /**
     * Get email opt-in record by email.
     */
    public function getEmailOptinRecordByEmail(string $email): ?array
    {
        return $this->fetchRecord($this->tableOptins, 'email', $email);
    }

    /**
     * Check if an email opt-in record exists.
     */
    public function doesEmailOptinExist(string $email): bool
    {
        return $this->getEmailOptinRecordByEmail($email) !== null;
    }

    /**
     * Get all email opt-in records.
     */
    public function getAllEmailOptinRecords(): array
    {
        return $this->fetchAllRecords($this->tableOptins);
    }

    /**
     * Get all email confirmation tokens.
     */
    public function getAllEmailConfTokens(): array
    {
        return $this->fetchAllRecords($this->tableConfTokens);
    }

    /**
     * Clean up expired records.
     */
    public function cleanupExpired(): bool
    {
        global $wpdb;

        // Remove opt-in records older than a month
        $wpdb->query("DELETE FROM {$this->tableOptins} WHERE created < DATE_SUB(NOW(), INTERVAL 1 MONTH)");

        // Clean orphaned tokens
        $existingOptinIds = array_column($this->getAllEmailOptinRecords(), 'id');
        $tokenOptinIds = array_column($this->getAllEmailConfTokens(), 'fk_optins_id');
        $orphanedOptinIds = array_diff($tokenOptinIds, $existingOptinIds);

        foreach ($orphanedOptinIds as $optinId) {
            $this->removeTokenRecordsByOptinId((int)$optinId);
        }

        return true;
    }

    /**
     * Initialize the database schema.
     */
    public function initSchema(): void
    {
        global $wpdb;
        require_once constant('ABSPATH') . 'wp-admin/includes/upgrade.php';

        $charsetCollate = $wpdb->get_charset_collate();

        $schemas = [
            "CREATE TABLE IF NOT EXISTS {$this->tableOptins} (
                id BIGINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
                email VARCHAR(255) NOT NULL,
                created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY email (email)
            ) $charsetCollate;",
            "CREATE TABLE IF NOT EXISTS {$this->tableConfTokens} (
                id BIGINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
                fk_optins_id BIGINT UNSIGNED NOT NULL,
                hashed_token VARCHAR(255) NOT NULL,
                hash_algo VARCHAR(20) NOT NULL,
                created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY hashed_token (hashed_token)
            ) $charsetCollate;"
        ];

        foreach ($schemas as $sql) {
            dbDelta($sql);
        }
    }

    /**
     * Helper: Delete a record.
     */
    private function deleteRecord(string $table, array $where): bool
    {
        global $wpdb;

        try {
            $wpdb->delete($table, $where, array_fill(0, count($where), '%d'));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Helper: Insert a record.
     */
    private function insertRecord(string $table, array $data): ?int
    {
        global $wpdb;

        try {
            $wpdb->insert($table, $data, array_map(fn($value) => is_int($value) ? '%d' : '%s', $data));
            return $wpdb->insert_id ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Helper: Fetch a single record by column.
     */
    private function fetchRecord(string $table, string $column, $value): ?array
    {
        global $wpdb;

        $query = $wpdb->prepare("SELECT * FROM $table WHERE $column = %s", $value);
        $result = $wpdb->get_row($query, 'ARRAY_A');

        return $result ?: null;
    }

    /**
     * Helper: Fetch all records from a table.
     */
    private function fetchAllRecords(string $table): array
    {
        global $wpdb;

        $query = "SELECT * FROM $table";
        return $wpdb->get_results($query, 'ARRAY_A');
    }
}
