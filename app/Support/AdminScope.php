<?php

namespace App\Support;

use App\Models\User;

class AdminScope
{
    /**
     * Institution ids an admin is allowed to manage.
     * Null means the caller is not an admin, so admin scoping does not apply.
     *
     * @return array<int>|null
     */
    public static function managedIds(?User $user): ?array
    {
        if (!$user || !$user->isAdmin()) {
            return null;
        }

        return $user->managedInstitutionIds();
    }

    public static function assert(?User $user, int|string|null $institutionId): void
    {
        $ids = self::managedIds($user);
        if ($ids === null) {
            return;
        }

        if ($institutionId === null || $institutionId === '' || !in_array((int) $institutionId, $ids, true)) {
            abort(403, 'You can only manage institutions assigned to your account.');
        }
    }

    /**
     * @return array<int>
     */
    public static function idsOrEmpty(?User $user): array
    {
        $ids = self::managedIds($user);

        return $ids === null ? [] : $ids;
    }

    public static function attachCreatedInstitution(?User $user, int $institutionId): void
    {
        if (!$user || !$user->isAdmin()) {
            return;
        }

        $ids = $user->managedInstitutionIds();
        $ids[] = $institutionId;
        $user->managedInstitutions()->sync(array_values(array_unique($ids)));

        if (!$user->institution_id) {
            $user->update(['institution_id' => $institutionId]);
        }
    }
}
