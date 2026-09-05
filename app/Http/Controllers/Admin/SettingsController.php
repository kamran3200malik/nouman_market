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
            'site_name' => 'Glamora Beauty & Salon Marketplace',
            'site_tagline' => 'Luxury Salon, Spa & Bridal Appointments Across Pakistan',
            'site_description' => 'Pakistan\'s premier beauty marketplace connecting verified salons, makeup artists, and clients for seamless appointment bookings.',
            'contact_email' => 'support@glamora.pk',
            'contact_phone' => '+92 (300) 123-4567',
            'support_whatsapp' => '+923001234567',
            'office_address' => 'Suite 402, Luxury Commercial Hub, Gulberg III, Lahore, Pakistan',
            'currency_code' => 'PKR',
            'currency_symbol' => 'PKR',
            'timezone' => 'Asia/Karachi',

            // Monetization & Fees
            'commission_rate' => '10',
            'subscription_monthly_fee' => '3000',
            'subscription_grace_days' => '3',
            'min_payout_threshold' => '5000',
            'tax_percentage' => '0',

            // Shipping & Logistics (Cosmetics & Merchandise Marketplace)
            'shipping_fee_standard' => '250',
            'shipping_free_threshold' => '3000',
            'shipping_free_enabled' => '1',
            'shipping_carrier_name' => 'Standard Express Beauty Courier',
            'shipping_estimated_days' => '2 - 4 Business Days',

            // Booking Policies
            'max_advance_booking_days' => '30',
            'min_booking_notice_hours' => '2',
            'cancellation_cutoff_hours' => '6',
            'auto_confirm_bookings' => '1',
            'allow_customer_reviews' => '1',

            // Notifications
            'email_booking_notifications' => '1',
            'sms_whatsapp_notifications' => '1',
            'admin_new_salon_alerts' => '1',

            // Social Channels
            'social_instagram' => 'https://instagram.com/glamora.pk',
            'social_facebook' => 'https://facebook.com/glamora.pk',
            'social_tiktok' => 'https://tiktok.com/@glamora.pk',
            // SEO & Search Indexing
            'seo_meta_title' => 'BeautyBook Luxe - Premier Salon Marketplace & Beauty CRM',
            'seo_meta_description' => 'Discover and book verified luxury salons, certified makeup artists, bridal packages, and professional beauty essentials across Pakistan.',
            'seo_meta_keywords' => 'salon booking pakistan, bridal makeup lahore, beauty parlor karachi, makeup artists islamabad, beauty products online, salon appointments',
            'seo_google_analytics' => '',
            'seo_google_verification' => '',
            'seo_index_enabled' => '1',

            // System
            'maintenance_mode' => '0',
            'maintenance_message' => 'We are undergoing scheduled luxury beauty platform upgrades. We will be back online shortly.',
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
