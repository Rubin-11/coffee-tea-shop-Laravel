<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавляет атрибуты кофе, которых не хватало по макету:
     * - вид кофе (арабика/робуста/смесь)
     * - способ обработки (мытая/сухая/натуральная)
     * - детали по арабике и робусте
     * - насыщенность (третья шкала, как кислинка/горчинка)
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('coffee_type')->nullable()->after('acidity_percent');      // Вид кофе: арабика, робуста, смесь
            $table->string('processing')->nullable()->after('coffee_type');            // Способ обработки: мытая, сухая, натуральная
            $table->string('arabica')->nullable()->after('processing');                // Детали по арабике
            $table->string('robusta')->nullable()->after('arabica');                   // Детали по робусте
            $table->integer('saturation_percent')->nullable()->after('robusta');       // Насыщенность (0–10), третья шкала
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['coffee_type', 'processing', 'arabica', 'robusta', 'saturation_percent']);
        });
    }
};
