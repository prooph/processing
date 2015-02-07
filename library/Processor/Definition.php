<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.10.14 - 19:34
 */

namespace Prooph\Processing\Processor;

/**
 * Class Definition
 *
 * @package Prooph\Processing\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class Definition 
{
    const DEFAULT_NODE_NAME            = "localhost";
    const SERVICE_ENVIRONMENT          = "processing.env";
    const SERVICE_WORKFLOW_PROCESSOR   = "processing.workflow_processor";
    const SERVICE_PROCESS_FACTORY      = "processing.process_factory";
    const SERVICE_PROCESS_REPOSITORY   = "processing.process_repository";

    const PROCESS_LINEAR_MESSAGING   = "linear_messaging";
    const PROCESS_PARALLEL_FOR_EACH  = "parallel_for_each";
    const PROCESS_PARALLEL_FORK      = "parallel_fork";

    const TASK_COLLECT_DATA          = "collect_data";
    const TASK_PROCESS_DATA          = "process_data";
    const TASK_RUN_SUB_PROCESS       = "run_sub_process";
    const TASK_MANIPULATE_PAYLOAD    = "manipulate_payload";

    const ENV_CONFIG_TYPE_COMMAND_BUS = "command_bus";
    const ENV_CONFIG_TYPE_EVENT_BUS   = "event_bus";

    /**
     * @return array
     */
    public static function getAllProcessTypes()
    {
        return [
            self::PROCESS_LINEAR_MESSAGING,
            self::PROCESS_PARALLEL_FOR_EACH,
            self::PROCESS_PARALLEL_FORK,
        ];
    }

    /**
     * @return array
     */
    public static function getAllTaskTypes()
    {
        return [
            self::TASK_COLLECT_DATA,
            self::TASK_PROCESS_DATA,
            self::TASK_MANIPULATE_PAYLOAD,
            self::TASK_RUN_SUB_PROCESS
        ];
    }
}
 