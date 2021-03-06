<?php

namespace Rhubarb\Scaffolds\BackgroundTasks\Tests;

use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Scaffolds\BackgroundTasks\BackgroundTasksModule;
use Rhubarb\Scaffolds\BackgroundTasks\Models\BackgroundTaskStatus;
use Rhubarb\Scaffolds\BackgroundTasks\Tests\Fixtures\UnitTestBackgroundTaskOne;
use Rhubarb\Scaffolds\BackgroundTasks\Tests\Fixtures\UnitTestBackgroundTaskTwo;

/*
 *	Copyright 2015 RhubarbPHP
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

class BackgroundTaskTest extends RhubarbTestCase
{
    const TEST_FILE = "cache/background-task-test.txt";

    protected function setUp()
    {
        parent::setUp();

        $this->application->registerModule(new BackgroundTasksModule());
    }

    private function removeTestFile()
    {
        if (file_exists(self::TEST_FILE)) {
            unlink(self::TEST_FILE);
        }
    }

    public function testBackgroundTaskRuns()
    {
        $this->removeTestFile();

        UnitTestBackgroundTaskOne::initiate();

        usleep(50000);

        $this->assertFileExists(self::TEST_FILE, 'The task should have created the test file by now.');
    }

    public function testBackgroundTaskRunsInBackground()
    {
        $this->removeTestFile();

        $status = UnitTestBackgroundTaskTwo::initiate();

        usleep(50000);

        $this->assertFileNotExists(self::TEST_FILE, 'The task shouldn\'t have completed yet so the test file should not exist.');

        $status->reload();

        $this->assertTrue($status->isRunning(), 'The task should still report that it is running');

        $lastTask = BackgroundTaskStatus::findLast();
        $this->assertEquals($lastTask->UniqueIdentifier, $status->UniqueIdentifier, "The execute method should have" .
            "returned the status object from tblBackgroundTaskStatus");

        $this->assertEquals("Foo", $lastTask->Message, "The task should be able to set the message");

        usleep(300000);

        $lastTask->reload();

        $this->assertEquals("Bar", $lastTask->Message, "The task should be able to set the message");

        $status->reload();
        $this->assertFalse($status->isRunning(), "The task no longer be marked as running");
    }
}
