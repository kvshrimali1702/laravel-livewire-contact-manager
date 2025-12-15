<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('field_name', 100);
            $table->text('field_value');
            $table->boolean('is_searchable')->default(false);
            $table->timestamps();

            // composite index: is_searchable first, then field_value
            $table->index(['is_searchable', 'field_value']);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contact_custom_fields');
    }
};
