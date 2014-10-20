<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.10.14 - 19:34
 */

namespace Ginger\Processor;

/**
 * Class Definition
 *
 * @package Ginger\Processor
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class Definition 
{
    const DEFAULT_WORKFLOW_PROCESSOR = "ginger_default_workflow_processor";

    const PROCESS_LINEAR_MESSAGING   = "linear_messaging";

    const TASK_COLLECT_DATA          = "collect_data";
    const TASK_PROCESS_DATA          = "process_data";

    const CONFIG_STOP_ON_ERROR       = "stop_on_error";
}
 