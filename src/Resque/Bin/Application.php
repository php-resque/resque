<?php

namespace Resque\Bin;

use Resque\Component\core\ResqueEvents;
use Resque\Redis\RedisQueueFactory;
use Resque\Redis\RedisStatistic;
use Resque\Component\Queue\Registry\QueueRegistry;
use RuntimeException;
use Predis\Client;
use Psr\Log\NullLogger;
use Resque\Component\Core\Event\EventDispatcher;
use Resque\Component\Core\Foreman;
use Resque\Redis\Bridge\PredisBridge;
use Resque\Component\Job\Factory\JobInstanceFactory;
use Resque\Component\Job\ResqueJobEvents;
use Resque\Component\Worker\Factory\WorkerFactory;
use Resque\Component\Worker\ResqueWorkerEvents;
use Resque\Redis\RedisEventListener;
use Resque\Redis\RedisFailure;
use Resque\Redis\RedisQueueRegistryAdapter;
use Psr\Log\LoggerInterface;
use Resque\Redis\RedisWorkerRegistry;

/**
 * Resque application
 *
 * A simple class that fires up some workers. It's configuration is based on Resque/Resque 1.x usage
 * of environment variables.
 */
class Application
{
    /**
     * @var array Array of config vars, loaded from ENV vars.
     */
    public $config = array();

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var PredisBridge
     */
    public $redisClient;

    /**
     * @var EventDispatcher
     */
    public $eventDispatcher;

    /**
     * @var RedisQueueRegistryAdapter
     */
    public $queueRegistry;
    /**
     * @var QueueInterface[]
     */
    public $queues = array();

    /**
     * @var JobInstanceFactory
     */
    public $jobInstanceFactory;

    /**
     * @var \Resque\Component\Statistic\StatisticInterface
     */
    public $statisticBackend;

    /**
     * @var WorkerFactory
     */
    public $workerFactory;
    /**
     * @var RedisWorkerRegistry
     */
    public $workerRegistry;
    /**
     * @var WorkerInterface[]
     */
    public $workers = array();

    /**
     * @var
     */
    public $failureBackend;

    /**
     * @var Foreman
     */
    public $foreman;

    public function __construct()
    {
        $this->eventDispatcher = new EventDispatcher();
    }

    /**
     * Run
     *
     * @todo catch and format exceptions
     *
     * @return int
     */
    public function setup()
    {
        $this->configure();
        $this->handleAppInclude();
        $this->validateConfiguration();
        $this->setupLogger();
        $this->setupRedis();
        $this->setupQueueRegistryFactory();
        $this->setupQueues();
        $this->setupFailureBackend();
        $this->setupStatisticBackend();
        $this->setupJobInstanceFactory();
        $this->setupWorkerFactory();
        $this->setupWorkerRegistry();
        $this->setupWorkers();
        $this->setupForeman();
    }

    /**
     * Configure
     *
     * As Resque/Resque 1.x uses ENV variables to config it, so does this.
     */
    protected function configure()
    {
        $this->config = array(
            'app_include' => getenv('APP_INCLUDE'),
            'worker_count' => false === getenv('COUNT') ? 1 : getenv('COUNT'),
            'queues' => getenv('QUEUE'),
            'queue_blocking' => (bool) getenv('BLOCKING'),
            'queue_interval' =>  false === getenv('INTERVAL') ? 5 : getenv('INTERVAL'),
            'redis_prefix' =>  false === getenv('PREFIX') ? 'resque' : getenv('PREFIX'),
            'redis_dsn' =>  getenv('REDIS_BACKEND'),
            'logging' => (bool) getenv('LOGGING'),
            'verbose' => (bool) getenv('VERBOSE'),
            'very_verbose' => (bool) getenv('VVERBOSE'),
        );
    }

    /**
     * Validate configuration
     *
     * Simply exits when a piece of information is missing.
     */
    protected function validateConfiguration()
    {
        if (empty($this->config['queues']) || count(explode(',', $this->config['queues'])) < 1) {
            throw new RuntimeException('Set QUEUE env var containing the list of queues to work');
        }
    }

    /**
     * Handle app include
     *
     * Loads the given file, or class, which allows the user to make changes to the environment their jobs run in.
     */
    protected function handleAppInclude()
    {
        $include = $this->config['app_include'];

        if ($include) {
            if (!file_exists($include) && !class_exists($include)) {
                throw new RuntimeException(
                    sprintf(
                        'The APP_INCLUDE "%s" does not exist, or could not be found',
                        $include
                    )
                );
            }

            if (file_exists($include)) {
                require_once $include;
            }

            new $include($this);
        }
    }

    protected function setupLogger()
    {
        if (null === $this->logger) {
            if ($this->config['verbose'] || $this->config['very_verbose']) {
                $this->logger = new Logger();

                return;
            }

            $this->logger = new NullLogger();
        }
    }

    protected function setupRedis()
    {
        if (null === $this->redisClient) {
            $this->redisClient = new PredisBridge(
                    new Client(
                    $this->config['redis_dsn'],
                    array(
                        'prefix' => $this->config['redis_prefix'] . ':'
                    )
                )
            );

            $redisEventListener = new RedisEventListener($this->redisClient);

            $this->eventDispatcher->addListener(
                ResqueWorkerEvents::BEFORE_FORK_TO_PERFORM,
                array($redisEventListener, 'disconnectFromRedis')
            );
            $this->eventDispatcher->addListener(
                ResqueEvents::BEFORE_FORK,
                array($redisEventListener, 'disconnectFromRedis')
            );
            $this->eventDispatcher->addListener(
                ResqueWorkerEvents::WAIT_NO_JOB,
                array($redisEventListener, 'disconnectFromRedis')
            );
        }
    }

    protected function setupQueueRegistryFactory()
    {
        if (null === $this->queueRegistry) {
            $factory = new RedisQueueFactory($this->redisClient, $this->eventDispatcher);
            $this->queueRegistry = new QueueRegistry(
                $this->eventDispatcher,
                new RedisQueueRegistryAdapter($this->redisClient, $factory),
                $factory
            );
        }
    }

    protected function setupQueues()
    {
        if (count($this->queues) < 1) {
            $configQueues = explode(',', $this->config['queues']);

            if (in_array('*', $configQueues)) {
                $wildcard = new \Resque\Component\Queue\WildcardQueue($this->queueRegistry);
                $queues[] = $wildcard;
            } else {
                foreach ($configQueues as $configQueue) {
                    $this->queues[] = $this->queueRegistry->createQueue($configQueue);
                }
            }
        }
    }

    protected function setupFailureBackend()
    {
        if (null === $this->failureBackend) {
            $this->failureBackend = new RedisFailure($this->redisClient);
        }

        $failureBackend = $this->failureBackend;

        // When a job fails, save it into redis.
        $this->eventDispatcher->addListener(
            ResqueJobEvents::FAILED,
            function ($event) use ($failureBackend) {
                $failureBackend->save($event->getJob(), $event->getException(), $event->getWorker());
            }
        );
    }

    protected function setupStatisticBackend()
    {
        if (null === $this->statisticBackend) {
            $this->statisticBackend = new RedisStatistic($this->redisClient);
        }

        $statisticBackend = $this->statisticBackend;

        $this->eventDispatcher->addListener(
            ResqueJobEvents::PERFORMED,
            function ($event) use ($statisticBackend) {
                $statisticBackend->jobProcessed($event);
            }
        );
        $this->eventDispatcher->addListener(
            ResqueJobEvents::FAILED,
            function ($event) use ($statisticBackend) {
                $statisticBackend->jobFailed($event);
            }
        );
    }

    protected function setupJobInstanceFactory()
    {
        if (null === $this->jobInstanceFactory) {
            $this->jobInstanceFactory = new JobInstanceFactory();
        }
    }

    protected function setupWorkerFactory()
    {
        if (null === $this->workerFactory) {
            $this->workerFactory = new WorkerFactory(
                $this->queueRegistry,
                $this->jobInstanceFactory,
                $this->eventDispatcher
            );
        }
    }

    protected function setupWorkerRegistry()
    {
        if (null === $this->workerRegistry) {
            $this->workerRegistry = new RedisWorkerRegistry(
                $this->redisClient,
                $this->eventDispatcher,
                $this->workerFactory
            );
        }

        $workerRegistry = $this->workerRegistry;

        // When a job performed, remove it from the worker, also update processed numbers.
        $this->eventDispatcher->addListener(
            \Resque\Component\Job\ResqueJobEvents::PERFORMED,
            function (\Resque\Component\Worker\Event\WorkerJobEvent $event) use ($workerRegistry) {
                $workerRegistry->persist($event->getWorker());
            }
        );
    }

    protected function setupWorkers()
    {
        $this->workers = array();
        for ($i = 0; $i < $this->config['worker_count']; ++$i) {
            $worker = $this->workerFactory->createWorker();
            $worker->setLogger($this->logger);

            foreach ($this->queues as $queue) {
                $worker->addQueue($queue);
            }

            $this->workers[] = $worker;
        }
    }

    protected function setupForeman()
    {
        $this->foreman = new Foreman($this->workerRegistry, $this->eventDispatcher);
        $this->foreman->setLogger($this->logger);
    }

    public function work()
    {
        $this->foreman->pruneDeadWorkers();
        $this->foreman->work($this->workers);
        echo sprintf(
            '%d workers attached to the %s queues successfully started.' . PHP_EOL,
            count($this->workers),
            implode($this->queues, ', ')
        );

        echo sprintf(
            'Workers (%s)' . PHP_EOL,
            implode(', ', $this->workers)
        );
    }
}
