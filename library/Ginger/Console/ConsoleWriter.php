<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.11.14 - 23:52
 */

namespace Ginger\Console;
use Zend\Console\Adapter\AdapterInterface;
use Zend\Console\ColorInterface;
use ZF\Console\Route;

/**
 * Class ConsoleWriter
 *
 * @package Ginger\Console
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ConsoleWriter 
{
    /**
     * @var AdapterInterface
     */
    private $console;

    /**
     * @var bool
     */
    private $verbose = false;

    /**
     * @var bool
     */
    private $quit = false;

    /**
     * @param AdapterInterface $console
     * @param bool $verbose
     * @param bool $quit
     */
    public function __construct(AdapterInterface $console, $verbose = false, $quit = false)
    {
        $this->console = $console;
        $this->verbose = $verbose;
        $this->quit    = $quit;
    }

    public function deriveVerbosityLevelFrom(Route $route)
    {
        $this->verbose = (bool)$route->getMatchedParam('verbose', false);
        $this->quit    = (bool)$route->getMatchedParam('quit', false);
    }

    public function writeInfo($msg)
    {
        if ($this->quit) return;

        $this->console->writeLine($this->getTimeString() . $msg);
    }

    public function writeSuccess($msg)
    {
        if ($this->quit) return;

        $this->console->writeLine($this->getTimeString() . $msg, ColorInterface::LIGHT_GREEN);
    }

    public function writeError($msg)
    {
        if ($this->quit) return;

        $this->console->writeLine($this->getTimeString() . $msg, ColorInterface::RED);
    }

    public function writeException(\Exception $ex, $previous = false)
    {
        if ($this->quit) return;

        if ($previous) {
            $this->writeError('Previous Exception: ' . $ex->getMessage());
        } else {
            $this->writeError('Exception: ' . $ex->getMessage());
        }

        if ($this->verbose) {
            $this->writeError($ex->getTraceAsString());

            if ($ex->getPrevious() instanceof \Exception) {
                $this->writeException($ex->getPrevious(), true);
            }
        }
    }

    public function writeNotice($msg)
    {
        if ($this->quit || ! $this->verbose) return;

        $this->console->writeLine($msg);
    }

    private function getTimeString()
    {
        return date('Y-m-d H:i:s') . ': ';
    }
}
 