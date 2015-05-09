<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 16.11.14 - 00:22
 */

namespace Prooph\ProcessingTest\Mock;

use Prooph\Common\Event\ActionEventDispatcher;
use Prooph\Common\Event\ActionEventListenerAggregate;
use Prooph\Common\Event\DetachAggregateHandlers;

/**
 * Class SimpleBusPlugin
 *
 * @package Prooph\ProcessingTest\Mock
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class SimpleBusPlugin implements ActionEventListenerAggregate
{
    use DetachAggregateHandlers;

    private $registered = false;

    /**
     * @var int
     */
    private $attachCount = 0;

    /**
     * Attach one or more listeners
     *
     * Implementors may add an optional $priority argument; the EventManager
     * implementation will pass this to the aggregate.
     *
     * @param ActionEventDispatcher $events
     *
     * @return void
     */
    public function attach(ActionEventDispatcher $events)
    {
        $this->registered = true;
        $this->attachCount++;
    }

    public function isRegistered()
    {
        return $this->registered;
    }

    /**
     * @return int
     */
    public function getAttachCount()
    {
        return $this->attachCount;
    }
}
 