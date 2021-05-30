<?php namespace Virtuenetz\Director\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateVirtuenetzDirector extends Migration
{
    public function up()
    {
        Schema::table('virtuenetz_director_', function($table)
        {
            $table->text('avatar')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('virtuenetz_director_', function($table)
        {
            $table->dropColumn('avatar');
        });
    }
}
