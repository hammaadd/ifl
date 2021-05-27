<?php namespace Virtuenetz\Pdf\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateVirtuenetzPdfPdf2 extends Migration
{
    public function up()
    {
        Schema::table('virtuenetz_pdf_pdf', function($table)
        {
            $table->text('thumb')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('virtuenetz_pdf_pdf', function($table)
        {
            $table->dropColumn('thumb');
        });
    }
}
