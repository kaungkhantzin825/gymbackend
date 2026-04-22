<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table already exists from a previous migration, skipping to avoid crash.
    }

    public function down(): void
    {
        // Nothing to drop as we didn't create anything
    }
};
