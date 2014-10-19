<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.10.14 - 16:42
 */

namespace Ginger\Processor;

use Prooph\EventStore\Aggregate\AggregateRepository;
use Prooph\EventStore\Aggregate\AggregateTranslatorInterface;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\StreamStrategyInterface;

/**
 * Class ProcessRepository
 *
 * @package Ginger\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ProcessRepository extends AggregateRepository
{
    /**
     * @param EventStore $eventStore
     * @param AggregateTranslatorInterface $aggregateTranslator
     * @param StreamStrategyInterface $streamStrategy
     */
    public function __construct(
        EventStore $eventStore,
        AggregateTranslatorInterface $aggregateTranslator,
        StreamStrategyInterface $streamStrategy
    )
    {
        parent::__construct($eventStore, $aggregateTranslator, $streamStrategy);
        $this->aggregateType = new AggregateType('Ginger\Processor\Process');
    }

    /**
     * @param Process $process
     */
    public function add(Process $process)
    {
        $this->addAggregateRoot($process);
    }

    /**
     * @param ProcessId $processId
     */
    public function getProcess(ProcessId $processId)
    {
        $this->getAggregateRoot();
    }
}
 