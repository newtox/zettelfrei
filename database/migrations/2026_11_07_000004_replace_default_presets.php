<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PRESETS = [
        'Weihnachtsbaum' => ['Schmuck', 'Beleuchtung', 'Wuchs & Form', 'Baumspitze', 'Gesamteindruck'],
        'Essen & Backen' => ['Geschmack', 'Aussehen', 'Kreativität', 'Gesamteindruck'],
        'Kostüm & Outfit' => ['Kreativität', 'Aufwand', 'Wiedererkennung', 'Gesamteindruck'],
        'Deko & Basteln' => ['Idee', 'Umsetzung', 'Wirkung', 'Gesamteindruck'],
        'Allgemein' => ['Idee', 'Umsetzung', 'Gesamteindruck'],
        'Nur Gesamteindruck' => ['Gesamteindruck'],
    ];

    public function up(): void
    {
        DB::table('presets')->whereNull('user_id')->delete();

        $now = now();

        foreach (self::PRESETS as $name => $criteria) {
            DB::table('presets')->insert([
                'user_id' => null,
                'name' => $name,
                'criteria' => json_encode($criteria),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('presets')->whereNull('user_id')->whereIn('name', array_keys(self::PRESETS))->delete();
    }
};
