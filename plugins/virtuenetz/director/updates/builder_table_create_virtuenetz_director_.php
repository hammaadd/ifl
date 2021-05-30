<?php namespace Virtuenetz\Director\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateVirtuenetzDirector extends Migration
{
    public function up()
    {
        Schema::create('virtuenetz_director_', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name', 191);
            $table->string('designation', 191)->nullable();
            $table->text('address')->nullable();
            $table->text('details')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('virtuenetz_director_');
    }
}
