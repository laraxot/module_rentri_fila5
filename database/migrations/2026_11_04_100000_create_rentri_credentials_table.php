<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Modules\Xot\Database\Migrations\XotBaseMigration;

return new class extends XotBaseMigration
{
    public function up(): void
    {
        $this->tableCreate(function (Blueprint $table): void {
            $table->bigIncrements('id');
            // FK cross-modulo (Waste.operator_sites): indice senza vincolo.
            $table->unsignedBigInteger('operator_site_id')->index();
            $table->string('name');
            $table->string('environment', 16)->index();
            $table->string('subject_identifier', 32);
            $table->string('certificate_path');
            // Cast `encrypted`: il testo cifrato e' molto piu' lungo della password.
            $table->text('certificate_password');
            $table->string('remote_signature_credential_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
        });

        $this->tableUpdate(function (Blueprint $table): void {
            if (! $this->hasColumn('remote_signature_credential_id')) {
                $table->string('remote_signature_credential_id')->nullable();
            }
            if (! $this->hasColumn('expires_at')) {
                $table->timestamp('expires_at')->nullable();
            }
            $this->updateTimestamps($table, true);
        });
    }
};
