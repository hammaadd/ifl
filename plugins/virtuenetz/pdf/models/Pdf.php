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

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
