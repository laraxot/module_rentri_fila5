<?php

declare(strict_types=1);

namespace Modules\Rentri\Datas;

use Spatie\LaravelData\Data;

/**
 * Lotto di scritture del registro da trasmettere (TransmitRegisterEntriesAction).
 */
class RentriRegisterPayloadData extends Data
{
    /**
     * @param  list<RentriRegisterEntryPayloadData>  $entries
     */
    public function __construct(
        public string $registerIdentifier,
        public array $entries,
    ) {}

    /**
     * @return list<int>
     */
    public function entryIds(): array
    {
        return array_map(static fn (RentriRegisterEntryPayloadData $entry): int => $entry->entryId, $this->entries);
    }

    /**
     * @return array<string, mixed>
     */
    public function toRentri(): array
    {
        return [
            config()->string('rentri.payload.register.entries_key') => array_map(
                static fn (RentriRegisterEntryPayloadData $entry): array => $entry->toRentri(),
                $this->entries,
            ),
        ];
    }
}
