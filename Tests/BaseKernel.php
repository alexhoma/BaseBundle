<?php

/*
 * This file is part of the BaseBundle for Symfony2.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

namespace Mmoreram\BaseBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Yaml\Yaml;

use Mmoreram\SymfonyBundleDependencies\CachedBundleDependenciesResolver;

/**
 * Class BaseKernel.
 */
final class BaseKernel extends Kernel
{
    use MicroKernelTrait;
    use CachedBundleDependenciesResolver;

    /**
     * @var string[]
     *
     * Bundle array
     */
    private $bundlesToLoad;

    /**
     * @var array[]
     *
     * Configuration
     */
    private $configuration;

    /**
     * @var array[]
     *
     * Routes
     */
    private $routes;

    /**
     * BaseKernel constructor.
     *
     * @param string[] $bundlesToLoad
     * @param array[]  $configuration
     * @param array[]  $routes
     */
    public function __construct(
        array $bundlesToLoad,
        array $configuration = [],
        array $routes = []
    ) {
        $this->bundlesToLoad = $bundlesToLoad;
        $this->routes = $routes;
        $this->configuration = array_merge(
            [
                'parameters' => [
                    'kernel.secret' => '1234',
                ],
            ],
            $configuration
        );

        parent::__construct('test', false);
    }

    /**
     * Returns an array of bundles to register.
     *
     * @return BundleInterface[] An array of bundle instances
     */
    public function registerBundles()
    {
        return $this->getBundleInstances(
            $this,
            $this->bundlesToLoad
        );
    }

    /**
     * Configures the container.
     *
     * You can register extensions:
     *
     * $c->loadFromExtension('framework', array(
     *     'secret' => '%secret%'
     * ));
     *
     * Or services:
     *
     * $c->register('halloween', 'FooBundle\HalloweenProvider');
     *
     * Or parameters:
     *
     * $c->setParameter('halloween', 'lot of fun');
     *
     * @param ContainerBuilder $c
     * @param LoaderInterface  $loader
     */
    protected function configureContainer(
        ContainerBuilder $c,
        LoaderInterface $loader
    ) {
        $yamlContent = Yaml::dump($this->configuration);
        $filePath = tempnam(sys_get_temp_dir(), 'test') . '.yml';
        file_put_contents($filePath, $yamlContent);
        $loader->load($filePath);
        unlink($filePath);
    }

    /**
     * Add or import routes into your application.
     *
     *     $routes->import('config/routing.yml');
     *     $routes->add('/admin', 'AppBundle:Admin:dashboard', 'admin_dashboard');
     *
     * @param RouteCollectionBuilder $routes
     */
    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        foreach ($this->routes as $route) {
            is_array($route)
                ? $routes->add(
                    $route[0],
                    $route[1],
                    $route[2]
                )
                : $routes->import($route);
        }
    }

    /**
     * Gets the application root dir.
     *
     * @return string The application root dir
     */
    public function getRootDir()
    {
        return sys_get_temp_dir() . '/' . 'base-kernel-' . substr(
            hash(
                'md5',
                json_encode([
                    $this->bundlesToLoad,
                    $this->configuration,
                    $this->routes,
                ])
            ),
            0,
            10
        );
    }
}
