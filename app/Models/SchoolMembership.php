<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Models\Concerns\BelongsToSchool;
use Database\Factories\SchoolMembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['status'])]
class SchoolMembership extends Model
{
    /** @use HasFactory<SchoolMembershipFactory> */
    use BelongsToSchool, HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MembershipStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<SchoolMembership>  $query
     * @return Builder<SchoolMembership>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', MembershipStatus::Active);
    }
}
