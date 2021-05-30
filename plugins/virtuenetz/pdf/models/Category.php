<?php namespace Virtuenetz\Pdf\Models;

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
    public $table = 'virtuenetz_pdf_categories';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public $belongsToMany = [
        'pdfs' => [
            'Virtuenetz\Pdf\Models\Pdf',
            'table' => 'virtuenetz_pdf_',
            'order' => 'title'

        ]
    ];
}
