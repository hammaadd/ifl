<?php namespace Virtuenetz\Pdf\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Pdf extends Controller
{
    public $implement = [        'Backend\Behaviors\ListController',        'Backend\Behaviors\FormController'    ];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Virtuenetz.Pdf', 'main-menu-item');
    }
}
