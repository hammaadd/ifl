<?php namespace Editor;

use App;
use Event;
use Backend;
use BackendMenu;
use BackendAuth;
use Backend\Models\UserRole;
use October\Rain\Support\ModuleServiceProvider;
use Illuminate\Support\Facades\Schema;

class ServiceProvider extends ModuleServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register('editor');

        /*
         * Backend specific
         */
        if (App::runningInBackend()) {
            $this->registerBackendNavigation();
            $this->registerBackendPermissions();
        }
    }

    /**
     * Bootstrap the module events.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot('editor');
        Schema::defaultStringLength(191);
    }

    /*
     * Register navigation
     */
    protected function registerBackendNavigation()
    {
        BackendMenu::registerCallback(function ($manager) {
            $manager->registerMenuItems('October.Editor', [
                'editor' => [
                    'label'       => 'editor::lang.editor.menu_label',
                    'icon'        => 'icon-pencil',
                    'iconSvg'     => 'modules/editor/assets/images/editor-icon.svg',
                    'url'         => Backend::url('editor'),
                    'order'       => 90,
                    'permissions' => [
                        'editor.access_editor'
                    ]
                ]
            ]);
        });
    }

    /*
     * Register permissions
     */
    protected function registerBackendPermissions()
    {
        BackendAuth::registerCallback(function ($manager) {
            $manager->registerPermissions('October.Editor', [
                'editor.access_editor' => [
                    'label' => 'editor::lang.permissions.access_editor',
                    'tab' => 'editor::lang.permissions.name',
                    'roles' => UserRole::CODE_DEVELOPER,
                    'order' => 100
                ]
            ]);
        });
    }
}