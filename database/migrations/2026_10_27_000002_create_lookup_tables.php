<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('post_code', 10)->index();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('available_post_codes', function (Blueprint $table) {
            $table->id();
            $table->string('post_code', 10)->unique();
        });

        Schema::create('help_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        // Eloquent default for App\RequestStatus (no $table override).
        Schema::create('request_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('address_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
        Schema::dropIfExists('address_types');
        Schema::dropIfExists('request_statuses');
        Schema::dropIfExists('help_types');
        Schema::dropIfExists('available_post_codes');
        Schema::dropIfExists('cities');
    }
};
