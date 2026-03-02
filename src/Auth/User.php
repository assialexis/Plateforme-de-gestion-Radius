<?php
/**
 * Classe User - Gestion des utilisateurs
 * Multi-tenant: Admin est le rôle principal (tenant owner)
 */

class User
{
    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_VENDEUR = 'vendeur';
    public const ROLE_GERANT = 'gerant';
    public const ROLE_CLIENT = 'client';
    public const ROLE_TECHNICIEN = 'technicien';

    private ?int $id = null;
    private string $username;
    private string $email;
    private ?string $phone = null;
    private ?string $fullName = null;
    private ?string $avatar = null;
    private string $role;
    private ?int $parentId = null;
    private bool $isActive = true;
    private ?array $preferences = null;
    private ?string $lastLogin = null;
    private float $creditBalance = 0.0;
    private float $smsCreditBalance = 0.0;
    private array $zones = [];
    private array $nas = [];

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }

    public function hydrate(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->username = $data['username'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->phone = $data['phone'] ?? null;
        $this->fullName = $data['full_name'] ?? null;
        $this->avatar = $data['avatar'] ?? null;
        $this->role = $data['role'] ?? self::ROLE_CLIENT;
        $this->parentId = $data['parent_id'] ?? null;
        $this->isActive = (bool)($data['is_active'] ?? true);
        $this->preferences = isset($data['preferences']) ? json_decode($data['preferences'], true) : null;
        $this->lastLogin = $data['last_login'] ?? null;
        $this->creditBalance = (float)($data['credit_balance'] ?? 0);
        $this->smsCreditBalance = (float)($data['sms_credit_balance'] ?? 0);
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): ?string { return $this->phone; }
    public function getFullName(): ?string { return $this->fullName; }
    public function getAvatar(): ?string { return $this->avatar; }
    public function getRole(): string { return $this->role; }
    public function getParentId(): ?int { return $this->parentId; }
    public function isActive(): bool { return $this->isActive; }
    public function getPreferences(): ?array { return $this->preferences; }
    public function getLastLogin(): ?string { return $this->lastLogin; }
    public function getCreditBalance(): float { return $this->creditBalance; }
    public function getSmsCreditBalance(): float { return $this->smsCreditBalance; }
    public function getZones(): array { return $this->zones; }
    public function getNas(): array { return $this->nas; }

    public function setZones(array $zones): void { $this->zones = $zones; }
    public function setNas(array $nas): void { $this->nas = $nas; }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isGerant(): bool
    {
        return $this->role === self::ROLE_GERANT;
    }

    public function isVendeur(): bool
    {
        return $this->role === self::ROLE_VENDEUR;
    }

    public function isClient(): bool
    {
        return $this->role === self::ROLE_CLIENT;
    }

    public function isTechnicien(): bool
    {
        return $this->role === self::ROLE_TECHNICIEN;
    }

    public function isAtLeastAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN]);
    }

    public function canManageUsers(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN]);
    }

    public function canCreateGerants(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN]);
    }

    public function canCreateVendeurs(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN, self::ROLE_GERANT, self::ROLE_TECHNICIEN]);
    }

    public function canCreateTechniciens(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN, self::ROLE_GERANT]);
    }

    public function canCreateAdmins(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canManageAdmins(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canManageZones(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN, self::ROLE_TECHNICIEN]);
    }

    public function canManageNas(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN, self::ROLE_VENDEUR, self::ROLE_TECHNICIEN]);
    }

    public function hasAccessToNas(int $nasId): bool
    {
        // SuperAdmin et Admin ont accès à tous les NAS
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return true;
        }

        // Vendeur a accès uniquement aux NAS qui lui sont assignés
        if (in_array($this->role, [self::ROLE_VENDEUR, self::ROLE_TECHNICIEN])) {
            foreach ($this->nas as $nas) {
                if ($nas['nas_id'] == $nasId) {
                    return true;
                }
            }
        }

        return false;
    }

    public function canManageProfiles(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN, self::ROLE_TECHNICIEN]);
    }

    public function canCreateVouchers(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN, self::ROLE_GERANT]);
    }

    public function canViewStats(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN, self::ROLE_GERANT]);
    }

    public function canAccessSettings(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN, self::ROLE_TECHNICIEN]);
    }

    public function hasAccessToZone(int $zoneId): bool
    {
        foreach ($this->zones as $zone) {
            if ($zone['zone_id'] == $zoneId) {
                return true;
            }
        }
        return false;
    }

    public function getRoleLabel(): string
    {
        return match($this->role) {
            self::ROLE_SUPERADMIN => __('role.superadmin') ?? 'Super Admin',
            self::ROLE_ADMIN => __('role.admin'),
            self::ROLE_VENDEUR => __('role.vendeur'),
            self::ROLE_GERANT => __('role.gerant'),
            self::ROLE_TECHNICIEN => __('role.technicien') ?? 'Technicien',
            self::ROLE_CLIENT => __('role.client'),
            default => __('role.unknown')
        };
    }

    public function getRoleColor(): string
    {
        return match($this->role) {
            self::ROLE_SUPERADMIN => 'red',
            self::ROLE_ADMIN => 'blue',
            self::ROLE_VENDEUR => 'orange',
            self::ROLE_GERANT => 'green',
            self::ROLE_TECHNICIEN => 'purple',
            self::ROLE_CLIENT => 'gray',
            default => 'gray'
        };
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'full_name' => $this->fullName,
            'avatar' => $this->avatar,
            'role' => $this->role,
            'role_label' => $this->getRoleLabel(),
            'role_color' => $this->getRoleColor(),
            'parent_id' => $this->parentId,
            'is_active' => $this->isActive,
            'credit_balance' => $this->creditBalance,
            'sms_credit_balance' => $this->smsCreditBalance,
            'last_login' => $this->lastLogin,
            'zones' => $this->zones,
            'nas' => $this->nas,
            'permissions' => [
                'manage_users' => $this->canManageUsers(),
                'create_vendeurs' => $this->canCreateVendeurs(),
                'create_gerants' => $this->canCreateGerants(),
                'create_admins' => $this->canCreateAdmins(),
                'manage_admins' => $this->canManageAdmins(),
                'manage_zones' => $this->canManageZones(),
                'manage_nas' => $this->canManageNas(),
                'manage_profiles' => $this->canManageProfiles(),
                'create_vouchers' => $this->canCreateVouchers(),
                'view_stats' => $this->canViewStats(),
                'access_settings' => $this->canAccessSettings(),
            ]
        ];
    }
}