<?php namespace Virtuenetz\Image\Models;

use Model;

/**
 * Model
 */
class Category extends Model
{
    use \October\Rain\Database\Traits\Validation;
    

    /**
     * @var string The database table used by the model.
     */
    public $table = 'virtuenetz_image_categories';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public $belongsToMany = [
        'images' => [
            'Virtuenetz\Image\Models\Image',
            'table' => 'virtuenetz_image_',
            'order' => 'title'

        ]
    ];
}
