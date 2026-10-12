<?php

namespace App\Enums;

/**
 * Platform roles and the permissions each one grants (single source of truth).
 * See docs/architecture/authorization.md and ADR 0002.
 */
enum Role: string
{
    case Buyer = 'buyer';
    case Renter = 'renter';
    case Owner = 'owner';
    case Broker = 'broker';
    case Developer = 'developer';
    case Staff = 'staff';
    case Admin = 'admin';

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Buyer, self::Renter => [],
            self::Owner, self::Broker, self::Developer => [
                Permission::PropertiesCreate,
                Permission::PropertiesManageOwn,
            ],
            self::Staff => [
                Permission::PropertiesViewUnpublished,
                Permission::PropertiesModerate,
            ],
            self::Admin => Permission::cases(),
        };
    }

    /**
     * Roles a user may choose at registration. Broker and developer require
     * license verification; staff and admin are never self-assigned.
     *
     * @return list<self>
     */
    public static function selfAssignable(): array
    {
        return [self::Buyer, self::Renter, self::Owner];
    }

    /**
     * @return list<string>
     */
    public static function selfAssignableValues(): array
    {
        return array_map(fn (self $role): string => $role->value, self::selfAssignable());
    }
}
