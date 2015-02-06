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
                new \Prooph\EventStore\Stream\StreamName('process_stream'),
                []
            )
        );

        $es->commit();
    }
}
 