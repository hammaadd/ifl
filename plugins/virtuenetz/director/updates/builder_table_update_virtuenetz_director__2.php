<?php namespace Virtuenetz\Director\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateVirtuenetzDirector2 extends Migration
{
    public function up()
    {
        Schema::table('virtuenetz_director_', function($table)
        {
            $table->integer('order')->default(5);
        });
    }
    
    public function down()
    {
        Schema::table('virtuenetz_director_', function($table)
        {
            $table->dropColumn('order');
        });
    }
}
