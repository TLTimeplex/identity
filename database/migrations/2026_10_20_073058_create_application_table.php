<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('application_category', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('application', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('url')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('application_category')->nullOnDelete();

            $table->unsignedBigInteger('required_permission')->nullable();
            $table->foreign('required_permission')->references('id')->on('permissions')->nullOnDelete();

            // Client ID for Hydra integration to refuse OAuth requests that are not permitted
            // Only one application can have a specific Hydra client ID
            $table->string('hydra_client_id')->unique()->nullable();
        });
    }

    /**
     * This will drop both the 'application' and 'application_category' tables.
     */
    public function down(): void
    {
        Schema::dropIfExists('application');
        Schema::dropIfExists('application_category');
    }
};
