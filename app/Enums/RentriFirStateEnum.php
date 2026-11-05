<?php

declare(strict_types=1);

namespace Modules\Rentri\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Modules\Xot\Traits\EnumTrait;

/**
 * Stati del formulario digitale RENTRI (`StatoFormulario`), verificati sulla documentazione pubblica.
 * Il valore e' la stringa esatta restituita dalle API.
 *
 * Etichette: Modules/Rentri/lang/{locale}/rentri_fir_state_enum.php.
 */
enum RentriFirStateEnum: string implements HasColor, HasIcon, HasLabel
{
    use EnumTrait;

    case INSERIMENTO_QUANTITA = 'InserimentoQuantita';
    case INSERIMENTO_QUANTITA_TRASPORTO_INIZIALE = 'InserimentoQuantitaTrasportoIniziale';
    case INSERIMENTO_TRASPORTO_INIZIALE = 'InserimentoTrasportoIniziale';
    case FIRMA_PRODUTTORE_TRASPORTATORE_INIZIALE = 'FirmaProduttoreTrasportatoreIniziale';
    case FIRMA_TRASPORTATORE_INIZIALE = 'FirmaTrasportatoreIniziale';
    case FIRMA_PRODUTTORE = 'FirmaProduttore';
    case INSERIMENTO_TRASPORTO_SUCCESSIVO = 'InserimentoTrasportoSuccessivo';
    case FIRMA_TRASPORTATORE_SUCCESSIVO = 'FirmaTrasportatoreSuccessivo';
    case FIRMA_ANNOTAZIONE = 'FirmaAnnotazione';
    case FIRMA_TRASBORDO_PARZIALE = 'FirmaTrasbordoParziale';
    case FIRMA_TRASBORDO_TOTALE = 'FirmaTrasbordoTotale';
    case FIRMA_SOSTA_TECNICA = 'FirmaSostaTecnica';
    case INSERIMENTO_ACCETTAZIONE = 'InserimentoAccettazione';
    case FIRMA_ACCETTAZIONE = 'FirmaAccettazione';
    case ACCETTATO = 'Accettato';
    case RESPINTO_ACCETTATO_PARZIALMENTE = 'RespintoAccettatoParzialmente';
    case FIRMA_DESTINATARIO_SUCCESSIVO = 'FirmaDestinatarioSuccessivo';
    case FIRMA_ACCETTAZIONE_SUCCESSIVA = 'FirmaAccettazioneSuccessiva';
    case FIRMA_ANNULLAMENTO = 'FirmaAnnullamento';
    case ANNULLATO = 'Annullato';

    /**
     * Stati in cui il formulario attende una firma.
     */
    public function awaitsSignature(): bool
    {
        return str_starts_with($this->value, 'Firma');
    }

    /**
     * Stati conclusivi del ciclo di vita del formulario.
     */
    public function isFinal(): bool
    {
        return \in_array($this, [self::ACCETTATO, self::RESPINTO_ACCETTATO_PARZIALMENTE, self::ANNULLATO], true);
    }
}
