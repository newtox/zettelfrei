<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('presets')->insert([
            [
                'user_id' => null,
                'name' => 'Klassisch',
                'criteria' => json_encode(['Schmuck', 'Beleuchtung', 'Wuchs & Form', 'Baumspitze', 'Gesamteindruck']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => null,
                'name' => 'Schnell',
                'criteria' => json_encode(['Gesamteindruck']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => null,
                'name' => 'Ausführlich',
                'criteria' => json_encode(['Schmuck', 'Beleuchtung', 'Wuchs & Form', 'Baumspitze', 'Kreativität', 'Ambiente drumherum', 'Gesamteindruck']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('presets')->whereNull('user_id')->delete();
    }
};
