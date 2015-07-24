<?php

/**
 * Copyright (c) 2011-present MediaSift Ltd
 * Copyright (c) 2015-present Ganbaro Digital Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the names of the copyright holders nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Libraries
 * @package   CommandRunner/ProcessRunners
 * @author    Stuart Herbert <stuherbert@ganbarodigital.com>
 * @copyright 2011-present MediaSift Ltd www.datasift.com
 * @copyright 2015-present Ganbaro Digital Ltd www.ganbarodigital.com
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://code.ganbarodigital.com/php-command-runner
 */

namespace GanbaroDigital\CommandRunner\ProcessRunners;

use GanbaroDigital\CommandRunner\Exceptions\E4xx_UnsupportedType;
use GanbaroDigital\CommandRunner\Exceptions\E5xx_CommandFailedToStart;
use GanbaroDigital\CommandRunner\Values\CommandResult;
use GanbaroDigital\CommandRunner\ValueBuilders\BuildEscapedCommand;
use GanbaroDigital\DateTime\Requirements\RequireTimeoutOrNull;
use GanbaroDigital\DateTime\ValueBuilders\BuildTimeoutAsFloat;
use GanbaroDigital\Reflection\Requirements\RequireTraversable;

/**
 * based on the CommandRunner code from Storyplayer
 */
class PopenProcessRunner
{
    /**
     * run a CLI command using the popen() interface
     *
     * @param  array|Traversable $command
     *         the command to execute
     * @param  int|null $timeout
     *         how long before we force the command to close?
     * @return CommandResult
     *         the result of executing the command
     */
    public static function run($command, $timeout = null)
    {
        // robustness
        RequireTraversable::checkMixed($command, E4xx_UnsupportedType::class);
        RequireTimeoutOrNull::check($timeout);

        return self::runCommand($command, $timeout);
    }

    /**
     * run a CLI command using the popen() interface
     *
     * @param  array|Traversable $command
     *         the command to execute
     * @param  int|null $timeout
     *         how long before we force the command to close?
     * @return CommandResult
     *         the result of executing the command
     */
    private static function runCommand($command, $timeout)
    {
        // when the command needs to stop
        $timeoutToUse = self::getTimeoutToUse($timeout);

        // start the process
        list($process, $pipes) = self::startProcess($command);

        // drain the pipes
        try {
            $output = self::drainPipes($pipes, $timeoutToUse);
        }
        finally {
            // at this point, our pipes have been closed
            // we can assume that the child process has finished
            $retval = self::stopProcess($process, $pipes);
        }

        // all done
        return new CommandResult($command, $retval, $output);
    }

    /**
     * convert the timeout into something we can use
     *
     * @param  mixed $timeout
     *         the timeout value to convert
     * @return array
     *         0 - the overall timeout
     *         1 - tv_sec param to select()
     *         2 - tv_usec param to select()
     */
    private static function getTimeoutToUse($timeout = null)
    {
        $tAsF = BuildTimeoutAsFloat::from($timeout);
        if ($tAsF >= 1.0) {
            return [ $tAsF, 1, 0 ];
        }

        return [ max($tAsF, 0.2), 0, max(intval($tAsF * 1000000), 200000) ];
    }

    /**
     * start the command
     *
     * @param  array|Traversable $command
     *         the command to execute
     * @return array
     *         0 - the process handle
     *         1 - the array of pipes to interact with the process
     */
    private static function startProcess($command)
    {
        // create the command to execute
        $cmdToExecute = BuildEscapedCommand::from($command);

        // how we will talk to the process
        $pipes = [];

        // start the process
        $process = proc_open($cmdToExecute, self::buildPipesSpec(), $pipes);

        // was there a problem?
        //
        // NOTE: this only occurs when something like a fork() failure
        // happens, which makes it very difficult to test for in a
        // unit test
        if (!$process) {
            throw new E5xx_CommandFailedToStart($cmd);
        }

        // we do not want to block whilst reading from the child process's
        // stdout and stderr
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);

        return [$process, $pipes];
    }

    /**
     * stop a previously started process
     *
     * @param  resource $process
     *         the process handle
     * @param  array &$pipes
     *         the pipes that are open to the process
     * @return int
     *         the return code from the command
     */
    private static function stopProcess($process, &$pipes)
    {
        // pipes must be closed first to avoid
        // a deadlock, according to the PHP Manual
        fclose($pipes[1]);
        fclose($pipes[2]);

        // close and get the return code
        return proc_close($process);
    }

    /**
     * create a description of the pipes that we want for talking to
     * the process
     *
     * @return array
     */
    private static function buildPipesSpec()
    {
        return [
            [ 'file', 'php://stdin', 'r' ],
            [ 'pipe', 'w' ],
            [ 'pipe', 'w' ]
        ];
    }

    /**
     * capture the output from the process
     *
     * @param  array &$pipes
     *         the pipes that are connected to the process
     * @return string
     *         the combined output from stdout and stderr
     */
    private static function drainPipes(&$pipes)
    {
        // the output from the command will be captured here
        $output = '';

        // at this point, our command may be running ...
        // OR our command may have failed with an error
        //
        // best thing to do is to keep reading from our pipes until
        // the pipes no longer exist
        while (!feof($pipes[1]) || !feof($pipes[2]))
        {
            // block until there is something to read, or until the
            // timeout has happened
            //
            // this makes sure that we do not burn CPU for the sake of it
            $readable = [ $pipes[1], $pipes[2] ];
            $writeable = $except = [];
            stream_select($readable, $writeable, $except, 1);

            // check all the streams for output
            if ($line = fgets($pipes[1])) {
                $output = $output . $line;
            }
            if ($line = fgets($pipes[2])) {
                $output = $output . $line;
            }
        }

        // all done
        return $output;
    }

    /**
     * run a CLI command using the popen() interface
     *
     * @param  array|Traversable $command
     *         the command to execute
     * @param  int|null $timeout
     *         how long before we force the command to close?
     * @return CommandResult
     *         the result of executing the command
     */
    public function __invoke($command, $timeout = null)
    {
        return self::runCommand($command, $timeout);
    }
}