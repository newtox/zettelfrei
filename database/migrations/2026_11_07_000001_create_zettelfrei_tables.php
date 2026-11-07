<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('criteria');
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 12)->unique();
            $table->text('admin_token');
            $table->boolean('keep')->default(false);
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->string('theme', 20)->default('christmas');
            $table->string('mode', 10)->default('score');
            $table->string('item_label', 30)->default('Familie');
            $table->string('items_label', 30)->default('Familien');
            $table->boolean('exclude_own')->default(true);
            $table->boolean('photos_enabled')->default(true);
            $table->boolean('one_at_a_time')->default(true);
            $table->boolean('voting_open')->default(false);
            $table->unsignedTinyInteger('max_choices')->default(1);
            $table->unsignedSmallInteger('reveal_step')->default(0);
            $table->boolean('join_locked')->default(false);
            $table->timestamps();
        });

        Schema::create('criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('status', 10)->default('pending');
            $table->timestamps();
        });

        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 40);
            $table->string('token_hash', 64)->unique();
            $table->timestamps();
            $table->unique(['room_id', 'name']);
        });

        Schema::create('ballots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->string('voter_hash', 64);
            $table->timestamps();
            $table->unique(['station_id', 'voter_hash']);
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ballot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->unique(['ballot_id', 'criterion_id']);
        });

        Schema::create('choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->string('voter_hash', 64);
            $table->timestamps();
            $table->unique(['room_id', 'voter_hash', 'station_id']);
            $table->index(['room_id', 'voter_hash']);
        });

        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
        Schema::dropIfExists('choices');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('ballots');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('stations');
        Schema::dropIfExists('criteria');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('presets');
    }
};
