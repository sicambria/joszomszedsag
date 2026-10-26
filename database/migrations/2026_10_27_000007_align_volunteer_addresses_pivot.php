<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The legacy pivot (2020_03_21) used INT UNSIGNED keys while
     * addresses/users use BIGINT. Align types so FKs can be enforced.
     * Requires doctrine/dbal for column changes — avoided on purpose:
     * recreate the table instead (pivot data is re-derivable from forms).
     */
    public function up(): void
    {
        Schema::dropIfExists('user_data_volunteer_addresses');

        Schema::create('user_data_volunteer_addresses', function (Blueprint $table) {
            $table->foreignId('address_id')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('user_data_volunteer_id')->constrained('user_data_volunteer')->cascadeOnDelete();
            $table->primary(['address_id', 'user_data_volunteer_id'], 'user_data_volunteer_addresses_pkey');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_data_volunteer_addresses');

        Schema::create('user_data_volunteer_addresses', function (Blueprint $table) {
            $table->integer('address_id')->unsigned();
            $table->integer('user_data_volunteer_id')->unsigned();
            $table->primary(['address_id', 'user_data_volunteer_id'], 'user_data_volunteer_addresses_pkey');
        });
    }
};
