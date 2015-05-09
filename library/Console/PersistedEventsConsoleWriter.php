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

use Prooph\Common\Event\ActionEventDispatcher;
use Prooph\Common\Event\ActionEventListenerAggregate;
use Prooph\Common\Event\DetachAggregateHandlers;
use Prooph\EventStore\PersistenceEvent\PostCommitEvent;

/**
 * Class PersistedEventsConsoleWriter
 *
 * @package Prooph\Processing\Console
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class PersistedEventsConsoleWriter implements ActionEventListenerAggregate
{
    use DetachAggregateHandlers;

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
     * @param ActionEventDispatcher $events
     *
     * @return void
     */
    public function attach(ActionEventDispatcher $events)
    {
        $this->trackHandler($events->attachListener('commit.post', [$this, 'onPostCommit']));
    }

    public function onPostCommit(PostCommitEvent $event) {
        foreach ($event->getRecordedEvents() as $recordedEvent) {
            $this->consoleWriter->writeNotice(
                sprintf(
                    "Event %s recorded with payload: \n\n%s\n\n",
                    $recordedEvent->messageName(),
                    json_encode($recordedEvent->payload())
                )
            );
        }
    }
}
 