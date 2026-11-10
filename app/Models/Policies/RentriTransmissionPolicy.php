<?php

declare(strict_types=1);

namespace Modules\Rentri\Models\Policies;

use Modules\User\Models\Policies\UserBasePolicy;
use Modules\Xot\Contracts\UserContract;
use Modules\Xot\Models\Policies\Concerns\DeniesUndeclaredFilamentAbilities;
use Modules\Xot\Models\Policies\Concerns\RequiresOperationalPermissions;

/**
 * Le trasmissioni sono un log scritto solo dalle Actions: dal pannello si leggono, si verifica l'esito e si riprova.
 * create/update/delete sono negati anche al super-admin (il `before` della base restituisce null per le abilita' non concesse).
 */
class RentriTransmissionPolicy extends UserBasePolicy
{
    use DeniesUndeclaredFilamentAbilities;
    use RequiresOperationalPermissions;

    public function before(UserContract $user, string $_ability): ?bool
    {
        if (\in_array($_ability, ['create', 'update', 'delete', 'forceDelete', 'restore', 'deleteAny'], true)) {
            return false;
        }

        return parent::before($user, $_ability);
    }

    public function viewAny(UserContract $user): bool
    {
        return $user->hasPermissionTo('rentri_transmission.viewAny');
    }

    public function view(UserContract $user): bool
    {
        return $user->hasPermissionTo('rentri_transmission.view');
    }

    public function create(UserContract $user): bool
    {
        return false;
    }

    public function update(UserContract $user): bool
    {
        return false;
    }

    public function delete(UserContract $user): bool
    {
        return false;
    }

    /** "Verifica esito" e "riprova". */
    public function retry(UserContract $user): bool
    {
        return $user->hasPermissionTo('rentri_transmission.retry');
    }

    /** "Firma FIR": firma remota con PIN (SignFirTableAction). */
    public function sign(UserContract $user): bool
    {
        return $user->hasPermissionTo('rentri_transmission.sign');
    }
}
