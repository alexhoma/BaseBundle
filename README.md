# Base Bundle

[![Build Status](https://travis-ci.org/mmoreram/BaseBundle.png?branch=master)](https://travis-ci.org/mmoreram/BaseBundle)
[![Latest Stable Version](https://poser.pugx.org/mmoreram/base-bundle/v/stable.png)](https://packagist.org/packages/mmoreram/base-bundle)
[![Latest Unstable Version](https://poser.pugx.org/mmoreram/base-bundle/v/unstable.png)](https://packagist.org/packages/mmoreram/base-bundle)

-----

This bundle aims to be the base for all bundles in your Symfony project.

* [Documentation bases](#documentation-bases)
* [Bundle](#bundle)
    * [Bundle dependencies](#bundle-dependencies)
    * [Extension declaration](#extension-declaration)
    * [Compiler Pass declaration](#compiler-pass-declaration)
    * [Commands declaration](#commands-declaration)
* [Extension](#extension)
    * [Extending BaseExtension](#extending-baseextension)
    * [Implementing EntitiesOverridableExtension](#implementing-entitiesoverridableextension)
* [Configuration](#configuration)
    * [Extension alias](#extension-alias)
* [Compiler Pass](#compiler-pass)
    * [Tag Compiler Pass](#tag-compiler-pass)
* [Provider](#provider)
    * [EntityManager Provider](#entitymanager-provider)
    * [Repository Provider](#repository-provider)
* [Functional Tests](#functional-tests)
    * [BaseKernel](#basekernel)
    * [BaseFunctionalTest](#basefuncionaltest)
* [Integration with SimpleDoctrineMapping](#integration-with-simpledoctrinemapping)
    * [Exposing the mapping](#exposing-the-mapping)
    * [Parametrization](#parametrization)
    * [Mapping Compiler Pass](#mapping-compiler-pass)

## Documentation bases

This documentation will always work with an scenario where...
* We have a bundle called AppBundle in our application.
* Inside this bundle, we have an entity called Cart.

Each time an example requires some extra bases, these new bases will be defined
before the example and will extend these ones.

Some libraries will be used as well during the documentation. We encourage you
to check them all in order to increase the quality of your bundles and the way
you know them.

* [Simple Doctrine Mapping](http://github.com/mmoreram/simple-doctrine-mapping)
* [Symfony Bundle Dependencies](http://github.com/mmoreram/symfony-bundle-dependencies)

## Bundle

All bundles in Symfony should start with a PHP class, the Bundle class. This
class should always implement the interface
`Symfony\Component\HttpKernel\Bundle\BundleInterface`, but as you know Symfony
always try to make things easy, you can simply extend the base implementation of
a bundle.

```php
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * My bundle
 */
final class MyBundle extends Bundle
{

}
```

I've personally defended some magic behind some parts of a Framework, but you
should always know what is that magic. Let me explain a little bit your bundle
behavior with this implementation.

### Bundle dependencies

When we talk about dependencies we are used to talking about PHP dependencies.
If we use a file, then this file should be inside our vendor folder, right? That
sounds great, but what about if a bundle needs another bundle to be instanced as
well in our kernel? How Symfony is supposed to handle this need?

Well, the project itself is not providing this feature at the moment, but even
if the theory says that a bundle should never have an external bundle
dependency, the reality is another one, and as far as I know, implementations
cover mostly real problems.

Let's check [Symfony Bundle Dependencies](https://github.com/mmoreram/symfony-bundle-dependencies).
By using this *BaseBundle*, your bundle has automatically dependencies (by
default none).

``` php
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Mmoreram\SymfonyBundleDependencies\DependentBundleInterface;

/**
 * Class AbstractBundle.
 */
abstract class BaseBundle extends Bundle implements DependentBundleInterface
{
    //...

    /**
     * Create instance of current bundle, and return dependent bundle namespaces
     *
     * @return array Bundle instances
     */
    public static function getBundleDependencies(KernelInterface $kernel)
    {
        return [];
    }
}
```

If your bundle has dependencies, feel free to overwrite this method in your
class and add them all. Take a look at the main library documentation to learn
a bit more about how to work with dependencies in your Kernel.

> If you use the *BaseBundle* class, you should need to add this
> *mmoreram/symfony-bundle-dependencies* dependency in your project composer
> file. I strongly recommend to start using this package in all your Symfony
> projects.

### Extension declaration

First of all, your extension will be loaded by magic. What does it mean? Well,
the framework will look for your extension following an standard (the Symfony
one). But what happens if your extension (by error or explicitly) doesn't follow
this standard?

Well, nothing will happen. The framework will still look for a non-existing
class and your desired class will never be instanced. You will spend then some
valuable time finding out where the problem is.

First step to do in your project: avoid this magic and define always your
extension by instancing it in your bundle.

```php
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * My bundle
 */
final class MyBundle extends Bundle
{
    /**
     * Returns the bundle's container extension.
     *
     * @return ExtensionInterface|null The container extension
     *
     * @throws \LogicException
     */
    public function getContainerExtension()
    {
        return new MyExtension($this);
    }
}
```

As you can see, your extensions will require the bundle itself as the first and
only construct parameter. Check the configuration chapter to know why.

Important, if your bundle is not using any extension, use this method as well
with a null return. Otherwise, even if you don't have any class inside the
DependencyInjection folder, your framework will look for something.

```php
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * My bundle
 */
final class MyBundle extends Bundle
{
    /**
     * Returns the bundle's container extension.
     *
     * @return ExtensionInterface|null The container extension
     *
     * @throws \LogicException
     */
    public function getContainerExtension()
    {
        return null;
    }
}
```

### Compiler Pass declaration

One of the most unknown Symfony features is the Compiler Pass. If you want to
know a little bit about what are they and how to use them, take a look at the
fantastic cookbook
[How to work with Compiler Passes in bundles](http://symfony.com/doc/current/cookbook/service_container/compiler_passes.html).

You can instance your Compiler Passes by using the *build* method inside your
bundle as you can see in this example.

``` php
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * My bundle
 */
final class MyBundle extends Bundle
{
    /**
     * Builds bundle.
     *
     * @param ContainerBuilder $container Container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        /**
         * Adds Compiler Passes.
         */
        $container->addCompilerPass(new MyCompilerPass());
    }
}
```

Let's make it easier. Use the *BaseBundle* and you will be able to use the
*getCompilerPasses* method in order to define all your compiler passes.

``` php
use Mmoreram\BaseBundle\BaseBundle;

/**
 * My bundle
 */
final class MyBundle extends BaseBundle
{
    /**
     * Register compiler passes
     *
     * @return CompilerPassInterface[]
     */
    public function getCompilerPasses()
    {
        return [
            new MyCompilerPass(),
        ];
    }
}
```

### Commands declaration

A bundle is also responsible to expose all commands into the main application.
Magic is here as well, so all files ending with *Command* and extending Command
or ContainerAwareCommand inside the main folder Command will be instanced and
loaded each time the bundle is instanced.

Same rationale than the Extension one. You're responsible to know where are your
classes, and the bundle should know it in a very explicit way.

By default, this BaseBundle abstract class removes the Command autoload,
allowing you, in your main Bundle class, to return an array of Command
instances.

``` php
/**
 * Class AbstractBundle.
 */
abstract class BaseBundle extends Bundle
{
    // ...

    /**
     * Register Commands.
     *
     * Disabled as commands are registered as services.
     *
     * @param Application $application An Application instance
     */
    public function registerCommands(Application $application)
    {
    }

    // ...
}
```

I highly recommend you to never use Commands with this kind of magic, as
commands should be, as Controllers and EventListeners, only an entry point to
your domain. You can define your commands as services, injecting there all you
need to make it work.

[How to define commands as services](http://symfony.com/doc/current/cookbook/console/commands_as_services.html)

## Extension

Another pain point each time you need to create a new Bundle. The bundle
Extension is some kind of port between the bundle itself and all the dependency
injection environment. You may be used to seeing files like this.

``` php
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 */
class MyExtension extends Extension
{
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
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $config);

        /**
         * Setting all config elements as DI parameters to inject them
         */
        $container->setParameter(
            'my_parameter',
            $config['my_parameter']
        );

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        /**
         * Loading DI definitions
         */
        $loader->load('services.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');
    }
}
```

### Extending BaseExtension

Difficult to remember, right? Well, that should never be a problem anymore. Take
a look at this implementation using the BaseExtension.

``` php
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 */
class MyExtension extends BaseExtension
{
    /**
     * Returns the recommended alias to use in XML.
     *
     * This alias is also the mandatory prefix to use when using YAML.
     *
     * @return string The alias
     *
     * @api
     */
    public function getAlias()
    {
        return 'app';
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
     * @return ConfigurationInterface Configuration file
     */
    protected function getConfigurationInstance()
    {
        return new Configuration();
    }

    /**
     * Get the Config file location.
     *
     * @return string Config file location
     */
    protected function getConfigFilesLocation()
    {
        return __DIR__ . '/../Resources/config';
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
     *      'file1',
     *      'file2',
     *      ['file3', $config['my_boolean'],
     *      ...
     * );
     *
     * @param array $config Config definitions
     *
     * @return array Config files
     */
    protected function getConfigFiles(array $config)
    {
        return [
            'services',
            'commands',
            'controllers',
        ];
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
     * @return array Parametrization values
     */
    protected function getParametrizationValues(array $config)
    {
        return [
            'my_parameter' => $config['my_parameter'],
        ];
    }
}
```

Maybe the file is larger, and you may notice that there are more lines of code,
but seems to be easier to understand, right? This is what clean code means.
There are only one thing this class will assume. Your services definitions use
*yml* format. This is because is much more clear than XML and PHP, and because
it's easier to interpret by humans. As you can see in the *getConfigFiles*
method, you return the name of the file without the extension, being this always
*yml*.

You can modify the container as well before and after the container is loaded by
using these two methods.

``` php
//...

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

//...
```

### Implementing EntitiesOverridableExtension

One of the coolest features this bundle can bring to your projects is the
extremely easy way you can use Interfaces in your Doctrine declaration instead
of specific implementations.

To understand a little bit more about this topic, take a look at this Symfony
cookbook [How to define Relationships with abstracts classes and interfaces](http://symfony.com/doc/current/cookbook/doctrine/resolve_target_entity.html).

This bundle allows you to define this relation between used interfaces or
abstract classes and their specific implementation. The only thing you have to
do is make your extension an implementation of the interface
EntitiesOverridableExtension. Let's check an example.

``` php
use Mmoreram\BaseBundle\DependencyInjection\BaseExtension;
use Mmoreram\BaseBundle\DependencyInjection\EntitiesOverridableExtension;

/**
 * This is the class that loads and manages your bundle configuration
 */
class MyExtension extends BaseExtension implements EntitiesOverridableExtension
{
    // ...

    /**
     * Get entities overrides.
     *
     * Result must be an array with:
     * index: Original Interface
     * value: Parameter where class is defined.
     *
     * @return array Overrides definition
     */
    public function getEntitiesOverrides()
    {
        return [
            'My\Interface' => 'My\Entity'
        ];
    }
}
```

## Configuration

The way your bundle will request some data from the outside (app) is by using a
configuration file. You can check the official
[Configuration Documentation](http://symfony.com/doc/current/components/config/definition.html)
if you want to know a little bit about this amazing feature.

Let's create a new configuration file for our bundle, and let's discover some
nice features this library will provide you by extending the Configuration file.

``` php
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Mmoreram\BaseBundle\DependencyInjection\BaseConfiguration;

/**
 * Class AppConfiguration.
 */
class AppConfiguration extends BaseConfiguration
{
    /**
     * {@inheritdoc}
     */
    protected function setupTree(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('skills')
                    ->prototype('scalar')
                    ->end()
                ->end()
            ->end();
    }
}
```

Ops! What happens here? Lets check it out step by step.

### Extension Alias

First of all, the configuration file will never define it's own name again. The
configuration file should only define what kind of data should request to the
app but not under what namespace.

So, who should define this namespace? The Extension should as is the one that
really extends the dependency injection environment. In other words, even if for
sure this will never be your scenario, you should be able to share a
configuration file between different extensions.

So... how we can do that? If your configuration files extend this one, then, as
long as you want to initialize it, you will have to define it's namespace. Take
a look at this example. This method is part of your Extension file.

``` php
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
 * @return ConfigurationInterface Configuration file
 */
protected function getConfigurationInstance()
{
    return new Configuration(
        $this->bundle,
        $this->getAlias()
    );
}
```

Your extension defines the alias and passes it to the configuration. But, why
are we passing the bundle as well in the configuration? Check the
[integration with SimpleDoctrineMapping chapter](#integration-with-simpledoctrinemapping).

## Compiler Pass

This library provides you some abstractions for your compiler passes to cover
some specific use cases. Let's check them all.

In case you don't know what a Compiler Pass is yet, I encourage you to start with
the documentation with
[How to work with compiler passes in bundles](http://symfony.com/doc/current/service_container/compiler_passes.html).
The sooner you understand the importance of this class, the sooner you will have
better bundles in your projects.

That simple.

### Tag Compiler Pass

Imagine you want to get all service with an specific tag. Then you want to call
another service's method with each one of these found services. This scenario is
so common, and the Symfony documentation refers to it as
[How to Work with Service Tags](http://symfony.com/doc/current/service_container/tags.html).

Symfony allows you to do this, as the documentation says, in a very simple way,
but this is not simple neither clear enough. Too many lines written once and
again doing non-domain-related stuff.

Let's check the TagCompilerPass, an abstract class that will make this task
as easy as implementing just 3 tiny methods.

``` php
use Mmoreram\BaseBundle\CompilerPass\FeederCompilerPass;

/**
 * Class FeederCompilerPass.
 */
final class FeederCompilerPass extends AbstractTagCompilerPass
{
    /**
     * Get collector service name.
     *
     * @return string Collector service name
     */
    public function getCollectorServiceName()
    {
        return 'my.collector.service';
    }

    /**
     * Get collector method name.
     *
     * @return string Collector method name
     */
    public function getCollectorMethodName()
    {
        return 'addClass';
    }

    /**
     * Get tag name.
     *
     * @return string Tag name
     */
    public function getTagName()
    {
        return 'my.tag';
    }
}
```

In this case, first of all we will check that a service with name
*my.collector.service* exists. If exists, we will look for all services with tag
*my.tag* and we will add them into this collector by using the collector method
*addClass*.

Simple.

## Provider

If you want to create this aliases of repositories and entity managers for your
entities, even if you're not using any Mapping external library, you can do it
by using these two provider services.

For using them, you should add, first of all, a reference of the *providers.yml*
file in your application configuration.

``` yml
imports:
    - { resource: '../../vendor/mmoreram/base-bundle/Resources/config/providers.yml' }
```

### EntityManager Provider

Imagine that you're using Symfony and Doctrine in your project. You have an app,
and for any reason you allowed DoctrineBundle to auto-discover all your
entities by default. If you've created many connections and entity managers in
your project, that example will fit your needs as well.

Let's think what happens in your dependency injection files.

``` yml
services:
    cart_manager:
        class: AppBundle\CartManager
        arguments:
            - "@doctrine.orm.default_entity_manager"
```

Whats happening here? Well your service is now coupled to the entity manager
assigned to manage your entity. If your application has only one single entity
or one service, that should be OK, but what happens if your applications has
many entities and many dependency injection files? What if your entity is no
longer managed by the default entity manager, and now is being managed by
another one called *new_entity_manager*?

Will you change all your *yml* files, one by one, looking for references to the
right entity manager, changing them all? And what happens if a service is using
the same entity manager ofr managing two entities, and one of them is not longer
managed by it?

Think about it.

Well, one of the best options here is changing a little bit the way you think
about the entity managers. Let's assume that each entity should have it's own
entity manager, even if all of them are the same one.

Let's use the same entity example. We have an entity called cart, and is part of
our bundle *AppBundle*. Our *CartManager* service is managing some Cart features
and its entity manager is needed.

First step, creation of a new service pointing our Cart entity manager.

``` yml
services:
    app.entity_manager.cart:
        parent: base.abstract_entity_manager
        arguments:
            - App\Entity\Cart
```

After that, you will be able to use this new service in your other services.
Let's go back to the last example.

``` yml
services:
    cart_manager:
        class: AppBundle\CartManager
        arguments:
            - "@app.entity_manager.cart"
```

If you're using the default Symfony implementation, with the mapping
auto-discover, the result of both implementations will be exactly the same, but
in the future, if you decide to remove the mapping auto-discovering, or you
split your applications in two different connections with several entity
managers, you will only have to focus on your doctrine configuration. After
that, your services will continue using the right entity manager.

> As you could think, using this strategy means that you should never use the
> default entity manager again, and start using one entity manager per entity.
> So, what if your service is managing two entities at the same time? Easy,
> managing *n* entities means coupling to *n* entity managers, even if they are
> the same one. So please, make sure your services are small and do **only**
> what they have to do.

### Repository Provider

Same for repositories. What if you want to inject your entity repository in your
services? Well, you can do it by using the same strategy that you did in entity
managers.

``` yml
services:
    app.entity_repository.cart:
        parent: base.abstract_repository
        arguments:
            - App\Entity\Cart
```

After that, you'll be able to inject this new service in your domain.

``` yml
services:
    cart_manager:
        class: AppBundle\CartManager
        arguments:
            - "@app.entity_repository.cart"
```

## Functional Tests

Some of the issues many projects have when they want to start testing their
application in a functional way is that they don't really know how to manage the
kernel, the differents states this kernel can handle and the whole application.

Well, this is not going to be a problem anymore, at least with this library.
Let's see a functional test and the way you can do it since this moment.

``` php
use Mmoreram\BaseBundle\Tests\BaseFunctionalTest;
use Mmoreram\BaseBundle\Tests\BaseKernel;

/**
 * Class TagCompilerPassTest.
 */
final class TagCompilerPassTest extends BaseFunctionalTest
{
    /**
     * Get kernel.
     *
     * @return KernelInterface
     */
    protected function getKernel()
    {
        return new BaseKernel(
            [
                'Mmoreram\BaseBundle\Tests\Bundle\TestBundle',
            ],
            [
                'services' => [
                    'my.service' => [
                        'class' => 'My\Class',
                        'arguments' => [
                            "a string",
                            "@another.service"
                        ]
                    ]
                ],
                'parameters' => [
                    'locale' => 'es'
                ],
                'framework' => [
                    'form' => true
                ]
            ],
            [
                ['/login', '@MyBundle:User:login', 'user_login'],
                ['/logout', '@MyBundle:User:logout', 'user_logout'],
            ]
        );
    }

    /**
     * Test compiler pass.
     */
    public function testCompilerPass()
    {
        // do your tests
    }
}
```

As you can see, you can do as many things as you need in order to create a
unique scenario.

Let's see step by step what can you do here

### BaseKernel

This library provides you a special kernel for your tests. This kernel is test
ready and allow you to customize as much as you need your application in each
scenario.

This kernel uses the
[Symfony Bundle Dependencies project](http://github.com/mmoreram/symfony-bundle-dependencies)
by default, so make sure you take a look at this project. Using it is not a must
but a great option.

Let's see what do you need to create your own Kernel using the one this library
offers to you.

``` php
new BaseKernel(
    [
        'Mmoreram\BaseBundle\Tests\Bundle\TestBundle',
    ],
    [
        'services' => [
            'my.service' => [
                'class' => 'My\Class',
                'arguments' => [
                    "a string",
                    "@another.service"
                ]
            ]
        ],
        'parameters' => [
            'locale' => 'es'
        ],
        'framework' => [
            'form' => true
        ]
    ],
    [
        ['/login', '@MyBundle:User:login', 'user_login'],
        ['/logout', '@MyBundle:User:logout', 'user_logout'],
        '@MyBundle/Resources/routing.yml',
    ]
);
```

Only three needed parameters for the kernel creation.

* Array of bundle namespaces you need to instance the kernel. If you don't want
to use the Symfony Bundle Dependencies project, make sure you add all of them.
Otherwise, if you use the project, you should only add the bundle/s you want to
test.

* Configuration for the dependency injection component. Use the same format as
you were using *yml* files but in PHP.

* Routes. You can define single routes with an array of three positions. The 
first one is the path, the second one the Controller notation and the last one,
the name of the route. You can define resources with the resource name.

As you can see, you can define a different and unique kernel per each of your
scenarios.

### BaseFunctionalTest

As soon as you have the definition of how you should instance you kernel, we
should create our first functional test. Let's take a look at how we can do
that.

``` php
use Mmoreram\BaseBundle\Tests\BaseFunctionalTest;
use Mmoreram\BaseBundle\Tests\BaseKernel;

/**
 * Class TagCompilerPassTest.
 */
final class TagCompilerPassTest extends BaseFunctionalTest
{
    /**
     * Get kernel.
     *
     * @return KernelInterface
     */
    protected function getKernel()
    {
        return $kernel;
    }

    /**
     * Test compiler pass.
     */
    public function testCompilerPass()
    {
        // do your tests
    }
}
```

In every scenario your kernel will be created and saved locally. You can create
your own kernel or use the *BaseKernel*, in both cases this will work properly,
but take in account that this kernel will be active in the whole scenario.

Inside your tests you can use then these methods:

* *get($serviceName)* if you want to use any container service
* *has($serviceName)* if you want to check if a container service exists
* *getParameter($parameterName)* if you want to use any container parameter

## Integration with SimpleDoctrineMapping

Let's assume that Doctrine is no longer responsible to auto-discover our entity
mapping information.

Before continuing reading, please, take a look at
[SimpleDoctrineMapping](http://github.com/mmoreram/SimpleDoctrineMapping)
repository in order to understand what is the real purpose of this bundle and to
understand properly how this bundle can be really useful in your implementation.

Here some tips.

* Doctrine is not longer responsible for your entity mapping auto-discovering
* You **must** define your own mapping definition
* Each bundle will provide this information to the final app, making each
  package responsible of what is providing

### Exposing the mapping

This chapter is only useful if you want to expose this mapping information to
the final app. By doing it, you provide to each app the possibility of defining
their own mapping data, exposing a default one.

Remember what data we need to define an entity mapping?
* Entity namespace
* Entity mapping file path
* Entity manager name
* Is entity enabled?

The last one makes sense only in that case, so even if you provide an entity
definition by default in your bundle, final user should be able to remove it.

First of all, we need to expose this configuration to the application, and the
way Symfony allow us to do such thing is by using the Configuration file. Of
course, we need to extend the *BaseBundle* configuration file to have some nice
methods available.

The first one allow us to define, one by one, each entity mapping definition.

``` php
/**
 * Add a mapping node into configuration.
 *
 * @param string $nodeName          Node name
 * @param string $entityClass       Class of the entity
 * @param string $entityMappingFile Path of the file where the mapping is defined
 * @param string $entityManager     Name of the entityManager assigned to manage the entity
 * @param bool   $entityEnabled     The entity mapping will be added to the application
 *
 * @return NodeDefinition Node
 */
protected function addCompleteMappingNode(
    string $nodeName,
    string $entityClass,
    string $entityMappingFile,
    string $entityManager,
    bool $entityEnabled
)
```

So, if your Configuration file is something like that...

``` php
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Mmoreram\BaseBundle\DependencyInjection\BaseConfiguration;

/**
 * Class AppConfiguration.
 */
class AppConfiguration extends BaseConfiguration
{
    /**
     * {@inheritdoc}
     */
    protected function setupTree(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('mapping')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->append($this->addCompleteMappingNode(
                            'cart',
                            'AppBundle\Entity\Cart',
                            '@AppBundle/Resources/config/doctrine/Cart.orm.yml',
                            'default',
                            true
                        ))
                    ->end()
                ->end()
            ->end();
    }
}
```

... your application configuration snippet will be defined as is shown here.

``` yml
app:
    mapping:
        cart:
            class: "AppBundle\Entity\Cart"
            mapping_file: "@AppBundle/Resources/config/doctrine/Cart.orm.yml"
            manager: "default"
            enabled: true
```

If you follow the Symfony standards, you can make it much easier by using some
batch methods.

``` php
/**
 * Add a mapping node into configuration.
 *
 * @param string $nodeName      Node name
 * @param string $className     Class name
 * @param string $entityManager Entity Manager
 *
 * @return NodeDefinition Node
 */
protected function addMappingNode(
    string $nodeName,
    string $className,
    string $entityManager = 'default'
)
```

So, if the result of this piece of code will be exactly the same one than the
last one.

``` php
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Mmoreram\BaseBundle\DependencyInjection\BaseConfiguration;

/**
 * Class AppConfiguration.
 */
class AppConfiguration extends BaseConfiguration
{
    /**
     * {@inheritdoc}
     */
    protected function setupTree(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('mapping')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->append($this->addMappingNode(
                            'cart',
                            'Cart'
                        ))
                    ->end()
                ->end()
            -end();
    }
}
```

Finally, if your bundle defines more than one entity, all these entities will
always be managed by the same entity manager (yes, you can take this decision if
all these entities are related by mapping specifications), and all of them
follow the standard defined by Symfony, then you can use this batch method as
well.

``` php
/**
 * Add all mapping nodes.
 *
 * @param ArrayNodeDefinition $rootNode      Root node
 * @param array               $entities      Entities
 * @param string              $entityManager Entity Manager
 */
protected function addMappingNodes(
    ArrayNodeDefinition $rootNode,
    array $entities,
    string $entityManager = 'default'
)
```

So, if your Configuration file is something like that...

``` php
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Mmoreram\BaseBundle\DependencyInjection\BaseConfiguration;

/**
 * Class AppConfiguration.
 */
class AppConfiguration extends BaseConfiguration
{
    /**
     * {@inheritdoc}
     */
    protected function setupTree(ArrayNodeDefinition $rootNode)
    {
        $this->addMappingNodes(
            $rootNode,
            [
                'cart' => 'Cart',
                'order' => 'Order'
            ],
            'default'
        );
    }
}
```

... your application configuration snippet will be defined as is shown here.

``` yml
app:
    mapping:
        cart:
            class: "AppBundle\Entity\Cart"
            mapping_file: "@AppBundle/Resources/config/doctrine/Cart.orm.yml"
            manager: "default"
            enabled: true
        order:
            class: "AppBundle\Entity\Order"
            mapping_file: "@AppBundle/Resources/config/doctrine/Order.orm.yml"
            manager: "default"
            enabled: true
```

As you can see, using this strategy, anyone can change everything, so each
application has the power of easily customize its own domain.

### Parametrization

As soon as we have the right mapping information in our bundle configuration,
and properly processed, we should expose these values into our container in
order to make them accessible by some Compiler Passes.

This step is quite easy, as you only need to use the BaseExtension
*getParametrizationValues* method in order to convert configuration values into
container parameters.

``` php
// ...

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
 * @return array Parametrization values
 */
protected function getParametrizationValues(array $config)
{
    return [
        'app.mapping.cart.class' => $config['mapping']['cart']['class'],
        'app.mapping.cart.mapping_file' => $config['mapping']['cart']['mapping_file'],
        'app.mapping.cart.manager' => $config['mapping']['cart']['manager'],
        'app.mapping.cart.enabled' => $config['mapping']['cart']['enabled'],
    ];
}

// ...
```

That's it.

### Mapping Compiler Pass

So what's next.

Another compiler pass interface this package provides you is the one you should
use in order to add your Doctrine entities definition.

This provided Compiler Pass is just an extra layer of simplicity for your entity
mapping definition. Let's take a look on how you can do it.

``` php
use Mmoreram\BaseBundle\CompilerPass\MappingCompilerPass;
use Mmoreram\BaseBundle\CompilerPass\MappingBag;
use Mmoreram\BaseBundle\CompilerPass\MappingBagCollection;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class MappingCompilerPass
 */
class MappingCompilerPass extends AbstractMappingCompilerPass
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $mappingBagCollection = new MappingBagCollection();

        $mappingBagCollection->addMappingBag(
            new MappingBag(
                'app',
                'cart',
                'doctrine.orm.default_entity_manager',
                'App\Entity\Cart',
                '@AppBundle/Resources/config/doctrine/Cart.orm.yml',
                'true'
            )
        );

        $this->addEntityMappings(
            $container,
            $mappingBagCollection
        );
    }
}
```

As the main library explains, you can use as well parameters instead of using
plain values here, so if you followed first two steps of this chapter, remember
the names of your parameters. Otherwise, continue.

``` php
use Mmoreram\BaseBundle\CompilerPass\MappingCompilerPass;
use Mmoreram\BaseBundle\CompilerPass\MappingBag;
use Mmoreram\BaseBundle\CompilerPass\MappingBagCollection;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class MappingCompilerPass
 */
class MappingCompilerPass extends AbstractMappingCompilerPass
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $mappingBagCollection = new MappingBagCollection();

        $mappingBagCollection->addMappingBag(
            new MappingBag(
                'app',
                'cart',
                'app.mapping.cart.manager',
                'app.mapping.cart.class',
                'app.mapping.cart.mapping_file',
                'app.mapping.cart.enabled'
            )
        );

        $this->addEntityMappings(
            $container,
            $mappingBagCollection
        );
    }
}
```

As you can see, for this mapping definition we're not using simple data anymore,
but value objects. It is important to know how this *MappingBag* object works in
order to understand how you can setup this mapping data for each active entity.

``` php

/**
 * Class MappingBag.
 */
class MappingBag
{
    /**
     * MappingBag constructor.
     *
     * @param string      $bundle      Bundle name
     * @param string      $name        Name of the entity
     * @param string      $manager     Name of the manager who will manage it
     * @param string      $class       Entity namespace
     * @param string      $mappingFile Mapping file
     * @param string|bool $enabled     This entity is enabled
     */
    public function __construct(
        string $bundle,
        string $name,
        string $manager,
        string $class,
        string $mappingFile,
        $enabled
    );
}
```

**Why using this Compiler Pass?** Well, not only because you can perfectly know
how your entities are mapped in your project, but because using this
*addEntityMappings* method you will create as well a service per each entity
repository and entity manager.

For example, in the last piece of code we will be able to use as well these
service in our dependency injection definition.

``` yml
services:

    my_service:
        class: App\MyService
        arguments:
            - ""@app.entity_manager.cart"
            - ""@app.repository.cart"
```

These services are automatically created, and if you change any of the entity
mapping definition, for example, if you use it by passing config parameters
instead of plain values, all definitions will change accordingly after clearing
the cache.
