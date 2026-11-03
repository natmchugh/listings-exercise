<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Criteria are real nullable columns, not a JSON blob: alerting has
            // to ask "which saved searches does this new listing match?", which
            // is a plain indexed WHERE over columns (NULL = "any").
            $table->unsignedInteger('max_price')->nullable();
            $table->unsignedTinyInteger('min_bedrooms')->nullable();
            $table->string('property_type')->nullable();
            $table->string('region')->nullable();
            $table->timestamps();

            // foreignId() doesn't index on SQLite, and the saved searches page
            // lists a user's searches newest first.
            //
            // There's deliberately no unique index over the criteria: NULLs are
            // distinct in unique indexes, so it wouldn't stop duplicates. That
            // check lives in StoreSavedSearchRequest.
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
    }
};
