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

declare(strict_types=1);

namespace Mmoreram\BaseBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

use Mmoreram\BaseBundle\Mapping\MappingBagProvider;

/**
 * Class BaseExtension.
 */
abstract class BaseExtension implements
    ExtensionInterface,
    ConfigurationExtensionInterface,
    PrependExtensionInterface
{
    /**
     * @var MappingBagProvider
     *
     * Mapping bag provider
     */
    protected $mappingBagProvider;

    /**
     * BaseExtension constructor.
     *
     * @param MappingBagProvider $mappingBagProvider
     */
    public function __construct(MappingBagProvider $mappingBagProvider = null)
    {
        $this->mappingBagProvider = $mappingBagProvider;
    }

    /**
     * Returns extension configuration.
     *
     * @param array            $config    An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @return ConfigurationInterface|null The configuration or null
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        $configuration = $this->getConfigurationInstance();
        if ($configuration) {
            $container->addObjectResource($configuration);
        }

        return $configuration;
    }

    /**
     * Loads a specific configuration.
     *
     * @param array            $config    An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     *
     * @api
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $container->addObjectResource($this);

        $configuration = $this->getConfiguration($config, $container);
        if ($configuration instanceof ConfigurationInterface) {
            $config = $this->processConfiguration($configuration, $config);
            $this->applyParametrizedValues($config, $container);
        }

        $configFiles = $this->getConfigFiles($config);
        if (!empty($configFiles)) {
            $this->loadFiles($configFiles, $container);
        }

        $this->postLoad($config, $container);
    }

    /**
     * Allow an extension to prepend the extension configurations.
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        $config = [];
        $configuration = $this->getConfigurationInstance();
        if ($configuration instanceof ConfigurationInterface) {
            $config = $container->getExtensionConfig($this->getAlias());

            $config = $this->processConfiguration($configuration, $config);
            $config = $container->getParameterBag()->resolveValue($config);
        }

        $this->applyMappingParametrization($config, $container);
        $this->applyParametrizedValues($config, $container);
        $this->overrideEntities($container);
        $this->preLoad($config, $container);
    }

    /**
     * Returns the namespace to be used for this extension (XML namespace).
     *
     * @return string The XML namespace
     *
     * @api
     */
    public function getNamespace()
    {
        return 'http://example.org/schema/dic/' . $this->getAlias();
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     *
     * @api
     */
    public function getXsdValidationBasePath()
    {
        return '';
    }

    /**
     * Get the Config file location.
     *
     * @return string
     */
    protected function getConfigFilesLocation() : string
    {
        if (!empty($this->getConfigFiles([]))) {
            throw new \RuntimeException(sprintf(
                'Method "getConfigFiles" returns non-empty, but "getConfigFilesLocation" is not implemented in "%s" extension.',
                $this->getAlias()
            ));
        }

        return '';
    }

    /**
     * Config files to load.
     *
     * Each array position can be a simple file name if must be loaded always,
     * or an array, with the filename in the first position, and a boolean in
     * the second one.
     *
     * As a parameter, this method receives all loaded configuration, to allow
     * setting this boolean value from a configuration value.
     *
     * return array(
     *      'file1.yml',
     *      'file2.yml',
     *      ['file3.yml', $config['my_boolean'],
     *      ...
     * );
     *
     * @param array $config Config definitions
     *
     * @return array Config files
     */
    protected function getConfigFiles(array $config) : array
    {
        return [];
    }

    /**
     * Return a new Configuration instance.
     *
     * If object returned by this method is an instance of
     * ConfigurationInterface, extension will use the Configuration to read all
     * bundle config definitions.
     *
     * Also will call getParametrizationValues method to load some config values
     * to internal parameters.
     *
     * @return ConfigurationInterface|null
     */
    protected function getConfigurationInstance() : ? ConfigurationInterface
    {
        return null;
    }

    /**
     * Load Parametrization definition.
     *
     * return array(
     *      'parameter1' => $config['parameter1'],
     *      'parameter2' => $config['parameter2'],
     *      ...
     * );
     *
     * @param array $config Bundles config values
     *
     * @return array
     */
    protected function getParametrizationValues(array $config) : array
    {
        return [];
    }

    /**
     * Hook after pre-pending configuration.
     *
     * @param array            $config    Configuration
     * @param ContainerBuilder $container Container
     */
    protected function preLoad(array $config, ContainerBuilder $container)
    {
        // Implement here your bundle logic
    }

    /**
     * Hook after load the full container.
     *
     * @param array            $config    Configuration
     * @param ContainerBuilder $container Container
     */
    protected function postLoad(array $config, ContainerBuilder $container)
    {
        // Implement here your bundle logic
    }

    /**
     * Process configuration.
     *
     * @param ConfigurationInterface $configuration Configuration object
     * @param array                  $configs       Configuration stack
     *
     * @return array configuration processed
     */
    private function processConfiguration(ConfigurationInterface $configuration, array $configs)
    {
        $processor = new Processor();

        return $processor->processConfiguration($configuration, $configs);
    }

    /**
     * Apply parametrized values.
     *
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function applyParametrizedValues(
        array $config,
        ContainerBuilder $container
    ) {
        $parametrizationValues = $this->getParametrizationValues($config);
        if (is_array($parametrizationValues)) {
            $container
                ->getParameterBag()
                ->add($parametrizationValues);
        }
    }

    /**
     * Apply parametrization for Mapping data.
     * This method is only applied if the extension implements
     * EntitiesMappedExtension.
     *
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function applyMappingParametrization(
        array $config,
        ContainerBuilder $container
    ) {
        if (!$this->mappingBagProvider instanceof MappingBagProvider) {
            return;
        }

        $mappingBagCollection = $this
            ->mappingBagProvider
            ->getMappingBagCollection();

        $mappedParameters = [];
        foreach ($mappingBagCollection->all() as $mappingBag) {
            $entityName = $mappingBag->getEntityName();
            $isOverwritable = $mappingBag->isOverwritable();
            $mappedParameters = array_merge($mappedParameters, [
                $mappingBag->getParamFormat('class') => $isOverwritable
                    ? $config['mapping'][$entityName]['class']
                    : $mappingBag->getEntityNamespace(),
                $mappingBag->getParamFormat('mapping_file') => $isOverwritable
                    ? $config['mapping'][$entityName]['mapping_file']
                    : $mappingBag->getEntityMappingFilePath(),
                $mappingBag->getParamFormat('manager') => $isOverwritable
                    ? $config['mapping'][$entityName]['manager']
                    : $mappingBag->getManagerName(),
                $mappingBag->getParamFormat('enabled') => $isOverwritable
                    ? $config['mapping'][$entityName]['enabled']
                    : $mappingBag->getEntityIsEnabled(),
            ]);
        }

        $container
            ->getParameterBag()
            ->add($mappedParameters);
    }

    /**
     * Load multiple files.
     *
     * @param array            $configFiles Config files
     * @param ContainerBuilder $container   Container
     */
    private function loadFiles(array $configFiles, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator($this->getConfigFilesLocation()));

        foreach ($configFiles as $configFile) {
            if (is_array($configFile)) {
                if (isset($configFile[1]) && false === $configFile[1]) {
                    continue;
                }

                $configFile = $configFile[0];
            }

            $loader->load($configFile . '.yml');
        }
    }

    /**
     * Override Doctrine entities.
     *
     * @param ContainerBuilder $container Container
     */
    private function overrideEntities(ContainerBuilder $container)
    {
        if ($this instanceof EntitiesOverridableExtension) {
            $overrides = $this->getEntitiesOverrides();
            foreach ($overrides as $interface => $override) {
                $overrides[$interface] = $container->getParameter($override);
            }

            $container->prependExtensionConfig('doctrine', [
                'orm' => [
                    'resolve_target_entities' => $overrides,
                ],
            ]);
        }
    }
}
