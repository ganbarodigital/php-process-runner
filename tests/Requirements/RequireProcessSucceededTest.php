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
 * @package   ProcessRunner/Requirements
 * @author    Stuart Herbert <stuherbert@ganbarodigital.com>
 * @copyright 2011-present MediaSift Ltd www.datasift.com
 * @copyright 2015-present Ganbaro Digital Ltd www.ganbarodigital.com
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://code.ganbarodigital.com/php-process-runner
 */

namespace GanbaroDigital\ProcessRunner\Requirements;

use GanbaroDigital\ProcessRunner\Values\ProcessResult;
use PHPUnit_Framework_TestCase;

/**
 * @coversDefaultClass GanbaroDigital\ProcessRunner\Requirements\RequireProcessSucceeded
 */
class RequireProcessSucceededTest extends PHPUnit_Framework_TestCase
{
    /**
     * @coversNothing
     */
    public function testCanInstantiate()
    {
        // ----------------------------------------------------------------
        // setup your test



        // ----------------------------------------------------------------
        // perform the change

        $obj = new RequireProcessSucceeded;

        // ----------------------------------------------------------------
        // test the results

        $this->assertTrue($obj instanceof RequireProcessSucceeded);
    }

    /**
     * @covers ::__invoke
     * @dataProvider provideResultsThatSucceeded
     */
    public function testCanUseAsObject($data)
    {
        // ----------------------------------------------------------------
        // setup your test

        $obj = new RequireProcessSucceeded;

        // ----------------------------------------------------------------
        // perform the change

        $obj($data);

        // ----------------------------------------------------------------
        // test the results
        //
        // if we get here, then all is well
    }

    /**
     * @covers ::check
     * @dataProvider provideResultsThatSucceeded
     */
    public function testCanCallStatically($data)
    {
        // ----------------------------------------------------------------
        // setup your test

        // ----------------------------------------------------------------
        // perform the change

        RequireProcessSucceeded::check($data);

        // ----------------------------------------------------------------
        // test the results
        //
        // if we get here, then all is well
    }

    /**
     * @covers ::check
     * @covers ::checkProcessResult
     * @dataProvider provideResultsThatFailed
     * @expectedException GanbaroDigital\ProcessRunner\Exceptions\E4xx_ProcessFailed
     */
    public function testThrowsExceptionIfACommandFailed($data)
    {
        // ----------------------------------------------------------------
        // setup your test

        // ----------------------------------------------------------------
        // perform the change

        RequireProcessSucceeded::check($data);
    }

    /**
     * @covers ::check
     * @covers ::checkProcessResult
     * @dataProvider provideResultsThatSucceeded
     */
    public function testDoesNotThrowExceptionIfCommandSucceeded($data)
    {
        // ----------------------------------------------------------------
        // setup your test

        // ----------------------------------------------------------------
        // perform the change

        RequireProcessSucceeded::check($data);

        // ----------------------------------------------------------------
        // test the results
        //
        // if we get here, then all is well
    }

    public function provideResultsThatSucceeded()
    {
        return [ [ new ProcessResult([], 0, '') ] ];
    }

    public function provideResultsThatFailed()
    {
        $retval = [];
        for ($i = -255; $i <0; $i++) {
            $retval[] = [ new ProcessResult([], $i, '') ];
        }
        for ($i = 1; $i <256; $i++) {
            $retval[] = [ new ProcessResult([], $i, '') ];
        }

        return $retval;
    }
}