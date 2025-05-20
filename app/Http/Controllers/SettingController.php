<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class SettingController extends Controller
{
    /**
     * Display the settings dashboard with system info and logs.
     */
    public function index(): View
    {
        $systemInfo = $this->getSystemInfo();
        $logs = $this->getSystemLogs();
        $databaseInfo = $this->getDatabaseInfo();
        $cacheInfo = $this->getCacheInfo();
        $stats = $this->getStats();
        $activityLogs = $this->getActivityLogs();

        return view('settings.index', compact(
            'systemInfo', 
            'logs', 
            'databaseInfo', 
            'cacheInfo', 
            'stats',
            'activityLogs'
        ));
    }

    /**
     * Clear the application cache.
     */
    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            
            return redirect()->route('settings.index')
                ->with('success', 'Application cache cleared successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to clear cache: ' . $e->getMessage());
            return redirect()->route('settings.index')
                ->with('error', 'Failed to clear cache. Please try again.');
        }
    }

    /**
     * Clear the application logs.
     */
    public function clearLogs()
    {
        try {
            File::put(storage_path('logs/laravel.log'), '');
            return redirect()->route('settings.index')
                ->with('success', 'Application logs cleared successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to clear logs: ' . $e->getMessage());
            return redirect()->route('settings.index')
                ->with('error', 'Failed to clear logs. Please try again.');
        }
    }

    /**
     * Get system information.
     */
    private function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'server_os' => PHP_OS,
            'database_connection' => config('database.default'),
            'timezone' => config('app.timezone'),
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug') ? 'Enabled' : 'Disabled',
            'maintenance_mode' => app()->isDownForMaintenance() ? 'Enabled' : 'Disabled',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . ' seconds',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];
    }

    /**
     * Get system logs.
     */
    private function getSystemLogs(): array
    {
        $logFile = storage_path('logs/laravel.log');
        $logs = [];

        if (File::exists($logFile)) {
            $logs = array_slice(
                array_reverse(File::lines($logFile)->toArray()),
                0,
                1000 // Limit to last 1000 lines
            );
        }

        return $logs;
    }

    /**
     * Get database information.
     */
    private function getDatabaseInfo(): array
    {
        try {
            $tables = DB::select('SHOW TABLE STATUS');
            $totalSize = 0;
            $tableInfo = [];

            foreach ($tables as $table) {
                $size = $table->Data_length + $table->Index_length;
                $totalSize += $size;
                $tableInfo[] = [
                    'name' => $table->Name,
                    'rows' => $table->Rows,
                    'size' => $this->formatBytes($size),
                    'created' => $table->Create_time,
                    'updated' => $table->Update_time,
                ];
            }

            return [
                'total_tables' => count($tables),
                'total_size' => $this->formatBytes($totalSize),
                'tables' => $tableInfo,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get database info: ' . $e->getMessage());
            return [
                'total_tables' => 0,
                'total_size' => '0 B',
                'tables' => [],
                'error' => 'Failed to retrieve database information'
            ];
        }
    }

    /**
     * Get cache information.
     */
    private function getCacheInfo(): array
    {
        try {
            $driver = config('cache.default');
            $prefix = config('cache.prefix');
            $stores = config('cache.stores');
            
            return [
                'driver' => $driver,
                'prefix' => $prefix,
                'stores' => array_keys($stores),
                'status' => Cache::get('system_status') ? 'Connected' : 'Not Connected',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get cache info: ' . $e->getMessage());
            return [
                'driver' => 'Unknown',
                'prefix' => 'Unknown',
                'stores' => [],
                'status' => 'Error',
                'error' => 'Failed to retrieve cache information'
            ];
        }
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get system statistics.
     */
    private function getStats(): array
    {
        try {
            $lastScan = DB::table('scan_logs')
                ->latest()
                ->first();

            return [
                'total_users' => DB::table('users')->count(),
                'active_events' => DB::table('events')
                    ->where('status', 'active')
                    ->where('end_date', '>=', now())
                    ->count(),
                'total_attendees' => DB::table('attendees')->count(),
                'total_scans' => DB::table('scan_logs')->count(),
                'last_scan' => $lastScan ? $lastScan->created_at : null,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get stats: ' . $e->getMessage());
            return [
                'total_users' => 0,
                'active_events' => 0,
                'total_attendees' => 0,
                'total_scans' => 0,
                'last_scan' => null,
            ];
        }
    }

    /**
     * Get activity logs.
     */
    private function getActivityLogs()
    {
        try {
            return DB::table('scan_logs')
                ->select([
                    'scan_logs.created_at',
                    'scan_logs.status as description',
                    'users.name as causer_name'
                ])
                ->leftJoin('users', 'scan_logs.scanner_id', '=', 'users.id')
                ->orderBy('scan_logs.created_at', 'desc')
                ->take(100)
                ->get()
                ->map(function ($log) {
                    return (object) [
                        'causer' => (object) ['name' => $log->causer_name ?? 'System'],
                        'description' => ucfirst($log->status),
                        'created_at' => \Carbon\Carbon::parse($log->created_at),
                        'id' => uniqid()
                    ];
                });
        } catch (\Exception $e) {
            Log::error('Failed to get activity logs: ' . $e->getMessage());
            return collect([]);
        }
    }
} 