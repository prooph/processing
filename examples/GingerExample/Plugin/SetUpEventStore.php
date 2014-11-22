<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 21.11.14 - 23:00
 */

namespace GingerExample\Plugin;

use Ginger\Environment\Environment;
use Ginger\Environment\Plugin;

/**
 * Class SetUpEventStore
 *
 * @package GingerExample\Plugin
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class SetUpEventStore implements Plugin
{

    /**
     * Return the name of the plugin
     *
     * @return string
     */
    public function getName()
    {
        return 'in_memory_event_store_set_up';
    }

    /**
     * Register the plugin on the workflow environment
     *
     * @param Environment $workflowEnv
     * @return void
     */
    public function registerOn(Environment $workflowEnv)
    {
        $es = $workflowEnv->getEventStore();

        $es->beginTransaction();

        $es->create(
            new \Prooph\EventStore\Stream\Stream(
                new \Prooph\EventStore\Stream\StreamName('Ginger\Processor\Process'),
                []
            )
        );

        $es->commit();
    }

    /**
     * Return a composer cli require package argument string
     * for the package that includes the supported Ginger\Types of the plugin
     *
     * This can be same package as of the plugin itself but be aware that this package will be installed on every node
     *
     * @example: vendor/package:2.*
     *
     * @return string
     */
    public function getSupportedTypesComposerPackage()
    {
        throw new \BadMethodCallException(__FUNCTION__ . ' is not supported by ' . __CLASS__);
    }

    /**
     * Return an array containing each supported Ginger\Type class as key
     * and all supported workflow messages for that Ginger\Type as value list
     *
     * You can use the short hand of the workflow messages:
     * - collect-data   -> tells the system that the type can be collected by the plugin
     * - data-collected -> tells the system that the plugin wants to be informed when the type was collected
     * - process-data   -> tells the system that the type can be processed by the plugin
     * - data-processed -> tells the system that the plugin wants to be informed when the type was processed
     *
     * @example
     *
     * ['Vendor\Type\User' => ['collect-data', 'data-processed'], 'Vendor\Type\']
     *
     * @return array
     */
    public function getSupportedMessagesByTypeMap()
    {
        return [];
    }
}
 