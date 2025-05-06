<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->logActivity('created');
        });

        static::updated(function ($model) {
            $model->logActivity('updated');
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted');
        });
    }

    public function logActivity($action)
    {
        if (!auth()->check()) {
            return;
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => "User {$this->getActivityIdentifier()} was {$action}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getActivityIdentifier()
    {
        return $this->id;
    }

    protected function getActivityDescription($action)
    {
        $modelName = class_basename($this);
        $identifier = $this->getActivityIdentifier();

        return ucfirst($action) . " {$modelName} {$identifier}";
    }

    protected function getOldData()
    {
        return $this->getOriginal();
    }

    protected function getNewData()
    {
        return $this->getAttributes();
    }
} 