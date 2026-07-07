<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mail_events')) {
            Schema::create('mail_events', function (Blueprint $table) {
                $table->id();
                $table->string('event')->index();       // accepted|delivered|opened|clicked|failed|complained|unsubscribed
                $table->string('severity')->nullable();  // temporary|permanent (para failed)
                $table->string('recipient')->nullable();
                $table->string('reason')->nullable();
                $table->timestamp('occurred_at')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index(['event', 'occurred_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_events');
    }
};
