<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name');
            $table->string('slug')->unique();
            $table->string('sector');
            $table->text('summary');
            $table->text('about')->nullable();
            $table->text('history')->nullable();
            $table->string('tagline');
            $table->string('banner_path')->nullable();
            $table->string('about_image_path')->nullable();
            $table->string('accent', 7)->default('#a53b2c');
            $table->string('illustration')->default('boxes');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('hours')->nullable();
            $table->text('address')->nullable();
            $table->string('map_query')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_demo')->default(true);
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category');
            $table->text('description');
            $table->text('specifications')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        foreach (['pillars', 'process_steps'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->text('description');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('subject');
            $table->text('message');
            $table->string('status')->default('new')->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['inquiries', 'process_steps', 'pillars', 'products', 'companies'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};
