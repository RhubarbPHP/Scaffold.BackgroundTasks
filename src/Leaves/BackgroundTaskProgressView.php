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

namespace Rhubarb\Scaffolds\BackgroundTasks\Leaves;

use Rhubarb\Leaf\Leaves\LeafDeploymentPackage;
use Rhubarb\Leaf\Views\View;
use Rhubarb\Scaffolds\BackgroundTasks\Models\BackgroundTaskStatus;

class BackgroundTaskProgressView extends View
{
    /**
     * @var BackgroundTaskModel
     */
    protected $model;

    protected function printViewContent()
    {
        ?>
        <div class="bar _gauge">
            <div class="progress _needle" style="width: 0%"></div>
            <p class="message"></p>
        </div>
        <?php
    }

    public function getDeploymentPackage()
    {
        return new LeafDeploymentPackage(
            __DIR__ . '/BackgroundTaskViewBridge.js',
            __DIR__ . '/BackgroundTaskProgressViewBridge.js' );
    }

    protected function getViewBridgeName()
    {
        return "BackgroundTaskProgressViewBridge";
    }
}
