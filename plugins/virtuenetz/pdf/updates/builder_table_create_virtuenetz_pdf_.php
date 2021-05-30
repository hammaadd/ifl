<?php namespace Virtuenetz\Pdf\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateVirtuenetzPdf extends Migration
{
    public function up()
    {
        Schema::create('virtuenetz_pdf_', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('pdf_id');
            $table->integer('category_id');
            $table->primary(['pdf_id','category_id']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('virtuenetz_pdf_');
    }
}
