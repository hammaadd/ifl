<?php namespace Virtuenetz\Image\Models;

use Model;

/**
 * Model
 */
class Image extends Model
{
    use \October\Rain\Database\Traits\Validation;
    

    /**
     * @var string The database table used by the model.
     */
    public $table = 'virtuenetz_image_image';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public $belongsToMany = [
        'categories' => [
            'Virtuenetz\Image\Models\Category',
            'table' => 'virtuenetz_image_',
            'order' => 'name'

        ]
    ];
}
