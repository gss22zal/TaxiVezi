<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // phone and is_active already added in add_role_id_to_users_table migration
            $table->string('password_hash')->nullable()->after('password');
            $table->string('role')->default('passenger')->after('password_hash');
            $table->string('first_name')->nullable()->after('role');
            $table->string('last_name')->nullable()->after('first_name');
            $table->boolean('is_verified')->default(false)->after('is_active');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->string('avatar_url')->nullable()->after('is_verified');
            $table->text('address')->nullable()->after('avatar_url');
            $table->date('birth_date')->nullable()->after('address');
            $table->string('locale')->default('ru')->after('birth_date');
            
            // Drop standard name field as we have first_name/last_name
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name');
            $table->dropColumn([
                'password_hash', 'role', 'first_name', 'last_name',
                'is_verified', 'phone_verified_at', 'avatar_url', 'address', 'birth_date', 'locale'
            ]);
        });
    }
};
