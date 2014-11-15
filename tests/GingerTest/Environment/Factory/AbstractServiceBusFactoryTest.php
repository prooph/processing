<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12.11.14 - 01:05
 */

namespace GingerTest\Environment\Factory;

use GingerTest\TestCase;

class AbstractServiceBusFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_fails_cause_implementation_is_missing()
    {
        $this->assertTrue(false);
    }
}
 