<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();

            // The search that first matched, for "because you saved…" context.
            // Deleting the search keeps the alert: the user was still told.
            $table->foreignId('saved_search_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // One alert per user per listing, however many of their searches
            // match and however often the listing goes live. Being enforced
            // here also makes the alerting job safe to retry.
            $table->unique(['user_id', 'listing_id']);

            // A user's alerts are read newest first.
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
