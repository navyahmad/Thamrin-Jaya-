<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->string('slug', 100)->unique();
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->string('logo_alt')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('template_key', 100)->default('default');
            $table->string('primary_color', 7)->default('#9e342b');
            $table->string('secondary_color', 7)->default('#f7f5ef');
            $table->string('font_family', 100)->nullable();
            $table->json('theme_settings')->nullable();
            $table->text('footer_description')->nullable();
            $table->string('copyright_text')->nullable();
            $table->json('social_links')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->json('contact_details')->nullable()->comment('Portal contact details; subsidiary contacts are read from companies.');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 100);
            $table->string('title');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_image_path')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['site_id', 'slug']);
            $table->unique(['id', 'site_id']);
        });

        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('key', 100);
            $table->string('type', 60);
            $table->string('variant', 100)->default('default');
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->longText('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('button_label')->nullable();
            $table->string('button_url', 2048)->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['page_id', 'key']);
            $table->index(['page_id', 'is_active', 'sort_order']);
        });

        Schema::create('section_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_section_id')->constrained()->cascadeOnDelete();
            $table->string('key', 100)->nullable();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->longText('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('file_path')->nullable();
            $table->string('link_label')->nullable();
            $table->string('link_url', 2048)->nullable();
            $table->string('value', 100)->nullable();
            $table->string('unit', 100)->nullable();
            $table->date('occurred_on')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['page_section_id', 'key']);
            $table->index(['page_section_id', 'is_active', 'sort_order']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('company_service', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->primary(['company_id', 'service_id']);
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('key', 100);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['site_id', 'key']);
            $table->unique(['id', 'site_id']);
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('site_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('page_id')->nullable();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('key', 100)->nullable();
            $table->string('label');
            $table->string('anchor', 100)->nullable();
            $table->string('url', 2048)->nullable();
            $table->enum('type', ['link', 'mega_menu'])->default('link');
            $table->boolean('open_in_new_tab')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['menu_id', 'key']);
            $table->unique(['id', 'menu_id']);
            $table->index(['menu_id', 'parent_id', 'sort_order']);
            $table->foreign(['menu_id', 'site_id'])->references(['id', 'site_id'])->on('menus')->cascadeOnDelete();
            $table->foreign(['page_id', 'site_id'])->references(['id', 'site_id'])->on('pages')->cascadeOnDelete();
            $table->foreign(['parent_id', 'menu_id'])->references(['id', 'menu_id'])->on('menu_items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['menu_items', 'menus', 'company_service', 'services', 'section_items', 'page_sections', 'pages', 'sites'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
