<?php

namespace Noitran\Lumen\Horizon;

use Noitran\Lumen\Horizon\Jobs\RabbitMQJob;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use Laravel\Horizon\Events\JobDeleted;
use Laravel\Horizon\Events\JobPushed;
use Laravel\Horizon\Events\JobReserved;
use Laravel\Horizon\JobId;
use Laravel\Horizon\JobPayload;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue as BaseQueue;

class RabbitMQQueue extends BaseQueue
{
    /**
     * The job that last pushed to queue via the "push" method.
     *
     * @var object|string
     */
    protected $lastPushed;

    /**
     * Get the number of queue jobs that are ready to process.
     *
     * @param  string|null  $queue
     *
     * @return int
     */
    public function readyNow($queue = null): int
    {
        return $this->size($queue);
    }

    /**
     * {@inheritdoc}
     */
    public function push($job, $data = '', $queue = null)
    {
        $this->lastPushed = $job;

        return parent::push($job, $data, $queue);
    }

    /**
     * {@inheritdoc}
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $payload = (new JobPayload($payload))->prepare($this->lastPushed)->value;

        return tap(parent::pushRaw($payload, $queue, $options), function () use ($queue, $payload): void {
            $this->event($this->getQueue($queue), new JobPushed($payload));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $payload = (new JobPayload($this->createPayload($job, $data)))->prepare($job)->value;

        $options = [
            'delay' => $this->secondsUntil($delay),
        ];

        return tap(parent::pushRaw($payload, $queue, $options), function () use ($payload, $queue): void {
            $this->event($this->getQueue($queue), new JobPushed($payload));
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function popRaw($queueName = null): ?RabbitMQJob
    {
        try {
            [$queue] = $this->declareEverything($queueName);

            $consumer = $this->getContext()->createConsumer($queue);

            if ($message = $consumer->receiveNoWait()) {
                return new RabbitMQJob($this->container, $this, $consumer, $message);
            }
        } catch (\Exception $exception) {
            $this->reportConnectionError('pop', $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function pop($queue = null)
    {
        return tap($this->popRaw($queue), function ($result) use ($queue): void {
            if ($result) {
                $this->event($this->getQueue($queue), new JobReserved($result->getRawBody()));
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function release($delay, $job, $data, $queue, $attempts = 0)
    {
        $this->lastPushed = $job;

        return parent::release($delay, $job, $data, $queue, $attempts);
    }

    /**
     * Fire the given event if a dispatcher is bound.
     *
     * @param  string  $queue
     * @param  mixed  $event
     *
     * @return void
     */
    protected function event($queue, $event): void
    {
        if ($this->container && $this->container->bound(Dispatcher::class)) {
            $queue = Str::replaceFirst('queues:', '', $queue);

            $this->container->make(Dispatcher::class)->dispatch(
                $event->connection($this->getConnectionName())->queue($queue)
            );
        }
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  string  $job
     * @param  string  $queue
     * @param  mixed   $data
     *
     * @return string
     */
    protected function createPayloadArray($job, $queue, $data = ''): string
    {
        return array_merge(parent::createPayloadArray($job, $queue, $data), [
            'id' => $this->getRandomId(),
            'attempts' => 0,
        ]);
    }

    /**
     * Fire the job deleted event.
     *
     * @param  string  $queue
     * @param  \Noitran\Lumen\Horizon\Jobs\RabbitMQJob  $job
     *
     * @return void
     */
    public function deleteReserved($queue, $job): void
    {
        $this->event($this->getQueue($queue), new JobDeleted($job, $job->getRawBody()));
    }

    /**
     * Get the queue name.
     *
     * @param string|null $queue
     *
     * @return string
     */
    protected function getQueue($queue = null): string
    {
        return $queue ?: $this->queueName;
    }

    /**
     * Get a random ID string.
     *
     * @return string
     */
    protected function getRandomId(): string
    {
        return JobId::generate();
    }
}
