<?php

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

namespace Rhubarb\Scaffolds\BackgroundTasks\Scripts;

use Rhubarb\Scaffolds\BackgroundTasks\BackgroundTask;
use Rhubarb\Scaffolds\BackgroundTasks\Task;
use Rhubarb\Scaffolds\BackgroundTasks\Models\BackgroundTaskStatus;

$taskClass = $argv[2];

if (!$taskClass) {
    die("No background task specified");
}

$taskId = intval($argv[3]);

if (!$taskId) {
    die("No background task specified");
}

// Get additional arguments passed to the task runner.
// See docs for more details on passing shell arguments.
$additionalArguments = array_slice($argv, 4);

$pid = pcntl_fork();

if ($pid) {
    exit;
}

if (posix_setsid() < 0) {
    exit;
}

$taskStatus = new BackgroundTaskStatus($taskId);
$taskStatus->ProcessID = getmypid();
$taskStatus->save();

$taskClass = $taskStatus->TaskClass;
$task = new $taskClass(...$taskStatus->TaskSettings);
$task->setShellArguments($additionalArguments);

BackgroundTask::executeInBackground($task, null, $taskStatus);
