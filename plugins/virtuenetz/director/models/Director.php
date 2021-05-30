<?php namespace Virtuenetz\Director\Models;

use Model;

/**
 * Model
 */
class Director extends Model
{
    use \October\Rain\Database\Traits\Validation;
    

    /**
     * @var string The database table used by the model.
     */
    public $table = 'virtuenetz_director_';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
