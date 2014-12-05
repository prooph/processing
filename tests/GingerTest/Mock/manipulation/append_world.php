<?php
/*
 * This file is part of Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 12/5/14 - 10:49 PM
 */
return function (\Ginger\Message\Payload $payload) {
    return \Ginger\Functional\Func::manipulate($payload, function($string) {return $string . ' World'; });
};