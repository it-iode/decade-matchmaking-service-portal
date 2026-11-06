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
        // Create subthemes table
        Schema::create('subthemes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create support_types table
        Schema::create('support_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create target_audiences table
        Schema::create('target_audiences', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create request_details table for normalized data
        Schema::create('request_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->foreign('request_id')->references('id')->on('requests')->onDelete('cascade');

            // Basic identification
            $table->string('capacity_development_title');
            $table->enum('is_related_decade_action', ['Yes', 'No']);
            $table->string('unique_related_decade_action_id')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');

            // Project details
            $table->enum('has_significant_changes', ['Yes', 'No'])->nullable();
            $table->text('changes_description')->nullable();
            $table->text('change_effect')->nullable();
            $table->enum('request_link_type', ['yes', 'no'])->nullable();
            $table->enum('project_stage', ['Planning', 'Approved', 'Implementation', 'Closed', 'Other'])->nullable();
            $table->string('project_url')->nullable();

            // Activity details
            $table->enum('related_activity', ['Training', 'Workshop', 'Both']);
            $table->enum('delivery_format', ['Online', 'On-site', 'Blended']);
            $table->string('delivery_country')->nullable();
            $table->text('subthemes_other')->nullable();
            $table->text('support_types_other')->nullable();
            $table->text('target_audience_other')->nullable();

            // Content details
            $table->text('gap_description');
            $table->enum('has_partner', ['Yes', 'No'])->nullable();
            $table->string('partner_name')->nullable();
            $table->enum('partner_confirmed', ['Yes', 'No'])->nullable();
            $table->enum('needs_financial_support', ['Yes', 'No'])->nullable();
            $table->text('budget_breakdown')->nullable();
            $table->integer('support_months')->nullable();
            $table->date('completion_date')->nullable();
            $table->text('risks')->nullable();
            $table->text('personnel_expertise')->nullable();
            $table->text('direct_beneficiaries')->nullable();
            $table->integer('direct_beneficiaries_number')->nullable();
            $table->text('expected_outcomes')->nullable();
            $table->text('success_metrics')->nullable();
            $table->text('long_term_impact')->nullable();

            // Metadata
            $table->json('additional_data')->nullable(); // For any future fields
            $table->timestamps();

            // Indexes for performance
            $table->index(['capacity_development_title']);
            $table->index(['related_activity']);
            $table->index(['delivery_format']);
            $table->index(['delivery_country']);
            $table->index(['is_related_decade_action']);
            $table->index(['needs_financial_support']);
        });

        // Create pivot tables for many-to-many relationships
        Schema::create('request_subtheme', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('subtheme_id');
            $table->foreign('request_id')->references('id')->on('requests')->onDelete('cascade');
            $table->foreign('subtheme_id')->references('id')->on('subthemes')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['request_id', 'subtheme_id']);
            $table->index(['subtheme_id']);
        });

        Schema::create('request_support_type', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('support_type_id');
            $table->foreign('request_id')->references('id')->on('requests')->onDelete('cascade');
            $table->foreign('support_type_id')->references('id')->on('support_types')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['request_id', 'support_type_id']);
            $table->index(['support_type_id']);
        });

        Schema::create('request_target_audience', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('target_audience_id');
            $table->foreign('request_id')->references('id')->on('requests')->onDelete('cascade');
            $table->foreign('target_audience_id')->references('id')->on('target_audiences')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['request_id', 'target_audience_id']);
            $table->index(['target_audience_id']);
        });

        // Add full-text search index
        Schema::table('request_details', function (Blueprint $table) {
            $table->fullText(['capacity_development_title', 'gap_description', 'expected_outcomes'],'request_details_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_target_audience');
        Schema::dropIfExists('request_support_type');
        Schema::dropIfExists('request_subtheme');
        Schema::dropIfExists('request_details');
        Schema::dropIfExists('target_audiences');
        Schema::dropIfExists('support_types');
        Schema::dropIfExists('subthemes');
    }
};
