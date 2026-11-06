<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Illuminate\Support\Facades\Storage;
use Modules\Rentri\Actions\Concerns\ResolvesFirNumber;
use Modules\Rentri\Datas\RentriRequestData;
use Modules\Rentri\Datas\RentriResponseData;
use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Exceptions\RentriRequestException;
use Modules\Rentri\Models\RentriCredential;
use Modules\Waste\Models\Fir;
use Spatie\QueueableAction\QueueableAction;
use function Safe\preg_replace;

/**
 * Scarica l'xFIR (formulario digitale firmato) con `GET /{numero_fir}/xfir` (verificato) e lo salva sul disco
 * privato `rentri.xfir.disk`. Restituisce il percorso relativo al disco.
 * Content type della risposta DA VERIFICARE: il body e' salvato cosi' com'e'.
 */
class DownloadXfirAction
{
    use QueueableAction;
    use ResolvesFirNumber;

    public function execute(Fir $fir, RentriCredential $credential): string
    {
        $exchange = app(SendRentriRequestAction::class)->exchange($credential, new RentriRequestData(
            service: RentriServiceEnum::FORMULARI,
            method: 'GET',
            path: $this->firPath($fir, 'xfir'),
            accept: config()->string('rentri.xfir.accept'),
        ), $fir);

        $response = $exchange->response;
        if (! $response instanceof RentriResponseData || ! $response->successful() || $response->raw === '') {
            throw RentriRequestException::fromTransmission($exchange->transmission, $response?->problem());
        }

        $path = trim(config()->string('rentri.xfir.directory'), '/')
            .'/'.preg_replace('/[^A-Za-z0-9_.-]/', '_', rawurldecode($this->firNumber($fir)))
            .'.'.config()->string('rentri.xfir.extension');

        Storage::disk(config()->string('rentri.xfir.disk'))->put($path, $response->raw);

        return $path;
    }
}
