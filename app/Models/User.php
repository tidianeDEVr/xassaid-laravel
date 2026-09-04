<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Super administrateur : seul compte autorisé à gérer les admins, la
     * communauté (vidéos, comptes app), la publication YouTube et la
     * maintenance des fichiers.
     */
    public const SUPER_ADMIN_EMAIL = 'cheikhtiindiaye@gmail.com';

    public function isSuperAdmin(): bool
    {
        return $this->email === self::SUPER_ADMIN_EMAIL;
    }

    public function hasRole($role)
    {
        $rolesArray = $this->roles->pluck('libelle')->toArray();

        return in_array($role, $rolesArray);
    }

    protected $with = ['roles'];

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

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
