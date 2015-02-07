<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.10.14 - 16:42
 */

namespace Prooph\Processing\Processor;

use Prooph\EventSourcing\EventStoreIntegration\AggregateTranslator;
use Prooph\EventStore\Aggregate\AggregateRepository;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\MappedSuperclassStreamStrategy;
use Prooph\EventStore\Stream\SingleStreamStrategy;

/**
 * Class ProcessRepository
 *
 * @package Prooph\Processing\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ProcessRepository extends AggregateRepository
{
    /**
     * @param EventStore $eventStore
     */
    public function __construct(
        EventStore $eventStore
    )
    {
        $aggregateType = AggregateType::fromAggregateRootClass('Prooph\Processing\Processor\Process');

        parent::__construct(
            $eventStore,
            new AggregateTranslator(),
            new SingleStreamStrategy($eventStore, 'process_stream'),
            $aggregateType
        );
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
     * @return Process
     */
    public function get(ProcessId $processId)
    {
        return $this->getAggregateRoot($processId->toString());
    }
}
 