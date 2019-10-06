<?php

namespace Noitran\Lumen\Horizon\Tests;

use Interop\Amqp\AmqpTopic;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpProducer;


class RabbitMQQueueTest extends TestCase
{
    public function testShouldSendExpectedMessageOnPushRaw()
    {
        $expectedQueueName = 'theQueueName';
        $expectedBody = 'thePayload';
        $topic = $this->createMock(AmqpTopic::class);
        $queue = $this->createMock(AmqpQueue::class);
        $queue->expects($this->any())->method('getQueueName')->willReturn('theQueueName');
        $producer = $this->createMock(AmqpProducer::class);
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($topic), $this->isInstanceOf(AmqpMessage::class))
            ->willReturnCallback(function ($actualTopic, AmqpMessage $message) use ($expectedQueueName, $expectedBody, $topic) {
                $this->assertSame($topic, $actualTopic);
                $this->assertSame($expectedBody, $message->getBody());
                $this->assertSame($expectedQueueName, $message->getRoutingKey());
                $this->assertSame('application/json', $message->getContentType());
                $this->assertSame(AmqpMessage::DELIVERY_MODE_PERSISTENT, $message->getDeliveryMode());
                $this->assertNotEmpty($message->getCorrelationId());
                $this->assertNull($message->getProperty(RabbitMQJob::ATTEMPT_COUNT_HEADERS_KEY));
            });
        $producer
            ->expects($this->never())
            ->method('setDeliveryDelay');
        $context = $this->createAmqpContext();
        $context
            ->expects($this->once())
            ->method('createTopic')
            ->willReturn($topic);
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->with($expectedBody)
            ->willReturn(new \Interop\Amqp\Impl\AmqpMessage($expectedBody));
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with($expectedQueueName)
            ->willReturn($queue);
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producer);
        $queue = new RabbitMQQueue($context, $this->createDummyConfig());
        $queue->setContainer($this->createDummyContainer());
        $queue->pushRaw('thePayload', $expectedQueueName);
    }
}
