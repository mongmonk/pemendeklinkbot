<?php

namespace App\Observers;

use App\Models\Link;
use App\Models\ActivityLog;

class LinkObserver
{
    /**
     * Handle the Link "created" event.
     */
    public function created(Link $link): void
    {
        ActivityLog::log([
            'action' => 'created',
            'model_type' => Link::class,
            'model_id' => $link->id,
            'description' => "Membuat link pendek: {$link->short_code}",
            'properties' => [
                'short_code' => $link->short_code,
                'long_url' => $link->long_url,
                'is_custom' => $link->is_custom,
            ],
        ]);
    }

    /**
     * Handle the Link "updated" event.
     */
    public function updated(Link $link): void
    {
        $changes = $link->getChanges();
        
        if (empty($changes)) {
            return;
        }

        $description = "Memperbarui link: {$link->short_code}";
        
        if (isset($changes['disabled'])) {
            $action = $changes['disabled'] ? 'disabled' : 'enabled';
            $description = $changes['disabled'] 
                ? "Menonaktifkan link: {$link->short_code}" 
                : "Mengaktifkan link: {$link->short_code}";
            
            ActivityLog::log([
                'action' => $action,
                'model_type' => Link::class,
                'model_id' => $link->id,
                'description' => $description,
                'properties' => [
                    'short_code' => $link->short_code,
                    'disabled' => $changes['disabled'],
                    'reason' => $link->disable_reason ?? null,
                ],
            ]);
        } else {
            ActivityLog::log([
                'action' => 'updated',
                'model_type' => Link::class,
                'model_id' => $link->id,
                'description' => $description,
                'properties' => [
                    'short_code' => $link->short_code,
                    'changes' => $changes,
                ],
            ]);
        }
    }

    /**
     * Handle the Link "deleted" event.
     */
    public function deleted(Link $link): void
    {
        ActivityLog::log([
            'action' => 'deleted',
            'model_type' => Link::class,
            'model_id' => $link->id,
            'description' => "Menghapus link: {$link->short_code}",
            'properties' => [
                'short_code' => $link->short_code,
                'long_url' => $link->long_url,
                'clicks' => $link->clicks,
            ],
        ]);
    }

    /**
     * Handle the Link "restored" event.
     */
    public function restored(Link $link): void
    {
        ActivityLog::log([
            'action' => 'restored',
            'model_type' => Link::class,
            'model_id' => $link->id,
            'description' => "Mengembalikan link: {$link->short_code}",
            'properties' => [
                'short_code' => $link->short_code,
            ],
        ]);
    }

    /**
     * Handle the Link "force deleted" event.
     */
    public function forceDeleted(Link $link): void
    {
        ActivityLog::log([
            'action' => 'force_deleted',
            'model_type' => Link::class,
            'model_id' => $link->id,
            'description' => "Menghapus permanen link: {$link->short_code}",
            'properties' => [
                'short_code' => $link->short_code,
                'long_url' => $link->long_url,
                'clicks' => $link->clicks,
            ],
        ]);
    }
}