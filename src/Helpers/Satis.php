<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 */

namespace SproutModules\Karmabunny\Satis\Helpers;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\Loader\RootPackageLoader;
use Composer\Package\Version\VersionGuesser;
use Composer\Package\Version\VersionParser;
use Composer\Satis\Builder\ArchiveBuilder;
use Composer\Satis\Builder\PackagesBuilder;
use Composer\Satis\Builder\WebBuilder;
use Composer\Satis\PackageSelection\PackageSelection;
use Composer\Util\ProcessExecutor;
use InvalidArgumentException;
use Kohana;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Sprout;
use SproutModules\Karmabunny\Satis\Models\Package;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

/**
 * Wrapper for satis things.
 */
class Satis
{

    const OUTPUT_DIR = STORAGE_PATH . 'satis';


    /**
     * Build a satis config from our repos.
     *
     * @return array
     */
    public static function getConfig(): array
    {
        static $config;

        if ($config === null) {
            $config = [];
            $config['homepage'] = rtrim(Sprout::absRoot(), '/');
            $config['name'] = Kohana::config('satis.name');
            $config['output-dir'] = self::OUTPUT_DIR;
            $config['require-all'] = true;
            $config['archive']['directory'] = 'archive';

            // Inject github token bits.
            $config['config']['github-protocols'] = ['https'];
            $config['config']['github-oauth'] = [
                'github.com' => Kohana::config('satis.github_token'),
            ];

            $config['repositories'] = [];

            $packages = Package::findAll();

            foreach ($packages as $package) {
                $config['repositories'][] = [
                    'type' => 'vcs',
                    'url' => $package->repo_url,
                ];
            }

            // Ensure the folder exists.
            if (!file_exists(self::OUTPUT_DIR)) {
                mkdir(self::OUTPUT_DIR, 0777, true);
            }
        }

        return $config;
    }


    /**
     * Get a composer instance.
     *
     * @param array $config
     * @return Composer
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public static function getComposer(array $config): Composer
    {
        static $composer;

        if ($composer === null) {
            $_SERVER['COMPOSER_HOME'] = STORAGE_PATH . 'composer';

            $composer = Factory::create(new NullIO(), $config, true, true);
        }

        return $composer;
    }


    /**
     * Pinched mostly from BuildCommand.
     *
     * Don't run this without first checking if the package is already being built.
     *
     * @param OutputInterface $output
     * @param string[] $filter repo url filter
     * @return bool
     */
    public static function build(OutputInterface $output, array $filter = []): bool
    {
        static $SKIP_ERRORS = false;

        // Uh. stuff.
        $io = new NullIO();
        $input = new ArrayInput([], new InputDefinition([
            new InputOption('stats', null, InputOption::VALUE_NONE),
        ]));

        $config = self::getConfig();

        $composer = self::getComposer($config);
        $composerConfig = $composer->getConfig();

        // Feed repo manager with satis' repos
        $manager = $composer->getRepositoryManager();
        foreach ($config['repositories'] as $repo) {
            $repo = $manager->createRepository($repo['type'], $repo, $repo['name'] ?? null);
            $manager->addRepository($repo);
        }

        // Make satis' config file pretend it is the root package
        $parser = new VersionParser();

        /**
         * In standalone case, the RootPackageLoader assembles an internal VersionGuesser with a broken ProcessExecutor
         * Workaround by explicitly injecting a ProcessExecutor with enableAsync;
         */
        $process = new ProcessExecutor($io);
        $process->enableAsync();
        $guesser = new VersionGuesser($composerConfig, $process, $parser);
        $loader = new RootPackageLoader($manager, $composerConfig, $parser, $guesser);
        $satisConfigAsRootPackage = $loader->load($config);
        $composer->setPackage($satisConfigAsRootPackage);

        $packageSelection = new PackageSelection($output, self::OUTPUT_DIR, $config, $SKIP_ERRORS);

        if ($filter) {
            $packageSelection->setRepositoriesFilter($filter);
            // $packageSelection->setPackagesFilter($packageFilter);
        }

        $packages = $packageSelection->select($composer, false);

        // Assumes config.archive.directory.
        $downloads = new ArchiveBuilder($output, self::OUTPUT_DIR, $config, $SKIP_ERRORS);
        $downloads->setComposer($composer);
        $downloads->setInput($input);
        $downloads->dump($packages);

        $packages = $packageSelection->clean();

        if ($packageSelection->hasFilterForPackages() || $packageSelection->hasRepositoriesFilter()) {
            // in case of an active filter we need to load the dumped packages.json and merge the
            // updated packages in
            $oldPackages = $packageSelection->load();
            $packages += $oldPackages;
            ksort($packages);
        }

        $packagesBuilder = new PackagesBuilder($output, self::OUTPUT_DIR, $config, $SKIP_ERRORS, false);
        $packagesBuilder->dump($packages);

        $web = new WebBuilder($output, self::OUTPUT_DIR, $config, $SKIP_ERRORS);
        $web->setRootPackage($composer->getPackage());
        $web->dump($packages);

        return true;
    }


    /**
     * Update build times for packages within the filter.
     *
     * If the filter is empty, all packages are updated.
     *
     * @param string[] $filter repo urls
     * @param bool $success
     * @return void
     */
    public static function updatePackages(array $filter, bool $success)
    {
        $data = [
            'last_build_time' => Pdb::now(),
            'build_success' => $success,
        ];

        if (empty($filter)) {
            Pdb::update('packages', $data, []);

        } else {
            Pdb::update('packages', $data, [['repo_url', 'in', $filter]]);
        }
    }
}
