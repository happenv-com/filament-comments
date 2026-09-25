<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        // An app that switches to UUIDs: its own comment model on its own table,
        // with UUID keys for the comments and for the commentable models.
        Schema::create('uuid_posts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('uuid_comments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->nullableUuidMorphs('commentable');
            $table->foreignId('author_id')->constrained('users');
            $table->text('content');
            $table->timestamps();
        });
    }
};
