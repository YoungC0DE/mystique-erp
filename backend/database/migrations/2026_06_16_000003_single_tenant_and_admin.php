<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }

        if (Schema::hasColumn('users', 'is_super_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('is_super_admin', 'is_admin');
            });
        }

        if (Schema::hasColumn('roles', 'company_id')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
            });

            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique(['company_id', 'slug']);
                $table->dropColumn('company_id');
            });
        }

        if (! $this->indexExists('roles', 'roles_slug_unique')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->unique('slug');
            });
        }

        if (Schema::hasColumn('modules', 'company_id')) {
            Schema::table('modules', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
            });

            Schema::table('modules', function (Blueprint $table) {
                $table->dropUnique(['company_id', 'slug']);
                $table->dropColumn('company_id');
            });
        }

        if (! $this->indexExists('modules', 'modules_slug_unique')) {
            Schema::table('modules', function (Blueprint $table) {
                $table->unique('slug');
            });
        }

        if (Schema::hasColumn('module_records', 'company_id')) {
            Schema::table('module_records', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }

        if (Schema::hasColumn('activity_logs', 'company_id')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }

        if (Schema::hasColumn('record_audits', 'company_id')) {
            Schema::table('record_audits', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }

        Schema::dropIfExists('companies');
    }

    public function down(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('active')->default(true);
            $table->string('plan')->default('free');
            $table->timestamps();
        });

        if (Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('is_admin', 'is_super_admin');
                $table->foreignId('company_id')->nullable()->after('password')->constrained('companies')->nullOnDelete();
            });
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->foreignId('company_id')->nullable()->after('uuid')->constrained('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'slug']);
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->foreignId('company_id')->after('uuid')->constrained('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'slug']);
        });

        Schema::table('module_records', function (Blueprint $table) {
            $table->foreignId('company_id')->after('module_id')->constrained('companies')->cascadeOnDelete();
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('uuid')->constrained('companies')->nullOnDelete();
        });

        Schema::table('record_audits', function (Blueprint $table) {
            $table->foreignId('company_id')->after('record_id')->constrained('companies')->cascadeOnDelete();
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $indexes = Schema::getConnection()->select(
            'SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?',
            [$index]
        );

        return count($indexes) > 0;
    }
};
