<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consolidate every users column the app actually reads/writes.
     * The legacy 2014 migration only had id/name/email/password; the rest
     * lived in Voyager BREAD (seed-only). Keep `name` for the default
     * RegisterController path (see BACKLOG BUG notes).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('password')->constrained('roles');
            $table->string('first_name')->nullable()->after('role_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('display_name')->nullable()->after('last_name');
            $table->integer('date_birth')->nullable()->after('display_name');
            $table->string('phone_number')->nullable()->after('date_birth');
            $table->string('facebook_profile')->nullable()->after('phone_number');
            $table->string('facebook_group')->nullable()->after('facebook_profile');
            $table->string('avatar')->nullable()->after('facebook_group');
            $table->text('settings')->nullable()->after('avatar');
            $table->text('description')->nullable()->after('settings');
            $table->foreignId('home_address_id')->nullable()->after('description')->constrained('addresses');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('home_address_id');
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn([
                'first_name', 'last_name', 'display_name', 'date_birth',
                'phone_number', 'facebook_profile', 'facebook_group',
                'avatar', 'settings', 'description',
            ]);
        });
    }
};
