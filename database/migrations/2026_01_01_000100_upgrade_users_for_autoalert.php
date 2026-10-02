<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 60)->after('id');
            $table->string('last_name', 60)->after('first_name');
            $table->string('phone', 30)->nullable()->after('last_name');
            $table->string('role', 20)->default('USER')->after('password')->index();
            $table->boolean('is_active')->default(true)->after('role');
            $table->boolean('notify_email')->default(true);
            $table->boolean('notify_whatsapp')->default(false);
            $table->boolean('notify_in_app')->default(true);
            $table->string('default_frequency', 20)->default('IMMEDIATE');
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'last_name', 'phone', 'role', 'is_active',
                'notify_email', 'notify_whatsapp', 'notify_in_app',
                'default_frequency', 'last_login_at',
            ]);
            $table->string('name')->after('id');
        });
    }
};
