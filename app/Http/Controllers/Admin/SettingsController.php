<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $allSettings = Setting::all()->pluck('value', 'key')->toArray();

        $defaults = [
            // General & Branding
            'site_name' => 'Luxe Beauty Market',
            'site_tagline' => '100% Genuine Cosmetics & Skincare Marketplace',
            'site_description' => 'Pakistan\'s premier multi-brand cosmetics and skincare marketplace delivering authentic, batch-verified beauty products nationwide.',
            'contact_email' => 'support@luxemarket.pk',
            'contact_phone' => '+92 (300) 123-4567',
            'support_whatsapp' => '+923001234567',
            'office_address' => 'Floor 3, Luxury Plaza, MM Alam Road, Gulberg III, Lahore, Pakistan',
            'currency_code' => 'PKR',
            'currency_symbol' => 'PKR',
            'timezone' => 'Asia/Karachi',

            // Shipping & Logistics
            'shipping_fee_standard' => '250',
            'shipping_free_threshold' => '5000',
            'shipping_free_enabled' => '1',
            'shipping_carrier_name' => 'TCS / Leopard Express',
            'shipping_estimated_days' => '2 - 4 Business Days',
            'cash_on_delivery_enabled' => '1',
            'online_payment_enabled' => '1',
            'tax_percentage' => '0',
            'allow_customer_reviews' => '1',

            // Notifications
            'email_order_notifications' => '1',
            'sms_whatsapp_notifications' => '1',
            'admin_new_order_alerts' => '1',

            // Social Channels
            'social_instagram' => 'https://instagram.com/luxemarket.pk',
            'social_facebook' => 'https://facebook.com/luxemarket.pk',
            'social_tiktok' => 'https://tiktok.com/@luxemarket.pk',
            'social_youtube' => '',

            // SEO & Search Indexing
            'seo_meta_title' => 'Luxe Beauty Market - Authentic Cosmetics, Skincare & Perfumes',
            'seo_meta_description' => 'Shop 100% original skincare, cosmetics, makeup, haircare and luxury fragrances with fast delivery across Pakistan.',
            'seo_meta_keywords' => 'cosmetics pakistan, skincare lahore, makeup online karachi, original perfumes, beauty shop pakistan',
            'seo_google_analytics' => '',
            'seo_google_verification' => '',
            'seo_index_enabled' => '1',

            // System
            'maintenance_mode' => '0',
            'maintenance_message' => 'We are undergoing scheduled platform upgrades. We will be back online shortly.',
        ];

        $settings = array_merge($defaults, $allSettings);

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($request->settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string)($value ?? '')]
            );
        }

        return back()->with('success', 'Platform settings saved successfully.');
    }

    public function backups()
    {
        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $files = File::files($backupDir);
        $backups = [];
        $totalBytes = 0;

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $size = $file->getSize();
            $totalBytes += $size;
            $mtime = $file->getMTime();

            $tablesCount = 45;
            if (str_ends_with($filename, '.json')) {
                try {
                    $content = json_decode(File::get($file->getRealPath()), true);
                    if (is_array($content)) {
                        $tablesCount = count($content);
                    }
                } catch (\Throwable $e) {
                    $tablesCount = 45;
                }
            }

            $backups[] = [
                'filename' => $filename,
                'path' => $file->getRealPath(),
                'size_bytes' => $size,
                'size_formatted' => $this->formatBytes($size),
                'created_at' => date('Y-m-d H:i:s', $mtime),
                'time_ago' => \Carbon\Carbon::createFromTimestamp($mtime)->diffForHumans(),
                'tables_count' => $tablesCount,
                'extension' => strtoupper($file->getExtension()),
            ];
        }

        // Sort latest first
        usort($backups, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        $dbName = DB::connection()->getDatabaseName();
        $tables = DB::select('SHOW TABLES');
        $dbTablesCount = count($tables);

        $stats = [
            'total_backups' => count($backups),
            'total_size_formatted' => $this->formatBytes($totalBytes),
            'last_backup' => count($backups) > 0 ? $backups[0]['created_at'] : null,
            'database_tables_count' => $dbTablesCount,
            'database_name' => $dbName,
        ];

        return Inertia::render('Admin/Settings/Backups', [
            'backups' => $backups,
            'stats' => $stats,
        ]);
    }

    public function createBackup()
    {
        Artisan::call('app:backup-database');

        return back()->with('success', 'Database snapshot generated and saved successfully.');
    }

    public function downloadBackup($filename)
    {
        $safeName = basename($filename);
        $filePath = storage_path("app/backups/{$safeName}");

        if (!File::exists($filePath)) {
            abort(404, 'Backup file not found.');
        }

        $downloadName = str_starts_with($safeName, 'beauty_book_') 
            ? $safeName 
            : 'beauty_book_' . $safeName;

        return response()->download($filePath, $downloadName);
    }

    public function restoreBackup($filename)
    {
        $safeName = basename($filename);
        $filePath = storage_path("app/backups/{$safeName}");

        if (!File::exists($filePath)) {
            return back()->with('error', 'Backup file not found.');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            if (str_ends_with($safeName, '.sql')) {
                $sql = File::get($filePath);
                DB::unprepared($sql);
            } else {
                $json = File::get($filePath);
                $data = json_decode($json, true);

                if (!is_array($data)) {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                    return back()->with('error', 'Invalid backup format.');
                }

                foreach ($data as $tableName => $rows) {
                    if (Schema::hasTable($tableName)) {
                        DB::table($tableName)->truncate();
                        if (!empty($rows)) {
                            $chunks = array_chunk($rows, 100);
                            foreach ($chunks as $chunk) {
                                $insertData = array_map(function ($row) {
                                    return (array) $row;
                                }, $chunk);
                                DB::table($tableName)->insert($insertData);
                            }
                        }
                    }
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            return back()->with('success', "Database successfully restored from snapshot [{$safeName}].");
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return back()->with('error', 'Restore error: ' . $e->getMessage());
        }
    }

    public function destroyBackup($filename)
    {
        $safeName = basename($filename);
        $filePath = storage_path("app/backups/{$safeName}");

        if (File::exists($filePath)) {
            File::delete($filePath);
            return back()->with('success', "Backup file [{$safeName}] deleted.");
        }

        return back()->with('error', 'Backup file not found.');
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
