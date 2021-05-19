<?php namespace System;

use Db;
use App;
use View;
use Event;
use Config;
use Backend;
use BackendMenu;
use BackendAuth;
use System as SystemHelper;
use System\Models\EventLog;
use System\Models\MailSetting;
use System\Classes\MailManager;
use System\Classes\ErrorHandler;
use System\Classes\CombineAssets;
use System\Classes\UpdateManager;
use System\Classes\MarkupManager;
use System\Classes\PluginManager;
use System\Classes\SettingsManager;
use System\Twig\Engine as TwigEngine;
use System\Twig\Loader as TwigLoader;
use System\Twig\Extension as TwigExtension;
use System\Twig\SecurityPolicy as TwigSecurityPolicy;
use Backend\Classes\WidgetManager;
use October\Rain\Support\ModuleServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\SandboxExtension;

class ServiceProvider extends ModuleServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register('system');

        $this->forgetSingletons();
        $this->registerSingletons();

        /*
         * Register all plugins
         */
        PluginManager::instance()->registerAll();

        $this->registerConsole();
        $this->registerErrorHandler();
        $this->registerLogging();
        $this->registerTwigParser();
        $this->registerMailer();
        $this->registerMarkupTags();
        $this->registerAssetBundles();
        $this->registerValidator();
        $this->registerGlobalViewVars();

        /*
         * Register other module providers
         */
        foreach (SystemHelper::listModules() as $module) {
            if (strtolower(trim($module)) != 'system') {
                App::register('\\' . $module . '\ServiceProvider');
            }
        }

        /*
         * Backend specific
         */
        if (App::runningInBackend()) {
            $this->registerBackendNavigation();
            $this->registerBackendReportWidgets();
            $this->registerBackendPermissions();
            $this->registerBackendSettings();
        }
    }

    /**
     * Bootstrap the module events.
     *
     * @return void
     */
    public function boot()
    {
        // Fix UTF8MB4 support for MariaDB < 10.2 and MySQL < 5.7
        $this->applyDatabaseDefaultStringLength();

        // Fix use of Storage::url() for local disks that haven't been configured correctly
        foreach (Config::get('filesystems.disks') as $key => $config) {
            if ($config['driver'] === 'local' && ends_with($config['root'], '/storage/app') && empty($config['url'])) {
                Config::set("filesystems.disks.$key.url", '/storage/app');
            }
        }

        Paginator::useBootstrapThree();
        Paginator::defaultSimpleView('system::pagination.simple-default');

        /*
         * Boot plugins
         */
        PluginManager::instance()->bootAll();

        parent::boot('system');
        Schema::defaultStringLength(191);
    }

    /**
     * Forget singletons that may linger from previous instances,
     * useful for testing and booting secondary instances
     */
    protected function forgetSingletons()
    {
        PluginManager::forgetInstance();
        UpdateManager::forgetInstance();
    }

    /**
     * Register singletons
     */
    protected function registerSingletons()
    {
        App::singleton('cms.helper', function () {
            return new \Cms\Helpers\Cms;
        });

        App::singleton('system.helper', function () {
            return new \System\Helpers\System;
        });

        App::singleton('backend.helper', function () {
            return new \Backend\Helpers\Backend;
        });

        App::singleton('backend.menu', function () {
            return \Backend\Classes\NavigationManager::instance();
        });

        App::singleton('backend.auth', function () {
            return \Backend\Classes\AuthManager::instance();
        });
    }

    /*
     * Register markup tags
     */
    protected function registerMarkupTags()
    {
        MarkupManager::instance()->registerCallback(function ($manager) {
            $manager->registerFunctions([
                // Escaped Functions
                'input'          => ['input', true],
                'post'           => ['post', true],
                'get'            => ['get', true],
                'form_value'     => [\Form::class, 'value', true],

                // Raw Functions
                'link_to'        => 'link_to',
                'link_to_asset'  => 'link_to_asset',
                'link_to_route'  => 'link_to_route',
                'link_to_action' => 'link_to_action',
                'asset'          => 'asset',
                'action'         => 'action',
                'url'            => 'url',
                'route'          => 'route',
                'secure_url'     => 'secure_url',
                'secure_asset'   => 'secure_asset',
                'html_email'     => [\Html::class, 'email'],

                // Escaped Classes
                'str_*'          => [\Str::class, '*', true],
                'html_*'         => [\Html::class, '*', true],

                // Raw Classes
                'url_*'          => [\Url::class, '*'],
                'form_*'         => [\Form::class, '*'],
                'form_macro'     => [\Form::class, '__call']
            ]);

            $manager->registerFilters([
                // Escaped Classes
                'trans'          => [\Lang::class, 'get', true],
                'transchoice'    => [\Lang::class, 'choice', true],

                // Raw Classes
                'slug'           => [\Str::class, 'slug'],
                'plural'         => [\Str::class, 'plural'],
                'singular'       => [\Str::class, 'singular'],
                'finish'         => [\Str::class, 'finish'],
                'snake'          => [\Str::class, 'snake'],
                'camel'          => [\Str::class, 'camel'],
                'studly'         => [\Str::class, 'studly'],
                'md'             => [\Markdown::class, 'parse'],
                'md_safe'        => [\Markdown::class, 'parseSafe'],
                'time_since'     => [\System\Helpers\DateTime::class, 'timeSince'],
                'time_tense'     => [\System\Helpers\DateTime::class, 'timeTense'],
            ]);
        });
    }

    /**
     * Register command line specifics
     */
    protected function registerConsole()
    {
        /*
         * Allow plugins to use the scheduler
         */
        Event::listen('console.schedule', function ($schedule) {
            // Halt if database has not migrated yet
            if (!SystemHelper::hasDatabase()) {
                return;
            }

            $plugins = PluginManager::instance()->getPlugins();
            foreach ($plugins as $plugin) {
                if (method_exists($plugin, 'registerSchedule')) {
                    $plugin->registerSchedule($schedule);
                }
            }
        });

        /*
         * Add CMS based cache clearing to native command
         */
        Event::listen('cache:cleared', function () {
            \System\Helpers\Cache::clearInternal();
        });

        /*
         * Register console commands
         */
        $this->registerConsoleCommand('october.up', \System\Console\OctoberUp::class);
        $this->registerConsoleCommand('october.down', \System\Console\OctoberDown::class);
        $this->registerConsoleCommand('october.migrate', \System\Console\OctoberMigrate::class);
        $this->registerConsoleCommand('october.update', \System\Console\OctoberUpdate::class);
        $this->registerConsoleCommand('october.util', \System\Console\OctoberUtil::class);
        $this->registerConsoleCommand('october.mirror', \System\Console\OctoberMirror::class);
        $this->registerConsoleCommand('october.fresh', \System\Console\OctoberFresh::class);
        $this->registerConsoleCommand('october.passwd', \System\Console\OctoberPasswd::class);

        $this->registerConsoleCommand('project.set', \System\Console\ProjectSet::class);
        $this->registerConsoleCommand('project.sync', \System\Console\ProjectSync::class);

        $this->registerConsoleCommand('plugin.install', \System\Console\PluginInstall::class);
        $this->registerConsoleCommand('plugin.remove', \System\Console\PluginRemove::class);
        $this->registerConsoleCommand('plugin.disable', \System\Console\PluginDisable::class);
        $this->registerConsoleCommand('plugin.enable', \System\Console\PluginEnable::class);
        $this->registerConsoleCommand('plugin.refresh', \System\Console\PluginRefresh::class);
        $this->registerConsoleCommand('plugin.list', \System\Console\PluginList::class);
        $this->registerConsoleCommand('plugin.check', \System\Console\PluginCheck::class);

        $this->registerConsoleCommand('theme.install', \System\Console\ThemeInstall::class);
        $this->registerConsoleCommand('theme.remove', \System\Console\ThemeRemove::class);
        $this->registerConsoleCommand('theme.list', \System\Console\ThemeList::class);
        $this->registerConsoleCommand('theme.use', \System\Console\ThemeUse::class);
        $this->registerConsoleCommand('theme.sync', \System\Console\ThemeSync::class);
        $this->registerConsoleCommand('theme.check', \System\Console\ThemeCheck::class);
    }

    /*
     * Error handling for uncaught Exceptions
     */
    protected function registerErrorHandler()
    {
        Event::listen('exception.beforeRender', function ($exception, $httpCode, $request) {
            $handler = new ErrorHandler;
            return $handler->handleException($exception);
        });
    }

    /*
     * Write all log events to the database
     */
    protected function registerLogging()
    {
        Event::listen(\Illuminate\Log\Events\MessageLogged::class, function ($event) {
            if (EventLog::useLogging()) {
                EventLog::add($event->message, $event->level);
            }
        });
    }

    /*
     * Register text twig parser
     */
    protected function registerTwigParser()
    {
        /*
         * Register system Twig environment
         */
        App::singleton('twig.environment', function ($app) {
            $twig = new TwigEnvironment(new TwigLoader, ['auto_reload' => true]);
            $twig->addExtension(new TwigExtension);
            $twig->addExtension(new SandboxExtension(new TwigSecurityPolicy, true));
            return $twig;
        });

        /*
         * Register Twig for mailer
         */
        App::singleton('twig.environment.mailer', function ($app) {
            $twig = new TwigEnvironment(new TwigLoader, ['auto_reload' => true]);
            $twig->addExtension(new TwigExtension);
            $twig->addExtension(new SandboxExtension(new TwigSecurityPolicy, true));
            $twig->addTokenParser(new \System\Twig\MailPartialTokenParser);
            return $twig;
        });

        /*
         * Register .htm extension for Twig views
         */
        App::make('view')->addExtension('htm', 'twig', function () {
            return new TwigEngine(App::make('twig.environment'));
        });
    }

    /**
     * Register mail templating and settings override.
     */
    protected function registerMailer()
    {
        /*
         * Register system layouts
         */
        MailManager::instance()->registerCallback(function ($manager) {
            $manager->registerMailLayouts([
                'default' => 'system::mail.layout-default',
                'system' => 'system::mail.layout-system',
            ]);

            $manager->registerMailPartials([
                'header' => 'system::mail.partial-header',
                'footer' => 'system::mail.partial-footer',
                'button' => 'system::mail.partial-button',
                'panel' => 'system::mail.partial-panel',
                'table' => 'system::mail.partial-table',
                'subcopy' => 'system::mail.partial-subcopy',
                'promotion' => 'system::mail.partial-promotion',
            ]);
        });

        /*
         * Override system mailer with mail settings
         */
        Event::listen('mailer.beforeRegister', function () {
            if (MailSetting::isConfigured()) {
                MailSetting::applyConfigValues();
            }
        });

        /*
         * Override standard Mailer content with template
         */
        Event::listen('mailer.beforeAddContent', function ($mailer, $message, $view, $data, $raw, $plain) {
            return !MailManager::instance()->addContentFromEvent($message, $view, $plain, $raw, $data);
        });
    }

    /*
     * Register navigation
     */
    protected function registerBackendNavigation()
    {
        BackendMenu::registerCallback(function ($manager) {
            $manager->registerMenuItems('October.System', [
                'system' => [
                    'label'       => 'system::lang.settings.menu_label',
                    'icon'        => 'icon-cog',
                    'iconSvg'     => 'modules/system/assets/images/cog-icon.svg',
                    'url'         => Backend::url('system/settings'),
                    'permissions' => [],
                    'order'       => 1000
                ]
            ]);
        });

        /*
         * Register the sidebar for the System main menu
         */
        BackendMenu::registerContextSidenavPartial(
            'October.System',
            'system',
            '~/modules/system/partials/_system_sidebar.htm'
        );

        /*
         * Remove the October.System.system main menu item if there is no subpages to display
         */
        Event::listen('backend.menu.extendItems', function ($manager) {
            $systemSettingItems = SettingsManager::instance()->listItems('system');
            $systemMenuItems = $manager->listSideMenuItems('October.System', 'system');

            if (empty($systemSettingItems) && empty($systemMenuItems)) {
                $manager->removeMainMenuItem('October.System', 'system');
            }
        }, -9999);
    }

    /*
     * Register report widgets
     */
    protected function registerBackendReportWidgets()
    {
        WidgetManager::instance()->registerReportWidgets(function ($manager) {
            $manager->registerReportWidget(\System\ReportWidgets\Status::class, [
                'label'   => 'backend::lang.dashboard.status.widget_title_default',
                'context' => 'dashboard'
            ]);
        });
    }

    /*
     * Register permissions
     */
    protected function registerBackendPermissions()
    {
        BackendAuth::registerCallback(function ($manager) {
            $manager->registerPermissions('October.System', [
                'system.manage_updates' => [
                    'label' => 'system::lang.permissions.manage_software_updates',
                    'tab' => 'system::lang.permissions.name'
                ],
                'system.access_logs' => [
                    'label' => 'system::lang.permissions.access_logs',
                    'tab' => 'system::lang.permissions.name'
                ],
                'system.manage_mail_settings' => [
                    'label' => 'system::lang.permissions.manage_mail_settings',
                    'tab' => 'system::lang.permissions.name'
                ],
                'system.manage_mail_templates' => [
                    'label' => 'system::lang.permissions.manage_mail_templates',
                    'tab' => 'system::lang.permissions.name'
                ]
            ]);
        });
    }

    /*
     * Register settings
     */
    protected function registerBackendSettings()
    {
        Event::listen('system.settings.extendItems', function ($manager) {
            \System\Models\LogSetting::filterSettingItems($manager);
        });

        SettingsManager::instance()->registerCallback(function ($manager) {
            $manager->registerSettingItems('October.System', [
                'updates' => [
                    'label'       => 'system::lang.updates.menu_label',
                    'description' => 'system::lang.updates.menu_description',
                    'category'    => SettingsManager::CATEGORY_SYSTEM,
                    'icon'        => 'octo-icon-download',
                    'url'         => Backend::url('system/updates'),
                    'permissions' => ['system.manage_updates'],
                    'order'       => 300
                ],
                'my_updates' => [
                    'label'       => 'system::lang.updates.menu_label',
                    'description' => 'system::lang.updates.menu_description',
                    'category'    => SettingsManager::CATEGORY_MYSETTINGS,
                    'icon'        => 'octo-icon-components',
                    'url'         => Backend::url('system/updates'),
                    'permissions' => ['system.manage_updates'],
                    'order'       => 520,
                    'context'     => 'mysettings'
                ],
                'administrators' => [
                    'label'       => 'backend::lang.user.menu_label',
                    'description' => 'backend::lang.user.menu_description',
                    'category'    => SettingsManager::CATEGORY_SYSTEM,
                    'icon'        => 'octo-icon-users',
                    'url'         => Backend::url('backend/users'),
                    'permissions' => ['backend.manage_users'],
                    'order'       => 400
                ],
                'mail_templates' => [
                    'label'       => 'system::lang.mail_templates.menu_label',
                    'description' => 'system::lang.mail_templates.menu_description',
                    'category'    => SettingsManager::CATEGORY_MAIL,
                    'icon'        => 'octo-icon-mail-messages',
                    'url'         => Backend::url('system/mailtemplates'),
                    'permissions' => ['system.manage_mail_templates'],
                    'order'       => 610
                ],
                'mail_settings' => [
                    'label'       => 'system::lang.mail.menu_label',
                    'description' => 'system::lang.mail.menu_description',
                    'category'    => SettingsManager::CATEGORY_MAIL,
                    'icon'        => 'octo-icon-mail-settings',
                    'class'       => 'System\Models\MailSetting',
                    'permissions' => ['system.manage_mail_settings'],
                    'order'       => 620
                ],
                'mail_brand_settings' => [
                    'label'       => 'system::lang.mail_brand.menu_label',
                    'description' => 'system::lang.mail_brand.menu_description',
                    'category'    => SettingsManager::CATEGORY_MAIL,
                    'icon'        => 'octo-icon-mail-branding',
                    'url'         => Backend::url('system/mailbrandsettings'),
                    'permissions' => ['system.manage_mail_templates'],
                    'order'       => 630
                ],
                'event_logs' => [
                    'label'       => 'system::lang.event_log.menu_label',
                    'description' => 'system::lang.event_log.menu_description',
                    'category'    => SettingsManager::CATEGORY_LOGS,
                    'icon'        => 'octo-icon-text-format-ul',
                    'url'         => Backend::url('system/eventlogs'),
                    'permissions' => ['system.access_logs'],
                    'order'       => 900,
                    'keywords'    => 'error exception'
                ],
                'request_logs' => [
                    'label'       => 'system::lang.request_log.menu_label',
                    'description' => 'system::lang.request_log.menu_description',
                    'category'    => SettingsManager::CATEGORY_LOGS,
                    'icon'        => 'icon-file-o',
                    'url'         => Backend::url('system/requestlogs'),
                    'permissions' => ['system.access_logs'],
                    'order'       => 910,
                    'keywords'    => '404 error'
                ],
                'log_settings' => [
                    'label'       => 'system::lang.log.menu_label',
                    'description' => 'system::lang.log.menu_description',
                    'category'    => SettingsManager::CATEGORY_LOGS,
                    'icon'        => 'octo-icon-log-settings',
                    'class'       => 'System\Models\LogSetting',
                    'permissions' => ['system.manage_logs'],
                    'order'       => 990
                ],
            ]);
        });
    }

    /**
     * Register asset bundles
     */
    protected function registerAssetBundles()
    {
        /*
         * Register asset bundles
         */
        CombineAssets::registerCallback(function ($combiner) {
            $combiner->registerBundle('~/modules/system/assets/js/framework.js');
            $combiner->registerBundle('~/modules/system/assets/js/framework.combined.js');
            $combiner->registerBundle('~/modules/system/assets/less/framework.extras.less');
        });
    }

    /**
     * Extends the validator with custom rules
     */
    protected function registerValidator()
    {
        $this->app->resolving('validator', function ($validator) {
            /*
             * Allowed file extensions, as opposed to mime types.
             * - extensions: png,jpg,txt
             */
            $validator->extend('extensions', function ($attribute, $value, $parameters) {
                $extension = strtolower($value->getClientOriginalExtension());
                return in_array($extension, $parameters);
            });

            $validator->replacer('extensions', function ($message, $attribute, $rule, $parameters) {
                return strtr($message, [':values' => implode(', ', $parameters)]);
            });
        });
    }

    protected function registerGlobalViewVars()
    {
        View::share('appName', Config::get('app.name'));
    }


    /**
     * Allows the database config to specify a max length for VARCHAR
     * Primarily used by MariaDB (<10.2) and MySQL (<5.7)
     * @todo This should be moved to the core library
     */
    protected function applyDatabaseDefaultStringLength()
    {
        if (Db::getDriverName() !== 'mysql') {
            return;
        }

        $defaultStrLen = Db::getConfig('varcharmax');
        if ($defaultStrLen === null) {
            return;
        }

        Schema::defaultStringLength((int) $defaultStrLen);
    }
}
