<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.11.14 - 17:44
 */

namespace Prooph\Processing\Environment;

/**
 * Interface Plugin
 *
 * @package Prooph\Processing\Environment
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface Plugin 
{
    /**
     * Return the name of the plugin
     *
     * @return string
     */
    public function getName();

    /**
     * Register the plugin on the workflow environment
     *
     * @param Environment $workflowEnv
     * @return void
     */
    public function registerOn(Environment $workflowEnv);
}
 