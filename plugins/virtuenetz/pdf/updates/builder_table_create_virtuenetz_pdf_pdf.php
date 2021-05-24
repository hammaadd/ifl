<?php namespace Virtuenetz\Pdf\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateVirtuenetzPdfPdf extends Migration
{
    public function up()
    {
        Schema::create('virtuenetz_pdf_pdf', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('virtuenetz_pdf_pdf');
    }
}
