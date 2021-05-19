<?php namespace System\Console;

use Lang;
use File;
use Config;
use System;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use System\Classes\UpdateManager;
use System\Classes\CombineAssets;
use System\Models\File as FileModel;
use Exception;

/**
 * Console command for other utility commands.
 *
 * This provides functionality that doesn't quite deserve its own dedicated
 * console class. It is used mostly developer tools and maintenance tasks.
 *
 * Currently supported commands:
 *
 * - purge thumbs: Deletes all thumbnail files in the uploads directory.
 * - purge orphans: Deletes files in "system_files" that do not belong to any other model.
 * - purge uploads: Deletes files in the uploads directory that do not exist in the "system_files" table.
 * - git pull: Perform "git pull" on all plugins and themes.
 * - compile assets: Compile registered Language, LESS and JS files.
 * - compile js: Compile registered JS files only.
 * - compile less: Compile registered LESS files only.
 * - compile scss: Compile registered SCSS files only.
 * - compile lang: Compile registered Language files only.
 * - set build: Pull the latest stable build number from the update gateway and set it as the current build number.
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class OctoberUtil extends Command
{
    use \Illuminate\Console\ConfirmableTrait;

    /**
     * The console command name.
     */
    protected $name = 'october:util';

    /**
     * The console command description.
     */
    protected $description = 'Utility commands for October';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $command = implode(' ', (array) $this->argument('name'));
        $method = 'util'.studly_case($command);

        $methods = preg_grep('/^util/', get_class_methods(get_called_class()));
        $list = array_map(function ($item) {
            return "october:".snake_case($item, " ");
        }, $methods);

        if (!$this->argument('name')) {
            $message = 'There are no commands defined in the "util" namespace.';
            if (count($list) === 1) {
                $message .= "\n\nDid you mean this?\n    ";
            }
            else {
                $message .= "\n\nDid you mean one of these?\n    ";
            }

            $message .= implode("\n    ", $list);
            throw new \InvalidArgumentException($message);
        }

        if (!method_exists($this, $method)) {
            $this->error(sprintf('Utility command "%s" does not exist!', $command));
            return;
        }

        $this->$method();
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::IS_ARRAY, 'The utility command to perform, For more info "http://octobercms.com/docs/console/commands#october-util-command".'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['debug', null, InputOption::VALUE_NONE, 'Run the operation in debug / development mode.'],
            ['projectId', null, InputOption::VALUE_REQUIRED, 'Specify a projectId for set project'],
        ];
    }

    //
    // Utilties
    //

    protected function utilSetBuild()
    {
        $this->output->newLine();

        /*
         * Skip setting the build number if no database is detected to set it within
         */
        if (!System::hasDatabase()) {
            $this->comment('No database detected - skipping setting the build number.');
            return;
        }

        try {
            $build = UpdateManager::instance()->setBuildNumberManually();
            $this->comment('* You are using October CMS version: v' . System::VERSION . '.' . $build);
        } catch (Exception $ex) {
            $this->comment('*** Unable to set build: [' . $ex->getMessage() . ']');
        }
    }

    protected function utilCompileJs()
    {
        $this->utilCompileAssets('js');
    }

    protected function utilCompileLess()
    {
        $this->utilCompileAssets('less');
    }

    protected function utilCompileScss()
    {
        $this->utilCompileAssets('scss');
    }

    protected function utilCompileAssets($type = null)
    {
        $this->comment('Compiling registered asset bundles...');

        Config::set('cms.enable_asset_minify', !$this->option('debug'));
        $combiner = CombineAssets::instance();
        $bundles = $combiner->getBundles($type);

        if (!$bundles) {
            $this->comment('Nothing to compile!');
            return;
        }

        if ($type) {
            $bundles = [$bundles];
        }

        foreach ($bundles as $bundleType) {
            foreach ($bundleType as $destination => $assets) {
                $destination = File::symbolizePath($destination);
                $publicDest = File::localToPublic(realpath(dirname($destination))) . '/' . basename($destination);

                $combiner->combineToFile($assets, $destination);
                $shortAssets = implode(', ', array_map('basename', $assets));
                $this->comment($shortAssets);
                $this->comment(sprintf(' -> %s', $publicDest));
            }
        }

        if ($type === null) {
            $this->utilCompileLang();
        }
    }

    protected function utilCompileLang()
    {
        if (!$locales = Lang::get('system::lang.locale')) {
            return;
        }

        $this->comment('Compiling client-side language files...');

        $locales = array_keys($locales);
        $stub = base_path() . '/modules/system/assets/js/lang/lang.stub';

        foreach ($locales as $locale) {
            /*
             * Generate messages
             */
            $fallbackPath = base_path() . '/modules/system/lang/en/client.php';
            $srcPath = base_path() . '/modules/system/lang/'.$locale.'/client.php';

            $messages = require $fallbackPath;
            if (File::isFile($srcPath) && $fallbackPath != $srcPath) {
                $messages = array_replace_recursive($messages, require $srcPath);
            }

            /*
             * Load possible replacements from /lang
             */
            $overridePath = base_path() . '/lang/'.$locale.'/system/client.php';
            if (File::isFile($overridePath)) {
                $messages = array_replace_recursive($messages, require $overridePath);
            }

            /*
             * Compile from stub and save file
             */
            $destPath = base_path() . '/modules/system/assets/js/lang/lang.'.$locale.'.js';

            $contents = str_replace(
                ['{{locale}}', '{{messages}}'],
                [$locale, json_encode($messages)],
                File::get($stub)
            );

            /*
             * Include the moment localization data
             */
            $momentPath = base_path() . '/modules/system/assets/ui/vendor/moment/locale/'.$locale.'.js';
            if (File::exists($momentPath)) {
                $contents .= PHP_EOL.PHP_EOL.File::get($momentPath).PHP_EOL;
            }

            File::put($destPath, $contents);

            /*
             * Output notes
             */
            $publicDest = File::localToPublic(realpath(dirname($destPath))) . '/' . basename($destPath);

            $this->comment($locale.'/'.basename($srcPath));
            $this->comment(sprintf(' -> %s', $publicDest));
        }
    }

    protected function utilPurgeThumbs()
    {
        if (!$this->confirmToProceed('This will PERMANENTLY DELETE all thumbs in the uploads directory.')) {
            return;
        }

        $totalCount = 0;
        $uploadsPath = Config::get('filesystems.disks.local.root', storage_path('app'));
        $uploadsPath .= '/uploads';

        /*
         * Recursive function to scan the directory for files beginning
         * with "thumb_" and repeat itself on directories.
         */
        $purgeFunc = function ($targetDir) use (&$purgeFunc, &$totalCount) {
            if ($files = File::glob($targetDir.'/thumb_*')) {
                foreach ($files as $file) {
                    $this->info('Purged: '. basename($file));
                    $totalCount++;
                    @unlink($file);
                }
            }

            if ($dirs = File::directories($targetDir)) {
                foreach ($dirs as $dir) {
                    $purgeFunc($dir);
                }
            }
        };

        $purgeFunc($uploadsPath);

        if ($totalCount > 0) {
            $this->comment(sprintf('Successfully deleted %s thumbs', $totalCount));
        }
        else {
            $this->comment('No thumbs found to delete');
        }
    }

    /**
     * utilPurgeUploads deletes files in the uploads directory that do not exist in the "system_files" table
     */
    protected function utilPurgeUploads()
    {
        if (!$this->confirm('This will PERMANENTLY DELETE files in the uploads directory that do not exist in the "system_files" table.')) {
            return;
        }

        $uploadsDisk = Config::get('cms.storage.uploads.disk', 'local');
        if ($uploadsDisk !== 'local') {
            $this->error('Purging uploads is only supported on the local disk');
            return;
        }

        $purgeFunc = function($localPath) {
            $chunks = collect(File::allFiles($localPath))->chunk(50);
            $filesToDelete = [];

            foreach ($chunks as $chunk) {
                $filenames = [];
                foreach ($chunk as $file) {
                    $filenames[] = $file->getFileName();
                }

                $foundModels = FileModel::whereIn('disk_name', $filenames)->lists('disk_name');

                foreach ($chunk as $file) {
                    if (!in_array($file->getFileName(), $foundModels)) {
                        $filesToDelete[$file->getFileName()] = $file->getPath();
                    }
                }
            }

            return $filesToDelete;
        };

        $localPath = Config::get('filesystems.disks.local.root', storage_path('app'))
            . '/'
            . Config::get('system.storage.uploads.folder');

        // Protected directory
        $this->comment('Scanning directory: '.$localPath.'/protected');
        $filesToDelete = $purgeFunc($localPath.'/protected');

        if (count($filesToDelete)) {
            $this->comment('Found the following files to delete');
            $this->comment(implode(', ', array_keys($filesToDelete)));
            if ($this->confirm('Please confirm file destruction.')) {
                foreach ($filesToDelete as $path) {
                    File::delete($path);
                }
            }
        }
        else {
            $this->comment('No files found to purge.');
        }

        // Public directory
        $this->comment('Scanning directory: '.$localPath.'/public');
        $filesToDelete = $purgeFunc($localPath.'/public');

        if (count($filesToDelete)) {
            $this->comment('Found the following files to delete');
            $this->comment(implode(', ', array_keys($filesToDelete)));
            if ($this->confirm('Please confirm file destruction.')) {
                foreach ($filesToDelete as $path) {
                    File::delete($path);
                }
            }
        }
        else {
            $this->comment('No files found to purge.');
        }
    }

    /**
     * utilPurgeOrphans deletes files in "system_files" that do not belong to any other model
     */
    protected function utilPurgeOrphans()
    {
        if (!$this->confirmToProceed('This will PERMANENTLY DELETE files in "system_files" that do not belong to any other model.')) {
            return;
        }

        $orphanedFiles = 0;

        // Locate orphans
        $files = FileModel::whereNull('attachment_id')->get();

        foreach ($files as $file) {
            $file->delete();
            $orphanedFiles += 1;
        }

        if ($orphanedFiles > 0) {
            $this->comment(sprintf('Successfully deleted %d orphaned record(s).', $orphanedFiles));
        }
        else {
            $this->comment('No records to purge.');
        }
    }

    /**
     * utilGitPull requires the git binary to be installed
     */
    protected function utilGitPull()
    {
        foreach (File::directories(plugins_path()) as $authorDir) {
            foreach (File::directories($authorDir) as $pluginDir) {
                if (!File::isDirectory($pluginDir.'/.git')) {
                    continue;
                }

                $exec = 'cd ' . $pluginDir . ' && ';
                $exec .= 'git pull 2>&1';
                echo 'Updating plugin: '. basename(dirname($pluginDir)) .'.'. basename($pluginDir) . PHP_EOL;
                echo shell_exec($exec);
            }
        }

        foreach (File::directories(themes_path()) as $themeDir) {
            if (!File::isDirectory($themeDir.'/.git')) {
                continue;
            }

            $exec = 'cd ' . $themeDir . ' && ';
            $exec .= 'git pull 2>&1';
            echo 'Updating theme: '. basename($themeDir) . PHP_EOL;
            echo shell_exec($exec);
        }
    }
}
