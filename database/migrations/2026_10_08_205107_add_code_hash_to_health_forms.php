<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Suchbarer Hash des persönlichen Codes; der Code selbst bleibt verschlüsselt.
     */
    public function up(): void
    {
        Schema::connection('mysql_info')->table('health_forms', function (Blueprint $table) {
            $table->string('code_hash', 64)->nullable()->index();
        });

        $this->fillCodeHashes();
    }

    /**
     * Hash für bestehende Blätter nachtragen (wie HealthForm::hashCode()).
     */
    public function fillCodeHashes(): void
    {
        DB::connection('mysql_info')->table('health_forms')->whereNull('code_hash')->orderBy('id')
            ->chunk(500, function ($healthForms) {
                foreach ($healthForms as $healthForm) {
                    $code = Crypt::decryptString($healthForm->code);
                    DB::connection('mysql_info')->table('health_forms')->where('id', $healthForm->id)
                        ->update(['code_hash' => hash_hmac('sha256', (string) $code, config('app.key'))]);
                }
            });
    }

    public function down(): void
    {
        Schema::connection('mysql_info')->table('health_forms', function (Blueprint $table) {
            $table->dropColumn('code_hash');
        });
    }
};
