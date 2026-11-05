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
            // Morph verso modelli di altri moduli (Fir, Register, RegisterEntry): niente vincoli.
            $table->nullableMorphs('subject');
            $table->unsignedBigInteger('rentri_credential_id')->index();
            $table->string('service', 32)->index();
            $table->string('method', 8);
            $table->string('path', 512);
            $table->json('request_payload')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->string('transaction_id')->nullable()->index();
            $table->string('status', 16)->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('completed_at')->nullable();
        });

        $this->tableUpdate(function (Blueprint $table): void {
            if (! $this->hasColumn('completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }
            $this->updateTimestamps($table, false);
        });
    }
};
