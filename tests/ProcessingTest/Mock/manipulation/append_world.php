<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12/5/14 - 10:49 PM
 */
return function (\Prooph\Processing\Message\Payload $payload) {
    return \Prooph\Processing\Functional\Func::manipulate($payload, function($string) {return $string . ' World'; });
};