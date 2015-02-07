<?php
/*
 * This file is part of the prooph processing framework.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.10.14 - 20:02
 */

namespace Prooph\ProcessingTest\Message;

use Prooph\Processing\Message\LogMessage;
use Prooph\Processing\Message\WorkflowMessage;
use Prooph\Processing\Processor\NodeName;
use Prooph\Processing\Processor\ProcessId;
use Prooph\Processing\Processor\Task\CollectData;
use Prooph\Processing\Processor\Task\TaskListId;
use Prooph\Processing\Processor\Task\TaskListPosition;
use Prooph\ProcessingTest\Mock\UserDictionary;
use Prooph\ProcessingTest\TestCase;

/**
 * Class LogMessageTest
 *
 * @package Prooph\ProcessingTest\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class LogMessageTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_log_a_warning_msg()
    {
        $wfMessage = $this->getTestWorkflowMessage();

        $message = LogMessage::logWarningMsg("A simple warning msg", $wfMessage);

        $this->assertEquals('A simple warning msg', $message->technicalMsg());
        $this->assertTrue($message->isWarning());
        $this->assertEquals($wfMessage->target(), $message->origin());
        $this->assertTrue($wfMessage->processTaskListPosition()->equals($message->processTaskListPosition()));
        $this->assertEquals(NodeName::defaultName()->toString(), $message->target());
    }

    /**
     * @test
     */
    public function it_can_log_a_debug_msg()
    {
        $wfMessage = $this->getTestWorkflowMessage();

        $message = LogMessage::logDebugMsg("A simple debug msg", $wfMessage);

        $this->assertEquals('A simple debug msg', $message->technicalMsg());
        $this->assertTrue($message->isDebug());
        $this->assertEquals($wfMessage->target(), $message->origin());
        $this->assertTrue($wfMessage->processTaskListPosition()->equals($message->processTaskListPosition()));
        $this->assertEquals(NodeName::defaultName()->toString(), $message->target());
    }

    /**
     * @test
     */
    public function it_can_log_a_data_processing_started_on_msg()
    {
        $wfMessage = $this->getTestWorkflowMessage();

        $message = LogMessage::logInfoDataProcessingStarted($wfMessage);

        $this->assertEquals('Data processing was started', $message->technicalMsg());
        $this->assertTrue($message->isInfo());
        $this->assertEquals($wfMessage->target(), $message->origin());
        $this->assertTrue($wfMessage->processTaskListPosition()->equals($message->processTaskListPosition()));
        $this->assertEquals(NodeName::defaultName()->toString(), $message->target());
    }

    /**
     * @test
     */
    public function it_can_log_an_exception_and_set_msg_code_to_500_if_no_code_is_specified()
    {
        $wfMessage = $this->getTestWorkflowMessage();

        $exception = new \RuntimeException("Internal error");

        $message = LogMessage::logException($exception, $wfMessage);

        $this->assertEquals('Internal error', $message->technicalMsg());
        $this->assertTrue($message->isError());
        $this->assertEquals(500, $message->msgCode());
        $this->assertEquals($wfMessage->target(), $message->origin());
        $this->assertTrue($wfMessage->processTaskListPosition()->equals($message->processTaskListPosition()));
        $this->assertTrue(isset($message->msgParams()['trace']));
        $this->assertEquals(NodeName::defaultName()->toString(), $message->target());
    }

    /**
     * @test
     */
    public function it_logs_exception_and_uses_exception_code_for_msg_code_if_specified()
    {
        $wfMessage = $this->getTestWorkflowMessage();

        $exception = new \DomainException("Data cannot be found", 404);

        $message = LogMessage::logException($exception, $wfMessage);

        $this->assertEquals('Data cannot be found', $message->technicalMsg());
        $this->assertTrue($message->isError());
        $this->assertEquals(404, $message->msgCode());
        $this->assertEquals($wfMessage->target(), $message->origin());
        $this->assertTrue($wfMessage->processTaskListPosition()->equals($message->processTaskListPosition()));
        $this->assertTrue(isset($message->msgParams()['trace']));
        $this->assertEquals(NodeName::defaultName()->toString(), $message->target());
    }

    /**
     * @test
     */
    public function it_only_accepts_error_code_greater_than_399_otherwise_it_uses_500_as_code()
    {
        $wfMessage = $this->getTestWorkflowMessage();

        $exception = new \DomainException("Data cannot be found", 399);

        $message = LogMessage::logException($exception, $wfMessage);

        $this->assertEquals('Data cannot be found', $message->technicalMsg());
        $this->assertTrue($message->isError());
        $this->assertEquals(500, $message->msgCode());
        $this->assertEquals($wfMessage->target(), $message->origin());
        $this->assertTrue($wfMessage->processTaskListPosition()->equals($message->processTaskListPosition()));
        $this->assertTrue(isset($message->msgParams()['trace']));
        $this->assertEquals(NodeName::defaultName()->toString(), $message->target());
    }

    /**
     * @test
     */
    public function it_logs_no_message_received_for_task_as_error()
    {
        $wfMessage = $this->getTestWorkflowMessage();

        $task = CollectData::from('crm', UserDictionary::prototype());

        $message = LogMessage::logNoMessageReceivedFor($task, $wfMessage->processTaskListPosition());

        $this->assertTrue($message->isError());
        $this->assertEquals(412, $message->msgCode());
        $this->assertEquals($wfMessage->processTaskListPosition()->taskListId()->nodeName(), $message->origin());
        $this->assertTrue($wfMessage->processTaskListPosition()->equals($message->processTaskListPosition()));
        $this->assertTrue(isset($message->msgParams()['task_class']));
        $this->assertTrue(isset($message->msgParams()['task_as_json']));
        $this->assertTrue(isset($message->msgParams()['task_list_position']));
        $this->assertTrue(isset($message->msgParams()['process_id']));

        $this->assertEquals($wfMessage->processTaskListPosition()->taskListId()->processId()->toString(), $message->msgParams()['process_id']);
        $this->assertEquals($wfMessage->processTaskListPosition()->position(), $message->msgParams()['task_list_position']);
        $this->assertEquals(get_class($task), $message->msgParams()['task_class']);
        $this->assertEquals(json_encode($task->getArrayCopy()), $message->msgParams()['task_as_json']);
        $this->assertEquals(NodeName::defaultName()->toString(), $message->target());
    }

    /**
     * @test
     */
    public function it_logs_wrong_message_received_for_task_as_error()
    {
        $wfMessage = $this->getTestWorkflowMessage();

        $task = CollectData::from('crm', UserDictionary::prototype());

        $logMessage = LogMessage::logWrongMessageReceivedFor($task, $wfMessage->processTaskListPosition(), $wfMessage);

        $this->assertTrue($logMessage->isError());
        $this->assertEquals(415, $logMessage->msgCode());
        $this->assertEquals($wfMessage->processTaskListPosition()->taskListId()->nodeName()->toString(), $logMessage->origin());
        $this->assertTrue($wfMessage->processTaskListPosition()->equals($logMessage->processTaskListPosition()));
        $this->assertTrue(isset($logMessage->msgParams()['task_class']));
        $this->assertTrue(isset($logMessage->msgParams()['task_as_json']));
        $this->assertTrue(isset($logMessage->msgParams()['task_list_position']));
        $this->assertTrue(isset($logMessage->msgParams()['process_id']));
        $this->assertTrue(isset($logMessage->msgParams()['message_name']));
        $this->assertEquals(NodeName::defaultName()->toString(), $logMessage->target());

        $this->assertEquals($wfMessage->processTaskListPosition()->taskListId()->processId()->toString(), $logMessage->msgParams()['process_id']);
        $this->assertEquals($wfMessage->processTaskListPosition()->position(), $logMessage->msgParams()['task_list_position']);
        $this->assertEquals(get_class($task), $logMessage->msgParams()['task_class']);
        $this->assertEquals(json_encode($task->getArrayCopy()), $logMessage->msgParams()['task_as_json']);
        $this->assertEquals($wfMessage->getMessageName(), $logMessage->msgParams()['message_name']);
    }

    /**
     * @test
     */
    public function it_logs_unsupported_message_received_as_error()
    {
        $wfMessage = $this->getTestWorkflowMessage();

        $logMessage = LogMessage::logUnsupportedMessageReceived($wfMessage);

        $this->assertTrue($logMessage->isError());
        $this->assertEquals(416, $logMessage->msgCode());
        $this->assertTrue($wfMessage->processTaskListPosition()->equals($logMessage->processTaskListPosition()));
        $this->assertTrue(isset($logMessage->msgParams()['workflow_message_handler']));
        $this->assertTrue(isset($logMessage->msgParams()['message_name']));

        $this->assertEquals($wfMessage->target(), $logMessage->msgParams()['workflow_message_handler']);
        $this->assertEquals($wfMessage->getMessageName(), $logMessage->msgParams()['message_name']);
    }

    /**
     * @test
     */
    public function it_translates_to_service_bus_message_and_back()
    {
        $wfMessage = $this->getTestWorkflowMessage();

        $logMessage = LogMessage::logWarningMsg("A simple warning msg", $wfMessage);

        $sbMessage = $logMessage->toServiceBusMessage();

        $this->assertInstanceOf('Prooph\ServiceBus\Message\StandardMessage', $sbMessage);

        $copyOfLogMessage = LogMessage::fromServiceBusMessage($sbMessage);

        $this->assertInstanceOf('Prooph\Processing\Message\LogMessage', $copyOfLogMessage);

        $this->assertEquals('A simple warning msg', $copyOfLogMessage->technicalMsg());
        $this->assertTrue($copyOfLogMessage->isWarning());
        $this->assertEquals($wfMessage->target(), $copyOfLogMessage->origin());
        $this->assertTrue($wfMessage->processTaskListPosition()->equals($copyOfLogMessage->processTaskListPosition()));
        $this->assertEquals(NodeName::defaultName()->toString(), $copyOfLogMessage->target());
    }

    /**
     * @test
     */
    function it_can_log_a_items_processing_failed_message_with_a_failed_msg_for_each_failed_item()
    {
        $wfMessage = $this->getTestWorkflowMessage();

        $successfulItems = 3;
        $failedItems = 2;
        $failedMsgs = [
            'Processing failed!',
            'Processing failed, too!'
        ];

        $logMsg = LogMessage::logItemsProcessingFailed($successfulItems, $failedItems, $failedMsgs, $wfMessage);

        $this->assertTrue($logMsg->isError());

        $this->assertEquals(LogMessage::ERROR_ITEMS_PROCESSING_FAILED, $logMsg->msgCode());

        $this->assertEquals('Processing for 2 of 5 items failed', $logMsg->technicalMsg());

        $msgParams = $logMsg->msgParams();

        $this->assertTrue(isset($msgParams[LogMessage::MSG_PARAM_SUCCESSFUL_ITEMS]));
        $this->assertTrue(isset($msgParams[LogMessage::MSG_PARAM_FAILED_ITEMS]));
        $this->assertTrue(isset($msgParams[LogMessage::MSG_PARAM_FAILED_MESSAGES]));

        $this->assertEquals($successfulItems, $msgParams[LogMessage::MSG_PARAM_SUCCESSFUL_ITEMS]);
        $this->assertEquals($failedItems, $msgParams[LogMessage::MSG_PARAM_FAILED_ITEMS]);
        $this->assertEquals($failedMsgs, $msgParams[LogMessage::MSG_PARAM_FAILED_MESSAGES]);
    }

    /**
     * @return WorkflowMessage
     */
    private function getTestWorkflowMessage()
    {
        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $wfMessage = WorkflowMessage::collectDataOf(UserDictionary::prototype(), 'test-case', 'message-handler');

        $wfMessage->connectToProcessTask($taskListPosition);

        return $wfMessage;
    }
}
 