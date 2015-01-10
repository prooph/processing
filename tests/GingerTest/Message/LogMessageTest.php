<?php
/*
 * This file is part of the Ginger Workflow Framework.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 03.10.14 - 20:02
 */

namespace GingerTest\Message;

use Ginger\Message\LogMessage;
use Ginger\Message\WorkflowMessage;
use Ginger\Processor\NodeName;
use Ginger\Processor\ProcessId;
use Ginger\Processor\Task\CollectData;
use Ginger\Processor\Task\TaskListId;
use Ginger\Processor\Task\TaskListPosition;
use GingerTest\Mock\UserDictionary;
use GingerTest\TestCase;

/**
 * Class LogMessageTest
 *
 * @package GingerTest\Message
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class LogMessageTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_log_a_warning_msg()
    {
        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $message = LogMessage::logWarningMsg("A simple warning msg", $taskListPosition);

        $this->assertEquals('A simple warning msg', $message->technicalMsg());
        $this->assertTrue($message->isWarning());
        $this->assertTrue($taskListPosition->equals($message->processTaskListPosition()));
    }

    /**
     * @test
     */
    public function it_can_log_a_debug_msg()
    {
        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $message = LogMessage::logDebugMsg("A simple debug msg", $taskListPosition);

        $this->assertEquals('A simple debug msg', $message->technicalMsg());
        $this->assertTrue($message->isDebug());
        $this->assertTrue($taskListPosition->equals($message->processTaskListPosition()));
    }

    /**
     * @test
     */
    public function it_can_log_a_data_processing_started_on_msg()
    {
        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $message = LogMessage::logInfoDataProcessingStarted($taskListPosition);

        $this->assertEquals('Data processing was started', $message->technicalMsg());
        $this->assertTrue($message->isInfo());
        $this->assertTrue($taskListPosition->equals($message->processTaskListPosition()));
    }

    /**
     * @test
     */
    public function it_can_log_an_exception_and_set_msg_code_to_500_if_no_code_is_specified()
    {
        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $exception = new \RuntimeException("Internal error");

        $message = LogMessage::logException($exception, $taskListPosition);

        $this->assertEquals('Internal error', $message->technicalMsg());
        $this->assertTrue($message->isError());
        $this->assertEquals(500, $message->msgCode());
        $this->assertTrue($taskListPosition->equals($message->processTaskListPosition()));
        $this->assertTrue(isset($message->msgParams()['trace']));
    }

    /**
     * @test
     */
    public function it_logs_exception_and_uses_exception_code_for_msg_code_if_specified()
    {
        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $exception = new \DomainException("Data cannot be found", 404);

        $message = LogMessage::logException($exception, $taskListPosition);

        $this->assertEquals('Data cannot be found', $message->technicalMsg());
        $this->assertTrue($message->isError());
        $this->assertEquals(404, $message->msgCode());
        $this->assertTrue($taskListPosition->equals($message->processTaskListPosition()));
        $this->assertTrue(isset($message->msgParams()['trace']));
    }

    /**
     * @test
     */
    public function it_only_accepts_error_code_greater_than_399_otherwise_it_uses_500_as_code()
    {
        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $exception = new \DomainException("Data cannot be found", 399);

        $message = LogMessage::logException($exception, $taskListPosition);

        $this->assertEquals('Data cannot be found', $message->technicalMsg());
        $this->assertTrue($message->isError());
        $this->assertEquals(500, $message->msgCode());
        $this->assertTrue($taskListPosition->equals($message->processTaskListPosition()));
        $this->assertTrue(isset($message->msgParams()['trace']));
    }

    /**
     * @test
     */
    public function it_logs_no_message_received_for_task_as_error()
    {
        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $task = CollectData::from('crm', UserDictionary::prototype());

        $message = LogMessage::logNoMessageReceivedFor($task, $taskListPosition);

        $this->assertTrue($message->isError());
        $this->assertEquals(412, $message->msgCode());
        $this->assertTrue($taskListPosition->equals($message->processTaskListPosition()));
        $this->assertTrue(isset($message->msgParams()['task_class']));
        $this->assertTrue(isset($message->msgParams()['task_as_json']));
        $this->assertTrue(isset($message->msgParams()['task_list_position']));
        $this->assertTrue(isset($message->msgParams()['process_id']));

        $this->assertEquals($taskListPosition->taskListId()->processId()->toString(), $message->msgParams()['process_id']);
        $this->assertEquals($taskListPosition->position(), $message->msgParams()['task_list_position']);
        $this->assertEquals(get_class($task), $message->msgParams()['task_class']);
        $this->assertEquals(json_encode($task->getArrayCopy()), $message->msgParams()['task_as_json']);
    }

    /**
     * @test
     */
    public function it_logs_wrong_message_received_for_task_as_error()
    {
        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $task = CollectData::from('crm', UserDictionary::prototype());

        $wfMessage = WorkflowMessage::collectDataOf(UserDictionary::prototype());

        $logMessage = LogMessage::logWrongMessageReceivedFor($task, $taskListPosition, $wfMessage);

        $this->assertTrue($logMessage->isError());
        $this->assertEquals(415, $logMessage->msgCode());
        $this->assertTrue($taskListPosition->equals($logMessage->processTaskListPosition()));
        $this->assertTrue(isset($logMessage->msgParams()['task_class']));
        $this->assertTrue(isset($logMessage->msgParams()['task_as_json']));
        $this->assertTrue(isset($logMessage->msgParams()['task_list_position']));
        $this->assertTrue(isset($logMessage->msgParams()['process_id']));
        $this->assertTrue(isset($logMessage->msgParams()['message_name']));

        $this->assertEquals($taskListPosition->taskListId()->processId()->toString(), $logMessage->msgParams()['process_id']);
        $this->assertEquals($taskListPosition->position(), $logMessage->msgParams()['task_list_position']);
        $this->assertEquals(get_class($task), $logMessage->msgParams()['task_class']);
        $this->assertEquals(json_encode($task->getArrayCopy()), $logMessage->msgParams()['task_as_json']);
        $this->assertEquals($wfMessage->getMessageName(), $logMessage->msgParams()['message_name']);
    }

    /**
     * @test
     */
    public function it_logs_unsupported_message_received_as_error()
    {
        $taskListPosition = TaskListPosition::at(TaskListId::linkWith(NodeName::defaultName(), ProcessId::generate()), 1);

        $wfMessage = WorkflowMessage::collectDataOf(UserDictionary::prototype());

        $wfMessage->connectToProcessTask($taskListPosition);

        $logMessage = LogMessage::logUnsupportedMessageReceived($wfMessage, 'test-message-handler');

        $this->assertTrue($logMessage->isError());
        $this->assertEquals(416, $logMessage->msgCode());
        $this->assertTrue($taskListPosition->equals($logMessage->processTaskListPosition()));
        $this->assertTrue(isset($logMessage->msgParams()['workflow_message_handler']));
        $this->assertTrue(isset($logMessage->msgParams()['message_name']));

        $this->assertEquals('test-message-handler', $logMessage->msgParams()['workflow_message_handler']);
        $this->assertEquals($wfMessage->getMessageName(), $logMessage->msgParams()['message_name']);
    }
}
 