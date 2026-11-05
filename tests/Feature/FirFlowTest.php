<?php

declare(strict_types=1);

namespace Modules\Rentri\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Modules\Rentri\Actions\CancelDigitalFirAction;
use Modules\Rentri\Actions\DownloadXfirAction;
use Modules\Rentri\Actions\IssueDigitalFirAction;
use Modules\Rentri\Actions\MapFirAcceptanceToRentriPayloadAction;
use Modules\Rentri\Actions\MapFirToRentriPayloadAction;
use Modules\Rentri\Actions\MapRegisterEntryToRentriPayloadAction;
use Modules\Rentri\Actions\ResolveCredentialAction;
use Modules\Rentri\Actions\SubmitFirAcceptanceAction;
use Modules\Rentri\Actions\TransmitHazardousFirDataAction;
use Modules\Rentri\Actions\TransmitRegisterEntriesAction;
use Modules\Rentri\Enums\TransmissionStatusEnum;
use Modules\Rentri\Exceptions\RentriCredentialException;
use Modules\Rentri\Models\RentriCredential;
use Modules\Rentri\Tests\Support\TestCertificate;
use Modules\Rentri\Tests\TestCase;
use Modules\Waste\Enums\AcceptanceEnum;
use Modules\Waste\Enums\FirStatusEnum;
use Modules\Waste\Enums\HazardPropertyEnum;
use Modules\Waste\Enums\PhysicalStateEnum;
use Modules\Waste\Enums\QuantityUnitEnum;
use Modules\Waste\Enums\RegisterCauseEnum;
use Modules\Waste\Enums\RegisterEntryTypeEnum;
use Modules\Waste\Enums\WasteOperationEnum;
use Modules\Waste\Models\EwcCode;
use Modules\Waste\Models\Fir;
use Modules\Waste\Models\Operator;
use Modules\Waste\Models\OperatorSite;
use Modules\Waste\Models\Register;
use Modules\Waste\Models\RegisterEntry;
use PHPUnit\Framework\Assert;

uses(TestCase::class);

beforeEach(function (): void {
    Sleep::fake();
    Http::preventStrayRequests();
});

/**
 * Fir in memoria con i soli dati di accettazione (nessuna query: il mapping li legge dal modello).
 */
function rentriAcceptedFir(AcceptanceEnum $acceptance, float $acceptedKg): Fir
{
    return (new Fir)->forceFill([
        'id' => 501,
        'number' => 'XRAB 000123 AA',
        'is_digital' => true,
        'status' => FirStatusEnum::from('accepted'),
        'acceptance' => $acceptance,
        'accepted_quantity_kg' => $acceptedKg,
        // 08:30 UTC = 09:30 a Roma (CET): indipendente dal cast datetime del modello Waste.
        'arrived_at' => CarbonImmutable::parse('2026-11-04 08:30:00', 'UTC'),
        'rejection_reason' => null,
    ]);
}

/**
 * Anagrafiche e FIR reali sulla connessione waste (richiede le migrazioni del modulo Waste).
 *
 * @return array{fir: Fir, plant: OperatorSite, producer: Operator, ewc: EwcCode}
 */
function rentriWasteFixture(): array
{
    if (! is_dir(\dirname(__DIR__, 3).'/Waste/database/migrations')) {
        test()->markTestSkipped('Modulo Waste non presente.');
    }

    $producer = (new Operator)->forceFill([
        'business_name' => 'Produttore Srl', 'fiscal_code' => '11111111111', 'vat_number' => '11111111111',
        'roles' => ['producer'], 'email' => 'p@example.test', 'pec' => 'p@pec.example.test', 'is_active' => true,
    ]);
    $producer->save();
    $plantOperator = (new Operator)->forceFill([
        'business_name' => 'Impianto Spa', 'fiscal_code' => '22222222222', 'vat_number' => '22222222222',
        'roles' => ['recipient'], 'email' => 'i@example.test', 'pec' => 'i@pec.example.test', 'is_active' => true,
    ]);
    $plantOperator->save();
    $plant = (new OperatorSite)->forceFill([
        'operator_id' => $plantOperator->getKey(), 'name' => 'Impianto Padova', 'rentri_site_number' => 'OP2501KCJ031133-PD0001',
        'is_plant' => true, 'is_active' => true,
    ]);
    $plant->save();
    $ewc = (new EwcCode)->forceFill([
        'code' => '16 01 07', 'chapter' => '16', 'sub_chapter' => '16 01', 'description' => 'filtri dell\'olio',
        'is_hazardous' => true, 'is_mirror' => false, 'is_active' => true,
    ]);
    $ewc->save();

    $fir = (new Fir)->forceFill([
        'number' => null, 'is_digital' => true, 'status' => FirStatusEnum::from('draft'),
        'producer_operator_id' => $producer->getKey(), 'recipient_operator_id' => $plantOperator->getKey(),
        'recipient_site_id' => $plant->getKey(), 'ewc_code_id' => $ewc->getKey(),
        'physical_state' => PhysicalStateEnum::from('solid_non_powdery'),
        'hazard_properties' => [HazardPropertyEnum::from('HP14')],
        'operation' => WasteOperationEnum::from('R13'),
        'quantity' => 1250.5, 'quantity_unit' => QuantityUnitEnum::from('kg'), 'is_quantity_estimated' => true,
        'vehicle_plate' => 'AB123CD',
    ]);
    $fir->save();

    return ['fir' => $fir, 'plant' => $plant, 'producer' => $producer, 'ewc' => $ewc];
}

describe('MapFirAcceptanceToRentriPayloadAction', function (): void {
    test('campi verificati: tipoAccettazione, quantitaAccettata.valore, dataOraArrivo', function (): void {
        $payload = app(MapFirAcceptanceToRentriPayloadAction::class)
            ->execute(rentriAcceptedFir(AcceptanceEnum::from('full'), 1000.0))
            ->toRentri();

        Assert::assertSame('A', $payload['tipoAccettazione']);
        Assert::assertSame(1000.0, data_get($payload, 'quantitaAccettata.valore'));
        Assert::assertSame('2026-11-04T09:30:00+01:00', $payload['dataOraArrivo']);
    });

    test('accettazione parziale usa la codifica configurata', function (): void {
        $payload = app(MapFirAcceptanceToRentriPayloadAction::class)
            ->execute(rentriAcceptedFir(AcceptanceEnum::from('partial'), 800.0))
            ->toRentri();

        Assert::assertSame(config('rentri.codes.acceptance.partial'), $payload['tipoAccettazione']);
        Assert::assertSame(800.0, data_get($payload, 'quantitaAccettata.valore'));
    });
});

describe('SubmitFirAcceptanceAction', function (): void {
    test('POST /{numero_fir}/accettazione con il body mappato e log su rentri_transmissions', function (): void {
        $credential = TestCertificate::generate()->credential();
        $fir = rentriAcceptedFir(AcceptanceEnum::from('full'), 1000.0);
        Http::fake(['*' => Http::response(['transazione_id' => 'TX-ACC'], 202)]);

        $transmission = app(SubmitFirAcceptanceAction::class)->execute($fir, $credential);

        Http::assertSent(function (Request $request): bool {
            Assert::assertSame('https://demoapi.rentri.gov.it/formulari/v1.0/XRAB%20000123%20AA/accettazione', $request->url());
            $body = json_decode($request->body(), true);
            Assert::assertIsArray($body);
            Assert::assertSame('A', data_get($body, 'tipoAccettazione'));
            Assert::assertSame(1000.0, data_get($body, 'quantitaAccettata.valore'));
            Assert::assertSame('2026-11-04T09:30:00+01:00', data_get($body, 'dataOraArrivo'));

            return true;
        });
        Assert::assertSame(TransmissionStatusEnum::SUBMITTED, $transmission->status);
        Assert::assertSame($fir->getMorphClass(), $transmission->subject_type);
        Assert::assertSame(501, $transmission->subject_id);
    });
});

describe('CancelDigitalFirAction e DownloadXfirAction', function (): void {
    test('annulla-fir seguito da note-annullamento con campo note', function (): void {
        $credential = TestCertificate::generate()->credential();
        Http::fake(['*' => Http::response(['transazione_id' => 'TX-C'], 202)]);

        app(CancelDigitalFirAction::class)->execute(rentriAcceptedFir(AcceptanceEnum::from('full'), 1.0), $credential, 'Errore di compilazione');

        Http::assertSentCount(2);
        $requests = rentriRecordedRequests();
        Assert::assertStringEndsWith('/annulla-fir', $requests[0]->url());
        Assert::assertStringEndsWith('/note-annullamento', $requests[1]->url());
        Assert::assertSame(['note' => 'Errore di compilazione'], json_decode($requests[1]->body(), true));
    });

    test('xFIR salvato su disco privato', function (): void {
        // Prima il disco xFIR, poi la credenziale: se condividono il disco, Storage::fake cancellerebbe il .p12.
        Storage::fake(config()->string('rentri.xfir.disk'));
        $credential = TestCertificate::generate()->credential();
        Http::fake(['*/xfir' => Http::response('<xFIR/>', 200, ['Content-Type' => 'application/xml'])]);

        $path = app(DownloadXfirAction::class)->execute(rentriAcceptedFir(AcceptanceEnum::from('full'), 1.0), $credential);

        Assert::assertTrue(Storage::disk(config()->string('rentri.xfir.disk'))->exists($path));
        Assert::assertSame('<xFIR/>', Storage::disk(config()->string('rentri.xfir.disk'))->get($path));
    });
});

describe('Flussi con anagrafiche Waste', function (): void {
    test('MapFirToRentriPayloadAction: num_iscr_sito, EER, codifiche', function (): void {
        $fixture = rentriWasteFixture();

        $payload = app(MapFirToRentriPayloadAction::class)->execute($fixture['fir'], $fixture['plant'])->toRentri();

        Assert::assertSame('OP2501KCJ031133-PD0001', $payload['num_iscr_sito']);
        Assert::assertSame('160107', data_get($payload, config()->string('rentri.payload.fir.ewc_code')));
        Assert::assertSame(['HP14'], data_get($payload, config()->string('rentri.payload.fir.hazard_properties')));
        Assert::assertSame(1250.5, data_get($payload, config()->string('rentri.payload.fir.quantity')));
        Assert::assertSame('11111111111', data_get($payload, config()->string('rentri.payload.fir.producer_fiscal_code')));
        Assert::assertSame(config('rentri.codes.physical_state.solid_non_powdery'), data_get($payload, config()->string('rentri.payload.fir.physical_state')));
    });

    test('IssueDigitalFirAction: risposta sincrona con numero => Fir aggiornato', function (): void {
        $fixture = rentriWasteFixture();
        $credential = TestCertificate::generate()->credential(['operator_site_id' => $fixture['plant']->getKey()]);
        Http::fake(['demoapi.rentri.gov.it/formulari/v1.0/' => Http::response(['numero_fir' => 'XRAB 000999 ZZ'], 201)]);

        $transmission = app(IssueDigitalFirAction::class)->execute($fixture['fir'], $credential);

        Assert::assertSame(TransmissionStatusEnum::SUCCEEDED, $transmission->status);
        $fir = $fixture['fir']->refresh();
        Assert::assertSame('XRAB 000999 ZZ', $fir->getAttribute('number'));
    });

    test('TransmitHazardousFirDataAction su FIR digitale usa /estrai', function (): void {
        $fixture = rentriWasteFixture();
        $fixture['fir']->forceFill(['number' => 'XRAB 1'])->save();
        $credential = TestCertificate::generate()->credential(['operator_site_id' => $fixture['plant']->getKey()]);
        Http::fake(['*' => Http::response(['transazione_id' => 'TX-H'], 202)]);

        app(TransmitHazardousFirDataAction::class)->execute($fixture['fir'], $credential);

        Http::assertSent(static fn (Request $request): bool => str_ends_with($request->url(), '/formulari/v1.0/trasmissioni/operatore/OP2501KCJ031133-PD0001/XRAB%201/estrai'));
    });

    test('ResolveCredentialAction: attiva e non scaduta', function (): void {
        $fixture = rentriWasteFixture();
        $siteId = $fixture['plant']->getKey();
        RentriCredential::factory()->expired()->createOne(['operator_site_id' => $siteId]);
        RentriCredential::factory()->inactive()->createOne(['operator_site_id' => $siteId]);
        $valid = RentriCredential::factory()->createOne(['operator_site_id' => $siteId]);

        Assert::assertSame($valid->id, app(ResolveCredentialAction::class)->execute($fixture['plant'])->id);
    });

    test('ResolveCredentialAction: nessuna credenziale => eccezione', function (): void {
        app(ResolveCredentialAction::class)->execute(rentriWasteFixture()['plant']);
    })->throws(RentriCredentialException::class);

    test('TransmitRegisterEntriesAction: solo le non trasmesse, transmitted_at dopo il polling', function (): void {
        $fixture = rentriWasteFixture();
        $register = (new Register)->forceFill([
            'operator_site_id' => $fixture['plant']->getKey(), 'name' => 'Registro impianto',
            'rentri_identifier' => 'REG-PD-1', 'is_active' => true,
        ]);
        $register->save();
        $make = static function (int $number, ?CarbonImmutable $transmittedAt) use ($register, $fixture): RegisterEntry {
            $entry = (new RegisterEntry)->forceFill([
                'register_id' => $register->getKey(), 'year' => 2026, 'number' => $number,
                'type' => RegisterEntryTypeEnum::from('load'), 'cause' => RegisterCauseEnum::from('from_third_parties'),
                'recorded_at' => CarbonImmutable::parse('2026-11-04 10:00:00'), 'operation_at' => CarbonImmutable::parse('2026-11-04 09:30:00'),
                'ewc_code_id' => $fixture['ewc']->getKey(), 'physical_state' => PhysicalStateEnum::from('solid_non_powdery'),
                'hazard_properties' => [HazardPropertyEnum::from('HP14')], 'quantity_kg' => 1000.0,
                'operation' => WasteOperationEnum::from('R13'), 'fir_id' => $fixture['fir']->getKey(),
                'counterpart_operator_id' => $fixture['producer']->getKey(), 'load_entry_ids' => [],
                'transmitted_at' => $transmittedAt,
            ]);
            $entry->save();

            return $entry;
        };
        $make(1, CarbonImmutable::now());
        $pending = $make(2, null);

        $mapped = app(MapRegisterEntryToRentriPayloadAction::class)->execute($pending)->toRentri();
        Assert::assertSame(config('rentri.codes.register_cause.from_third_parties'), data_get($mapped, config()->string('rentri.payload.register_entry.cause')));
        Assert::assertSame(config('rentri.codes.register_entry_type.load'), data_get($mapped, config()->string('rentri.payload.register_entry.type')));

        $credential = TestCertificate::generate()->credential(['operator_site_id' => $fixture['plant']->getKey()]);
        Http::fake([
            '*/REG-PD-1/movimenti' => Http::response(['transazione_id' => 'TX-REG'], 202),
            '*/TX-REG/status' => Http::response(['stato' => 'Eseguita'], 200),
            '*/TX-REG/result' => Http::response([], 200),
        ]);

        $transmission = app(TransmitRegisterEntriesAction::class)->execute($register, $credential);

        Assert::assertSame([$pending->getKey()], data_get($transmission->request_payload, 'meta.entry_ids'));
        $sent = data_get($transmission->request_payload, 'body.'.config()->string('rentri.payload.register.entries_key'));
        Assert::assertIsArray($sent);
        Assert::assertCount(1, $sent);
        Assert::assertNull($pending->refresh()->getAttribute('transmitted_at'));

        app(\Modules\Rentri\Actions\PollRentriTransactionAction::class)->execute($transmission);

        $fresh = $pending->refresh();
        Assert::assertNotNull($fresh->getAttribute('transmitted_at'));
        Assert::assertSame('TX-REG', $fresh->getAttribute('rentri_transmission_ref'));
    });
});
