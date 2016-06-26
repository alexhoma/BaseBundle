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

namespace Mmoreram\BaseBundle\CompilerPass\Abstracts;

/**
 * Class MappingBagCollection.
 */
class MappingBagCollection
{
    /**
     * @var MappingBag[]
     *
     * Mapping bags
     */
    private $mappingBags = [];

    /**
     * Add mapping bag.
     *
     * @param MappingBag $mappingBag
     */
    public function addMappingBag(MappingBag $mappingBag)
    {
        $this->mappingBags[] = $mappingBag;
    }

    /**
     * Get mapping bags.
     *
     * @return MappingBag[]
     */
    public function all()
    {
        return $this->mappingBags;
    }
}
