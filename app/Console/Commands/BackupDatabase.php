<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database {--format=sql : Output format: sql or json}';
    protected $description = 'Creates a full SQL or JSON data & schema backup of all marketplace tables';

    public function handle()
    {
        $this->info('Starting full database backup...');

        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $dbName = DB::connection()->getDatabaseName();
        $tablesResult = DB::select('SHOW TABLES');
        $property = "Tables_in_{$dbName}";

        $tables = [];
        foreach ($tablesResult as $tableObj) {
            $tableName = $tableObj->$property ?? array_values((array)$tableObj)[0];
            $tables[] = $tableName;
        }

        $count = count($tables);
        $this->info("Discovered {$count} tables in database [{$dbName}].");

        // 1. Generate Complete Standard SQL Dump
        $sqlDump = "-- =========================================================\n";
        $sqlDump .= "-- BEAUTY SALON MARKETPLACE DATABASE BACKUP\n";
        $sqlDump .= "-- Database: `{$dbName}`\n";
        $sqlDump .= "-- Generated: " . now()->toDateTimeString() . "\n";
        $sqlDump .= "-- Tables: {$count}\n";
        $sqlDump .= "-- =========================================================\n\n";
        $sqlDump .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $sqlDump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sqlDump .= "SET time_zone = \"+00:00\";\n\n";

        $pdo = DB::connection()->getPdo();
        $jsonData = [];

        foreach ($tables as $table) {
            $this->line("  Backing up table: {$table}...");

            // Fetch table creation schema
            $createTableResult = DB::select("SHOW CREATE TABLE `{$table}`");
            $createTableSql = $createTableResult[0]->{'Create Table'} ?? null;

            if ($createTableSql) {
                $sqlDump .= "-- ---------------------------------------------------------\n";
                $sqlDump .= "-- Table structure for `{$table}`\n";
                $sqlDump .= "-- ---------------------------------------------------------\n";
                $sqlDump .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sqlDump .= "{$createTableSql};\n\n";
            }

            // Fetch table rows
            $rows = DB::table($table)->get()->toArray();
            $jsonData[$table] = $rows;

            if (!empty($rows)) {
                $sqlDump .= "-- Data for `{$table}` (" . count($rows) . " records)\n";
                $columns = array_keys((array)$rows[0]);
                $escapedColumns = array_map(fn($col) => "`{$col}`", $columns);
                $columnsSql = implode(', ', $escapedColumns);

                $chunks = array_chunk($rows, 100);
                foreach ($chunks as $chunk) {
                    $valuesList = [];
                    foreach ($chunk as $row) {
                        $values = [];
                        foreach ($columns as $col) {
                            $val = $row->$col;
                            if (is_null($val)) {
                                $values[] = 'NULL';
                            } elseif (is_numeric($val) && !is_string($val)) {
                                $values[] = $val;
                            } else {
                                $values[] = $pdo->quote((string)$val);
                            }
                        }
                        $valuesList[] = '(' . implode(', ', $values) . ')';
                    }
                    $sqlDump .= "INSERT INTO `{$table}` ({$columnsSql}) VALUES\n" . implode(",\n", $valuesList) . ";\n";
                }
                $sqlDump .= "\n";
            }
        }

        $sqlDump .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $sqlDump .= "-- Backup Complete --\n";

        // Save SQL Dump File
        $sqlFile = "{$backupDir}/beauty_book_backup_{$timestamp}.sql";
        File::put($sqlFile, $sqlDump);

        // Also save JSON snapshot
        $jsonFile = "{$backupDir}/beauty_book_backup_{$timestamp}.json";
        File::put($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("✓ SQL Backup saved at: {$sqlFile}");
        $this->info("✓ JSON Backup saved at: {$jsonFile}");
        $this->info("Total tables backed up: {$count}");

        return Command::SUCCESS;
    }
}
