<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('own_question', 120)->nullable()->after('exclude_own');
            $table->string('own_none_label', 40)->nullable()->after('own_question');
            $table->boolean('photos_own_only')->default(false)->after('photos_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['own_question', 'own_none_label', 'photos_own_only']);
        });
    }
};
