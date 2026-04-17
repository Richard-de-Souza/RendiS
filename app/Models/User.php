<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'cpf',
        'password',
        'role_id',
        'monthly_working_hours',
        'salary',
        'age',
        'investment_profile',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function loginAudits()
    {
        return $this->hasMany(LoginAudit::class);
    }

    public function convertMoneyToHours($amount)
    {
        if (!$this->salary || !$this->monthly_working_hours || $this->monthly_working_hours <= 0) {
            return 0;
        }

        $hourlyRate = $this->salary / $this->monthly_working_hours;
        
        if ($hourlyRate <= 0) {
            return 0;
        }

        return $amount / $hourlyRate;
    }

    /**
     * Formats decimal hours into a readable 'XXh YYm' string.
     * 
     * @param float $decimalHours
     * @return string
     */
    public function formatHoursToReadableTime($decimalHours)
    {
        if ($decimalHours <= 0) return '0h';

        $hours = floor($decimalHours);
        $minutes = round(($decimalHours - $hours) * 60);

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}m";
        }
    }
}
