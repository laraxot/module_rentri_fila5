<?php

declare(strict_types=1);

namespace Modules\Rentri\Filament\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Support\Htmlable;
use Modules\Rentri\Actions\CheckRentriServiceStatusAction;
use Modules\Rentri\Datas\RentriServiceStatusData;
use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Models\RentriCredential;
use Modules\Xot\Filament\Pages\XotBasePage;

/**
 * Stato dei servizi RENTRI visti da una credenziale (CheckRentriServiceStatusAction per ogni servizio monitorato).
 * La pagina tiene solo la scelta della credenziale e l'ultimo esito; la logica sta nelle Actions.
 *
 * Testi: lang/{locale}/rentri_status.php.
 */
class RentriStatusPage extends XotBasePage
{
    protected string $view = 'rentri::filament.pages.rentri-status';

    public ?int $credentialId = null;

    /**
     * Ultimo esito per servizio, serializzato per Livewire.
     *
     * @var array<string, array{health: string, http_status: int|null, message: string|null, checked_at: string}>
     */
    public array $results = [];

    public static function getNavigationLabel(): string
    {
        return static::trans('navigation.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return static::trans('navigation.group');
    }

    public function getTitle(): string|Htmlable
    {
        return static::trans('title');
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof Authorizable && $user->can('viewAny', RentriCredential::class);
    }

    public function mount(): void
    {
        $first = RentriCredential::query()->where('is_active', true)->orderBy('name')->value('id');
        $this->credentialId = is_numeric($first) ? (int) $first : null;
    }

    /**
     * @return array<int, string>
     */
    public function getCredentialOptions(): array
    {
        $options = [];
        /** @var iterable<RentriCredential> $credentials */
        $credentials = RentriCredential::query()->where('is_active', true)->orderBy('name')->get();
        foreach ($credentials as $credential) {
            $options[$credential->id] = $credential->name.' ('.$credential->environment->getLabel().')';
        }

        return $options;
    }

    public function check(): void
    {
        $credential = $this->credentialId === null ? null : RentriCredential::query()->find($this->credentialId);
        if (! $credential instanceof RentriCredential) {
            $this->results = [];

            return;
        }

        $action = app(CheckRentriServiceStatusAction::class);
        $results = [];
        foreach (RentriServiceEnum::monitored() as $service) {
            $results[$service->value] = $this->serialize($action->execute($credential, $service));
        }
        $this->results = $results;
    }

    /**
     * @return array<string, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            'check' => Action::make('check')
                ->icon('heroicon-o-signal')
                ->action(fn () => $this->check()),
        ];
    }

    /**
     * @return array{health: string, http_status: int|null, message: string|null, checked_at: string}
     */
    private function serialize(RentriServiceStatusData $status): array
    {
        return [
            'health' => $status->health->value,
            'http_status' => $status->httpStatus,
            'message' => $status->message,
            'checked_at' => $status->checkedAt->toIso8601String(),
        ];
    }
}
