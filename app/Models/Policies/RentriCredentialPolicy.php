<?php

declare(strict_types=1);

namespace Modules\Rentri\Models\Policies;

use Modules\User\Models\Policies\UserBasePolicy;
use Modules\Xot\Contracts\UserContract;
use Modules\Xot\Models\Policies\Concerns\DeniesUndeclaredFilamentAbilities;
use Modules\Xot\Models\Policies\Concerns\RequiresOperationalPermissions;

class RentriCredentialPolicy extends UserBasePolicy
{
    use DeniesUndeclaredFilamentAbilities;
    use RequiresOperationalPermissions;

    public function viewAny(UserContract $user): bool
    {
        return $user->hasPermissionTo('rentri_credential.viewAny');
    }

    public function view(UserContract $user): bool
    {
        return $user->hasPermissionTo('rentri_credential.view');
    }

    public function create(UserContract $user): bool
    {
        return $user->hasPermissionTo('rentri_credential.create');
    }

    public function update(UserContract $user): bool
    {
        return $user->hasPermissionTo('rentri_credential.update');
    }

    public function delete(UserContract $user): bool
    {
        return $user->hasPermissionTo('rentri_credential.delete');
    }
}
