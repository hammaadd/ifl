<?php namespace Virtuenetz\Pdf\Models;

use Model;

/**
 * Model
 */
class Pdf extends Model
{
    use \October\Rain\Database\Traits\Validation;
    

    /**
     * @var string The database table used by the model.
     */
    public $table = 'virtuenetz_pdf_pdf';

    public $belongsToMany = [
        'categories' => [
            'Virtuenetz\Pdf\Models\Category',
            'table' => 'virtuenetz_pdf_',
            'order' => 'name'

        ]
    ];

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
