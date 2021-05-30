<?php namespace Virtuenetz\Pdf\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateVirtuenetzPdfCategories extends Migration
{
    public function up()
    {
        Schema::create('virtuenetz_pdf_categories', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name', 191);
            $table->text('description')->nullable();
            $table->text('slug')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('virtuenetz_pdf_categories');
    }
}
