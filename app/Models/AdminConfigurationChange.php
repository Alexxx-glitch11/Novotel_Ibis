<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminConfigurationChange extends Model
{
    protected $table = 'admin_configuration_changes';

    protected $fillable = ['config_key', 'current_value', 'proposed_value', 'status', 'users_id_requested_by', 'note'];

    protected function casts(): array
    {
        return ['current_value' => 'array', 'proposed_value' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id_requested_by', 'users_id');
    }
}
