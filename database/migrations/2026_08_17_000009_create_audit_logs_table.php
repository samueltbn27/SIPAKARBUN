<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit Log — catatan event penting aplikasi (kontrak §24).
 *
 * Event yang dicatat (event_type kontrak):
 *   DIAGNOSIS_CREATED
 *   REQUEST_SUBMITTED
 *   REQUEST_REVIEWED
 *   REQUEST_ACCEPTED
 *   REQUEST_REJECTED
 *   POPT_ASSIGNED
 *   HANDLING_STATUS_CHANGED
 *
 * Kolom serbaguna:
 *   - event_type      : kode event (lihat daftar di atas).
 *   - actor_user_id   : user pelaku aksi (nullable utk event sistem).
 *   - entity_type/id  : objek yang terkena dampak (mis. "permohonan", 12).
 *   - old_value/new   : ringkasan nilai sebelum/sesudah (JSON).
 *   - metadata        : JSON tambahan bebas.
 *   - timestamp       : waktu event.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 80);
            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('entity_type', 80)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('timestamp')->useCurrent();

            $table->index(['event_type', 'timestamp']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('actor_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
