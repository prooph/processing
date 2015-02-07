<?php
/*
 * This file is part of the prooph processing framework.
 * (c) prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 28.01.15 - 20:54
 */

namespace Prooph\Processing\Type;

/**
 * Interface Optional
 *
 * Marker interface for types that can have null as value
 *
 * @package Prooph\Processing\Type
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface Optional 
{
    /**
     * @return static
     */
    public static function fromNull();

    /**
     * @return bool
     */
    public function isNull();
}
 