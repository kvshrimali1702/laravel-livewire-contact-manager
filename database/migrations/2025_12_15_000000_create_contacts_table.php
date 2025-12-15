<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 100);
            // store phone as unsigned big integer (8-14 digits)
            $table->unsignedBigInteger('phone')->comment('Unsigned numeric, 8-14 digits');
            // gender as native DB enum column. values: 1 = male, 2 = female, 3 = prefer not to say (default)
            $table->enum('gender', ['1', '2', '3'])->default('3')->comment('1=male,2=female,3=prefer not to say');
            $table->text('profile_image')->nullable();
            $table->text('additional_file')->nullable();
            $table->timestamps();

            $table->index(['name', 'email', 'phone']);
        });

        // Phone validation will be handled at the application layer (Laravel validation).
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contacts');
    }
};
