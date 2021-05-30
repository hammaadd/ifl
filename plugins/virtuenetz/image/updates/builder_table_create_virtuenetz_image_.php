<?php namespace Virtuenetz\Image\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateVirtuenetzImage extends Migration
{
    public function up()
    {
        Schema::create('virtuenetz_image_', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('image_id');
            $table->integer('category_id');
            $table->primary(['image_id','category_id']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('virtuenetz_image_');
    }
}
