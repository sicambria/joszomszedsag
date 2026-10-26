<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_data_volunteer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('has_car')->default(false);
            $table->string('availability', 50)->nullable();
            $table->text('helping_groups')->nullable();
            $table->tinyInteger('status')->default(1)->index();
            $table->timestamps();
        });

        Schema::create('user_data_support', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('help_request')->constrained('help_types');
            $table->boolean('has_coronavirus')->default(false);
            $table->boolean('is_in_quarantine')->default(false);
            $table->boolean('has_chronic_illness')->default(false);
            $table->boolean('is_healthy')->default(false);
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(2)->index();
            $table->foreignId('volunteer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_data_support');
        Schema::dropIfExists('user_data_volunteer');
    }
};
