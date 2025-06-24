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
        Schema::create('personnel', function (Blueprint $table) {
            $table->id();
            
            $table->string('prefix', 20);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('mobile_number', 20);
            $table->string('email', 255);

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexing
            $table->index(['email'], 'personnel_email_index');
            $table->index(['mobile_number'], 'personnel_mobile_index');
            $table->index(['created_at'], 'personnel_created_index');
            $table->index(['deleted_at'], 'personnel_deleted_index');
            
            // Constraints
            $table->unique(['email', 'deleted_at'], 'personnel_email_unique');
            $table->unique(['mobile_number', 'deleted_at'], 'personnel_mobile_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel');
    }
};