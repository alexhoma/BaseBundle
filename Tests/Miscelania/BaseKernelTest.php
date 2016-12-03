<?php
/**
 * File header placeholder
 */

namespace Mmoreram\BaseBundle\Tests\Miscelania;

use Mmoreram\BaseBundle\Tests\BaseKernel;
use Mmoreram\BaseBundle\Tests\Bundle\TestBundle;
use Mmoreram\BaseBundle\Tests\Bundle\TestEntityBundle;
use Mmoreram\BaseBundle\Tests\Bundle\TestMappingBundle;
use PHPUnit_Framework_TestCase;

/**
 * Class BaseKernelTest
 */
class BaseKernelTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test unsorted but equals == same kernel
     */
    public function testUnsorted()
    {
        $kernel1 = new BaseKernel([
            TestBundle::class,
            new TestEntityBundle()
        ], [
            'key1' => [
                'key1' => 'value1',
                'key2' => 'value2',
                'keyX' => [
                    ['a1', 'an'],
                    'a2',
                ]
            ],
            'key2' => [
                'key3' => 'value3',
                'key4' => 'value4',
            ]
        ], [
            ['route1', 'key1', 'value1'],
            ['@Bundle1/routing.yml']
        ]);

        $kernel2 = new BaseKernel([
            new TestEntityBundle(),
            new TestBundle(),
        ], [
            'key2' => [
                'key4' => 'value4',
                'key3' => 'value3',
            ],
            'key1' => [
                'key2' => 'value2',
                'keyX' => [
                    'a2',
                    ['an', 'a1'],
                ],
                'key1' => 'value1',
            ]
        ], [
            ['@Bundle1/routing.yml'],
            ['route1', 'key1', 'value1'],
        ]);

        $this->assertEquals(
            $kernel1->getRootDir(),
            $kernel2->getRootDir()
        );
    }

    /**
     * Test same bundles, same configuration, same routes == same kernel
     */
    public function testSameBundlesSameConfigurationSameRoutes()
    {
        $kernel1 = new BaseKernel([
            TestBundle::class,
            new TestEntityBundle()
        ], [
            [
                'key1' => [
                    'key2' => 'value2'
                ]
            ]
        ], [
            ['route1', 'key1', 'value1'],
            ['@Bundle1/routing.yml']
        ]);

        $kernel2 = new BaseKernel([
            new TestBundle(),
            TestEntityBundle::class
        ], [
            [
                'key1' => [
                    'key2' => 'value2'
                ]
            ]
        ], [
            ['route1', 'key1', 'value1'],
            ['@Bundle1/routing.yml']
        ]);

        $this->assertEquals(
            $kernel1->getRootDir(),
            $kernel2->getRootDir()
        );
    }

    /**
     * Test same bundles, same configuration, diff routes != same kernel
     */
    public function testSameBundlesSameConfigurationDiffRoutes()
    {
        $kernel1 = new BaseKernel([
            TestBundle::class,
            new TestEntityBundle()
        ], [
            [
                'key1' => [
                    'key2' => 'value2'
                ]
            ]
        ], [
            ['route1', 'key1', 'value1'],
            ['@Bundle1/routing.yml']
        ]);

        $kernel2 = new BaseKernel([
            new TestBundle(),
            TestEntityBundle::class
        ], [
            [
                'key1' => [
                    'key2' => 'value2'
                ]
            ]
        ], [
            ['route1', 'key1', 'value1']
        ]);

        $this->assertNotEquals(
            $kernel1->getRootDir(),
            $kernel2->getRootDir()
        );
    }

    /**
     * Test same bundles, diff configuration, same routes != same kernel
     */
    public function testSameBundlesDiffConfigurationSameRoutes()
    {
        $kernel1 = new BaseKernel([
            TestBundle::class,
            new TestEntityBundle()
        ], [
            [
                'key1' => [
                    'key2' => 'value2'
                ]
            ]
        ], [
            ['route1', 'key1', 'value1'],
            ['@Bundle1/routing.yml']
        ]);

        $kernel2 = new BaseKernel([
            new TestBundle(),
            TestEntityBundle::class
        ], [
            [
                'key1' => [
                    'key2' => 'value1'
                ]
            ]
        ], [
            ['route1', 'key1', 'value1'],
            ['@Bundle1/routing.yml']
        ]);

        $this->assertNotEquals(
            $kernel1->getRootDir(),
            $kernel2->getRootDir()
        );
    }

    /**
     * Test diff bundles, same configuration, same routes = same kernel
     */
    public function testDiffBundlesSameConfigurationSameRoutes()
    {
        $kernel1 = new BaseKernel([
            TestBundle::class,
            new TestEntityBundle()
        ], [
            [
                'key1' => [
                    'key2' => 'value2'
                ]
            ]
        ], [
            ['route1', 'key1', 'value1'],
            ['@Bundle1/routing.yml']
        ]);

        $kernel2 = new BaseKernel([
            new TestBundle(),
            TestMappingBundle::class
        ], [
            [
                'key1' => [
                    'key2' => 'value2'
                ]
            ]
        ], [
            ['route1', 'key1', 'value1'],
            ['@Bundle1/routing.yml']
        ]);

        $this->assertNotEquals(
            $kernel1->getRootDir(),
            $kernel2->getRootDir()
        );
    }
}