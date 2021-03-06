Triggering from a browser using Leaf
====================================

To make integrating background tasks with user interface easier, a number of UI
[leaves](/manual/module.leaf/) are provided to provide good base patterns to make this
a simple job.

The task is triggered using AJAX through the ViewBridge's `raiseServerEvent` JS method. For a
web application this natively puts the processing into a background process as it's unconnected
with any HTML rendering request. 

## Basic Approach
 
``` demo[examples/Basic/BasicBehaviour.php]
```

for 2 different interface scenarios. You can use these presenters as-is, extend them or follow them as a guide.

Both presenters require that you configure them with the BackgroundTaskStatusID of the running background
task. In other words, your application must initiate the background task and then inform the presenter.

In this standard 'postback' example we respond to an event from our view by initiating the task and
giving the status ID to the view who passes it on to the progress presenter:

~~~ php
class MyPresenter extends FormPresenter
{
    protected function configureView()
    {
        parent::configureView();

        $this->view->attachEventHandler( "StartLongTask", function()
        {
            $status = LongTask::initiate();

            $this->view->backgroundTaskStatusId = $status->BackgroundTaskStatusID;
        });
    }
}

class MyView extends HtmlView
{
    public $backgroundTaskStatusId;

    public function configurePresenters()
    {
        if ( $this->backgroundTaskStatusId != null ) {
            $this->leaves["Progress"]->setBackgroundTaskStatusId( $this->backgroundTaskStatusId );
        }
    }
}
~~~

This approach should also work if the Button presenter triggering the view event is set to run in xmlRpc mode
as the BackgroundTaskStatusID change will be picked up back on the client side after the ajax post.

Finally you can also set the backgroundTaskStatusId in javascript from a view bridge:

~~~ js
bridge.prototype.attachEvents = function() {

    var self = this;

    $('a.execute').click( function() {
        // Raise a server event that should return the status ID
        self.raiseServerEvent("StartLongTask", function(taskStatusId) {
            // Pass the status ID to the progress presenter.
            self.findViewBridge("ProgressBar").setBackgroundTaskStatusId(taskStatusId);
        } );
    } );
};
~~~

### Full Focus

Sometimes the action that's being undertaken is a 'full focus' event. For example, submitting details to a
credit card provider (assuming full PCI compliance of course!) or running a database migration tool. In this case
you can use the BackgroundTaskBlockingPresenter to handle the user interface for you.

The normal pattern for using a full focus background task is to host it as a 'step' in a SwitchedPresenter.
After the background task has started (triggered by the click of a button for example), the step is changed
to the full focus presenter which has already been configured with the full focus presenter.

The BackgroundTaskFullFocus will trigger a server side event when the task completes and this will be
either as a normal post back, or via an XHR request. It can additionally be configured to redirect to a target URL
(e.g. a payment complete page) instead of handling the event directly.

A BackgroundTaskFullFocus must be constructed with a view that extends BackgroundTaskFullFocusView as an
argument to supply the content for the 'holding' page.

~~~ php
class MySwitchedPresenter extends SwitchedPresenter
{
    protected function getSwitchedPresenters()
    {
        $presenters = [
            "payment" => $payment = new PaymentDetailsPresenter(),
            "please-wait" => $pleaseWait = new BackgroundTaskFullFocus( new PaymentProcessingView() ),
            "thanks" => new ThanksPresenter()
        ];

        $payment->attachEventHandler( "StartLongTask", function() use ($pleaseWait)
        {
            $status = LongTask::initiate();

            $pleaseWait->setBackgroundTaskStatusId( $status->BackgroundTaskStatusID );

            $this->changePresenter( "please-wait" );
        });

        $pleaseWait->attachEventHandler( "TaskComplete", function($status){
            $this->changePresenter( "thanks" );
        } );

        return $presenters;
    }
}

class PaymentProcessingView extends BackgroundTaskFullFocusView
{
    public function printViewContent()
    {
        ?>
        <h2>Your payment is processing.... Please Wait</h2>
        <?php
    }
}
~~~

Some background tasks run quickly enough most of the time to allow you to avoid the interim. If that's the
case you can set an 'acceptableWaitTime' in microseconds on the presenter. The same example again:

~~~ php
class MySwitchedPresenter extends SwitchedPresenter
{
    protected function getSwitchedPresenters()
    {
        $presenters = [
            "payment" => $payment = new PaymentDetailsPresenter(),
            "please-wait" => $pleaseWait = new BackgroundTaskFullFocus( new PaymentProcessingView() ),
            "thanks" => new ThanksPresenter()
        ];

        // Wait for 0.5 seconds to see if the task completes before sending back the interim step.
        $pleaseWait->setAcceptableWaitTime( 500000 );

        $payment->attachEventHandler( "StartLongTask", function() use ($pleaseWait)
        {
            $status = LongTask::initiate();

            $pleaseWait->setBackgroundTaskStatusId( $status->BackgroundTaskStatusID );

            $this->changePresenter( "please-wait" );
        });

        $pleaseWait->attachEventHandler( "TaskComplete", function($status){
            $this->changePresenter( "thanks" );
        } );

        return $presenters;
    }
}
~~~

### Progress Bar support

This module includes a presenter you can use to act as a progress bar for the background task. Here is a sample
Presenter and View where a background task is executed on the event of a button being pushed that then displays
a progress bar.

~~~ php
class DemoPresenter extends FormPresenter
{
    private $longTask;

    protected function createView()
    {
        return new DemoView();
    }

    protected function configureView()
    {
        parent::configureView();

        $this->view->attachEventHandler("StartLongTask", function()
        {
            // Run the long task but capture the returned task object.
            $this->longTask = BackgroundTask::execute(function()
            {
                // Do something hard.
                reticulateSplines();
            });
        });
    }

    protected function applyModelToView()
    {
        // Pass the
        $this->view->longTaskId = $this->longTask->backgroundTaskId;
    }
}

class DemoView extends HtmlView
{
    public $longTaskId = null;

    protected function createSubLeaves()
    {
        $this->registerSubLeaf(
            new Button( "DoLongTask", "Reticulate The Splines", function()
            {
                $this->raiseEvent( "StartLongTask" );
            }),
            new BackgroundProgressPresenter( "Progress" )
        );
    }

    protected function configurePresenters()
    {
        if ( $this->longTaskId ) {
            // If we have a background task ID we need to pass this to our progress presenter
            $this->leaves[ "Progress" ]->setBackgroundTaskId = $this->longTaskId;
        }
    }

    public function printViewContent()
    {
        print $this->leaves[ "DoLongTask" ];

        if ( $this->longTaskId ) {
            // Only if we have a background task should we print the progress presenter.
            print $this->leaves[ "Progress" ];
        }
    }
}
~~~

### 'Global' progress bar

Sometimes you want to show a progress bar on the site not as a direct consequence of the user interacting
with your page, but as a consequence of **any** user starting a background task. This is quite simple to
achieve - simply create a BackgroundTask and set it's task id like this:

~~~ php
// In createSubLeaves():

$this->registerSubLeaf(
    $progress = new BackgroundTask( "Progress" )
);

try {
    $runningTask = BackgroundTaskStatus::findLast(new Equals("TaskStatus", "Running"));
    $progress->setBackgroundTaskStatusId($runningTask->UniqueIdentifier);
    $this->showProgressBar = true;
} catch (RecordNotFoundException $er) {}
~~~


This simply looks for the last task, and if it's running configures the progress bar to track it. The only
remaining thing to do is to show the progress bar if it has been configured this way:

~~~ php
// In printViewContent():

if ($this->showProgressBar) {
    print $this->leaves["Progress"];
}
~~~