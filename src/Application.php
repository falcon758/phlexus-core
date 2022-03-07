<?php

/**
 * This file is part of the Phlexus CMS.
 *
 * (c) Phlexus CMS <cms@phlexus.io>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phlexus;

use InvalidArgumentException;
use Phalcon\Cli\Console as CliApplication;
use Phalcon\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Mvc\Application as MvcApplication;
use Phlexus\Providers\ConfigProvider;
use Phlexus\Providers\CookiesProvider;
use Phlexus\Providers\DatabaseProvider;
use Phlexus\Providers\DispatcherProvider;
use Phlexus\Providers\EscaperProvider;
use Phlexus\Providers\EventsManagerProvider;
use Phlexus\Providers\ModelsManagerProvider;
use Phlexus\Providers\ModelsMetadataProvider;
use Phlexus\Providers\ModulesProvider;
use Phlexus\Providers\ProviderInterface;
use Phlexus\Providers\RegistryProvider;
use Phlexus\Providers\RequestProvider;
use Phlexus\Providers\ResponseProvider;
use Phlexus\Providers\RouterProvider;
use Phlexus\Providers\SecurityProvider;
use Phlexus\Providers\SessionProvider;
use Phlexus\Providers\TagProvider;
use Phlexus\Providers\UrlProvider;
use Phlexus\Providers\ViewProvider;
use Phlexus\Providers\VoltTemplateEngineProvider;
use Phlexus\Providers\FlashProvider;

/**
 * Plexus Application
 *
 * @package Phlexus
 */
class Application
{
    /**
     * Application Dependency Injection Container name
     */
    public const APP_CONTAINER_NAME = 'bootstrap';

    /**
     * Default MVC Mode
     *
     * Default behaviour of WEB Application
     */
    public const MODE_DEFAULT = 'default';

    /**
     * Console Mode
     *
     * Used for tasks and cron jobs
     */
    public const MODE_CLI = 'cli';

    /**
     * MC Mode
     *
     * Model and Controller mode without View.
     * Controllers always returns array which is
     * transformed into JSON and send to client.
     */
    public const MODE_API = 'api';

    /**
     * The Dependency Injector
     *
     * @var DiInterface
     */
    protected DiInterface $di;

    /**
     * The Phalcon Application
     *
     * @var MvcApplication
     */
    protected MvcApplication $app;

    /**
     * Root path of project
     *
     * @var string
     */
    protected string $rootPath;

    /**
     * Application mode
     *
     * Possible values: default and cli
     *
     * @var string
     */
    protected string $mode;

    /**
     * Application Service Providers
     *
     * @var ProviderInterface[]
     */
    protected $providers = [];

    /**
     * Application constructor
     *
     * @param string $rootPath
     * @param string $mode
     * @param array $configs
     * @param array $vendorModules
     */
    public function __construct(
        string $rootPath,
        string $mode = self::MODE_DEFAULT,
        array $configs = [],
        array $vendorModules = []
    ) {
        $this->di = new Di();
        $this->app = $this->createApplication($mode);
        $this->setRootPath($rootPath);
        $this->di->setShared(self::APP_CONTAINER_NAME, $this);

        Di::setDefault($this->di);

        $modules = [
            'vendor' => $vendorModules,
            'custom' => $configs['modules'] ?? [],
        ];

        $events = $configs['events'] ?? [];
        $viewParams = $configs['view'] ?? [];
        $extraProviders = $configs['providers'] ?? [];
        $securityParams = $configs['security'] ?? [];
        $applicationParams = $configs['application'] ?? [];
        $flashParams = $configs['flash'] ?? [];

        // Init Generic Service Providers
        $this->initializeProvider(new RegistryProvider($this->di));
        $this->initializeProvider(new ConfigProvider($this->di), $configs);
        $this->initializeProvider(new EventsManagerProvider($this->di), $events);
        $this->initializeProvider(new ModelsManagerProvider($this->di));
        $this->initializeProvider(new ModelsMetadataProvider($this->di));
        $this->initializeProvider(new ModulesProvider($this->di), $modules);
        $this->initializeProvider(new RequestProvider($this->di));
        $this->initializeProvider(new RouterProvider($this->di));
        $this->initializeProvider(new DispatcherProvider($this->di));
        $this->initializeProvider(new ResponseProvider($this->di));
        $this->initializeProvider(new EscaperProvider($this->di));
        $this->initializeProvider(new TagProvider($this->di));
        $this->initializeProvider(new CookiesProvider($this->di));
        $this->initializeProvider(new SessionProvider($this->di));
        $this->initializeProvider(new SecurityProvider($this->di), $securityParams);
        $this->initializeProvider(new FlashProvider($this->di), $flashParams);

        if (!empty($configs['db'])) {
            $this->initializeProvider(new DatabaseProvider($this->di), $configs['db']);
        }

        // Init Mode Service Providers
        if ($mode == self::MODE_DEFAULT) {
            $this->initializeProvider(new UrlProvider($this->di), $applicationParams);
            $this->initializeProvider(new ViewProvider($this->di), $viewParams);
            $this->initializeProvider(new VoltTemplateEngineProvider($this->di), $viewParams);
        }

        $this->initializeProviders($extraProviders);

        $this->app->setDI($this->di);
    }

    /**
     * Set Root Path of Project
     *
     * @param string $path
     * @return Application
     */
    public function setRootPath(string $path): Application
    {
        $this->rootPath = $path;

        $this->di->offsetSet('rootPath', function () use ($path) {
            return $path;
        });

        return $this;
    }

    /**
     * Get Root Path of Project
     *
     * @return string
     */
    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    /**
     * Run!
     *
     * @return string
     */
    public function run(): string
    {
        return $this->getOutput();
    }

    /**
     * Get Application output
     *
     * @return string
     */
    public function getOutput(): string
    {
        return $this->app->handle($_SERVER['REQUEST_URI'])->getContent();
    }

    /**
     * Get current Application instance
     *
     * @return MvcApplication|CliApplication
     */
    public function getApplication()
    {
        return $this->app;
    }

    /**
     * Get current Application mode
     *
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Initialize the Service in the Dependency Injector Container.
     *
     * @param ProviderInterface $provider
     * @param array $parameters
     * @return $this
     */
    public function initializeProvider(ProviderInterface $provider, array $parameters = []): Application
    {
        $provider->register($parameters);

        $this->providers[$provider->getName()] = $provider;

        return $this;
    }

    /**
     * Initialize the Services in the Dependency Injector Container.
     *
     * @param array $providers
     * @return void
     */
    public function initializeProviders(array $providers = []): void
    {
        foreach ($providers as $provider) {
            if (!class_exists($provider)) {
                continue;
            }

            /** @var ProviderInterface $class */
            $class = new $provider($this->di);
            if (!$class instanceof ProviderInterface) {
                continue;
            }

            $this->initializeProvider($class);
        }
    }

    /**
     * Create Internal Application
     *
     * @param string $mode
     * @return CliApplication|MvcApplication
     * @throws InvalidArgumentException
     */
    protected function createApplication(string $mode)
    {
        $this->mode = $mode;

        switch ($mode) {
            case self::MODE_DEFAULT:
            case self::MODE_API:
                return new MvcApplication($this->di);
            case self::MODE_CLI:
                return new CliApplication($this->di);
            default:
                throw new InvalidArgumentException('Invalid application mode: ' . $mode);
        }
    }
}
