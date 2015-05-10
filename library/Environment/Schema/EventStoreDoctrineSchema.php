<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 5/10/15 - 10:59 AM
 */
namespace Prooph\Processing\Environment\Schema;

use Doctrine\DBAL\Schema\Schema;
/**
 * Class EventStoreDoctrineSchema
 *
 * @package Prooph\Processing\Environment\Schema
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class EventStoreDoctrineSchema
{
    public static function createSchema(Schema $schema, $streamName = 'prooph_processing_stream')
    {
        $eventStream = $schema->createTable($streamName);

        $eventStream->addColumn('event_id', 'string', ['length' => 36]);        //UUID of the event
        $eventStream->addColumn('version', 'integer');                          //Version of the aggregate after event was recorded
        $eventStream->addColumn('event_name', 'string', ['length' => 100]);     //Name of the event
        $eventStream->addColumn('event_class', 'string', ['length' => 100]);    //Class of the event
        $eventStream->addColumn('payload', 'text');                             //Event payload
        $eventStream->addColumn('created_at', 'string', ['length' => 100]);     //DateTime ISO8601 when the event was recorded
        $eventStream->addColumn('aggregate_id', 'string', ['length' => 36]);    //UUID of linked aggregate
        $eventStream->addColumn('aggregate_type', 'string', ['length' => 100]); //Class of the linked aggregate
        $eventStream->setPrimaryKey(['event_id']);
        $eventStream->addUniqueIndex(['aggregate_id', 'aggregate_type', 'version'], $streamName . '_m_v_uix'); //Concurrency check on database level
    }

    public static function dropSchema(Schema $schema, $streamName = 'prooph_processing_stream')
    {
        $schema->dropTable($streamName);
    }
} 