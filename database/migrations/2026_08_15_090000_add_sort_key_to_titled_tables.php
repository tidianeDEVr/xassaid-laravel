<?php

use App\Support\NaturalSort;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['audios', 'audio_categories', 'articles', 'files'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table) || Schema::hasColumn($table, 'sort_key')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->string('sort_key', 512)->nullable()->index("{$table}_sort_key_index");
            });

            DB::table($table)->select('id', 'title')->orderBy('id')->chunk(500, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    DB::table($table)->where('id', $row->id)->update([
                        'sort_key' => NaturalSort::key($row->title),
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'sort_key')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('sort_key');
                });
            }
        }
    }
};
