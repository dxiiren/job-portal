<?php

use App\Models\Employer;
use App\Models\User;
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
        Schema::create('employers', function (Blueprint $table) {
            $table->id();

            $table->string('company_name');
            $table->foreignIdFor(User::class)->nullable()->constrained()->cascadeOnDelete();

            $table->timestamps();
        });

        Schema::table('offered_jobs', function (Blueprint $table) {
            $table->foreignIdFor(Employer::class)->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offered_jobs', function (Blueprint $table) {
            $table->dropForeign(['employer_id']); // Drop foreign key dahulu
            $table->dropColumn('employer_id'); // Kemudian drop column
        });

        Schema::dropIfExists('employers');
    }
};
