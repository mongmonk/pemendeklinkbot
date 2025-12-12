<?php

namespace App\Observers;

use App\Models\Admin;
use App\Models\ActivityLog;

class AdminObserver
{
    /**
     * Handle Admin "created" event.
     */
    public function created(Admin $admin): void
    {
        ActivityLog::log([
            'action' => 'created',
            'model_type' => Admin::class,
            'model_id' => $admin->id,
            'description' => "Membuat admin: {$admin->username}",
            'properties' => [
                'username' => $admin->username,
                'telegram_user_id' => $admin->telegram_user_id,
                'email' => $admin->email,
            ],
        ]);
    }

    /**
     * Handle Admin "updated" event.
     */
    public function updated(Admin $admin): void
    {
        $changes = $admin->getChanges();
        
        if (empty($changes)) {
            return;
        }

        $description = "Memperbarui admin: {$admin->username}";
        
        if (isset($changes['is_active'])) {
            $action = $changes['is_active'] ? 'enabled' : 'disabled';
            $description = $changes['is_active'] 
                ? "Mengaktifkan admin: {$admin->username}" 
                : "Menonaktifkan admin: {$admin->username}";
            
            ActivityLog::log([
                'action' => $action,
                'model_type' => Admin::class,
                'model_id' => $admin->id,
                'description' => $description,
                'properties' => [
                    'username' => $admin->username,
                    'is_active' => $changes['is_active'],
                ],
            ]);
        } else {
            ActivityLog::log([
                'action' => 'updated',
                'model_type' => Admin::class,
                'model_id' => $admin->id,
                'description' => $description,
                'properties' => [
                    'username' => $admin->username,
                    'changes' => $changes,
                ],
            ]);
        }
    }

    /**
     * Handle Admin "deleted" event.
     */
    public function deleted(Admin $admin): void
    {
        ActivityLog::log([
            'action' => 'deleted',
            'model_type' => Admin::class,
            'model_id' => $admin->id,
            'description' => "Menghapus admin: {$admin->username}",
            'properties' => [
                'username' => $admin->username,
                'telegram_user_id' => $admin->telegram_user_id,
                'total_links' => $admin->total_links,
                'total_clicks' => $admin->total_clicks,
            ],
        ]);
    }

    /**
     * Handle Admin "restored" event.
     */
    public function restored(Admin $admin): void
    {
        ActivityLog::log([
            'action' => 'restored',
            'model_type' => Admin::class,
            'model_id' => $admin->id,
            'description' => "Mengembalikan admin: {$admin->username}",
            'properties' => [
                'username' => $admin->username,
            ],
        ]);
    }

    /**
     * Handle Admin "force deleted" event.
     */
    public function forceDeleted(Admin $admin): void
    {
        ActivityLog::log([
            'action' => 'force_deleted',
            'model_type' => Admin::class,
            'model_id' => $admin->id,
            'description' => "Menghapus permanen admin: {$admin->username}",
            'properties' => [
                'username' => $admin->username,
                'telegram_user_id' => $admin->telegram_user_id,
                'total_links' => $admin->total_links,
                'total_clicks' => $admin->total_clicks,
            ],
        ]);
    }
}