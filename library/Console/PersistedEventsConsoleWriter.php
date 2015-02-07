<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 21.11.14 - 23:05
 */

namespace Prooph\Processing\Console;

use Prooph\EventStore\PersistenceEvent\PostCommitEvent;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;

/**
 * Class PersistedEventsConsoleWriter
 *
 * @package Prooph\Processing\Console
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class PersistedEventsConsoleWriter extends AbstractListenerAggregate
{
    /**
     * @var ConsoleWriter
     */
    private $consoleWriter;

    /**
     * @param ConsoleWriter $consoleWriter
     */
    public function __construct(ConsoleWriter $consoleWriter)
    {
        $this->consoleWriter = $consoleWriter;
    }

    /**
     * Attach one or more listeners
     *
     * Implementors may add an optional $priority argument; the EventManager
     * implementation will pass this to the aggregate.
     *
     * @param EventManagerInterface $events
     *
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach('commit.post', [$this, 'onPostCommit']);
    }

    public function onPostCommit(PostCommitEvent $event) {
        foreach ($event->getRecordedEvents() as $recordedEvent) {
            $this->consoleWriter->writeNotice(
                sprintf(
                    "Event %s recorded with payload: \n\n%s\n\n",
                    $recordedEvent->eventName()->toString(),
                    json_encode($recordedEvent->payload())
                )
            );
        }
    }
}
 