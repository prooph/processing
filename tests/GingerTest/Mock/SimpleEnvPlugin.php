<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.11.14 - 22:07
 */

namespace GingerTest\Mock;

use Ginger\Environment\Environment;
use Ginger\Environment\Plugin;

/**
 * Class SimpleEnvPlugin
 *
 * @package GingerTest\Mock
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class SimpleEnvPlugin implements Plugin
{
    /**
     * @var bool
     */
    private $registered = false;

    /**
     * Return the name of the plugin
     *
     * @return string
     */
    public function getName()
    {
        return get_class($this);
    }

    /**
     * Register the plugin on the workflow environment
     *
     * @param Environment $workflowEnv
     * @return void
     */
    public function registerOn(Environment $workflowEnv)
    {
        $this->registered = true;
    }

    /**
     * @return bool
     */
    public function isRegistered()
    {
        return $this->registered;
    }
}
 