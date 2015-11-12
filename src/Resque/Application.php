<?php
namespace Resque;

use Predis\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Resque\Component\Core\Event\EventDispatcher;
use Resque\Component\Core\Exception\ResqueRuntimeException;
use Resque\Component\Core\Foreman;
use Resque\Component\Core\ResqueEvents;
use Resque\Component\Job\Factory\JobInstanceFactory;
use Resque\Component\Job\ResqueJobEvents;
use Resque\Component\Log\SimpleLogger;
use Resque\Component\Queue\Factory\QueueFactory;
use Resque\Component\Queue\Registry\QueueRegistry;
use Resque\Component\System\StandardSystem;
use Resque\Component\Worker\Factory\WorkerFactory;
use Resque\Component\Worker\Registry\WorkerRegistry;
use Resque\Component\Worker\ResqueWorkerEvents;
use Resque\Redis\Bridge\PredisBridge;
use Resque\Redis\RedisEventListener;
use Resque\Redis\RedisFailure;
use Resque\Redis\RedisQueueRegistryAdapter;
use Resque\Redis\RedisQueueStorage;
use Resque\Redis\RedisStatistic;
use Resque\Redis\RedisWorkerRegistryAdapter;
use RuntimeException;

/**
 * Resque application.
 *
 * A simple class that fires up some workers. It's configuration is based on Resque/Resque 1.x usage
 * of environment variables.
 *
 * You may use APP_INCLUDE to override and or add functionality. If APP_INCLUDE is set to a class name, that class
 * will be initiated and $this injected into it's constructor. If APP_INCLUDE is a file, that class will be
 * included, much like the behaviour of Resque/Resque 1.x.
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
     * @var \Resque\Component\Queue\Storage\QueueStorageInterface
     */
    public $queueStorage;

    /**
     * @var \Resque\Component\Queue\Factory\QueueFactoryInterface
     */
    public $queueFactory;

    /**
     * @var \Resque\Component\Queue\Registry\QueueRegistryAdapterInterface
     */
    public $queueRegistryAdapter;

    /**
     * @var \Resque\Component\Queue\Registry\QueueRegistryInterface
     */
    public $queueRegistry;

    /**
     * @var \Resque\Component\Queue\Model\QueueInterface[]
     */
    public $queues = null;

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
     * @var \Resque\Redis\RedisWorkerRegistryAdapter|\Resque\Component\Worker\Registry\WorkerRegistryAdapterInterface
     */
    public $workerRegistryAdapter;

    /**
     * @var WorkerRegistry
     */
    public $workerRegistry;

    /**
     * @var \Resque\Component\Worker\Model\WorkerInterface[]
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

    /**
     * @var \Resque\Component\System\SystemInterface
     */
    public $system;

    public function __construct()
    {
        $this->eventDispatcher = new EventDispatcher();
        $this->system = new StandardSystem();
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
        $this->setupRedisEvents();
        $this->setupQueueStorage();
        $this->setupQueueFactory();
        $this->setupQueueRegistryAdapter();
        $this->setupQueueRegistry();
        $this->setupQueues();
        $this->setupFailureBackend();
        $this->setupStatisticBackend();
        $this->setupJobInstanceFactory();
        $this->setupWorkerFactory();
        $this->setupWorkerRegistryAdapter();
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
            'queue_blocking' => (bool)getenv('BLOCKING'),
            'queue_interval' => false === getenv('INTERVAL') ? 5 : getenv('INTERVAL'),
            'redis_prefix' => false === getenv('PREFIX') ? 'resque' : getenv('PREFIX'),
            'redis_dsn' => getenv('REDIS_BACKEND'),
            'logging' => (bool)getenv('LOGGING'),
            'verbose' => (bool)getenv('VERBOSE'),
            'very_verbose' => (bool)getenv('VVERBOSE'),
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

                return;
            }

            new $include($this);
        }
    }

    protected function setupLogger()
    {
        if (null === $this->logger) {
            if ($this->config['verbose'] || $this->config['very_verbose']) {
                $this->logger = new SimpleLogger();

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
        }
    }

    protected function setupRedisEvents()
    {
        $redisEventListener = new RedisEventListener($this->redisClient);

        $this->eventDispatcher->addListener(
            ResqueWorkerEvents::BEFORE_FORK_TO_PERFORM,
            array($redisEventListener, 'disconnectFromRedis')
        );
        $this->eventDispatcher->addListener(
            ResqueEvents::PRE_FORK,
            array($redisEventListener, 'disconnectFromRedis')
        );
        $this->eventDispatcher->addListener(
            ResqueWorkerEvents::WAIT_NO_JOB,
            array($redisEventListener, 'disconnectFromRedis')
        );
    }

    protected function setupQueueStorage()
    {
        if (null === $this->queueStorage) {
            $this->queueStorage = new RedisQueueStorage($this->redisClient);
        }
    }

    protected function setupQueueFactory()
    {
        if (null === $this->queueFactory) {
            $this->queueFactory = new QueueFactory($this->queueStorage, $this->eventDispatcher);
        }
    }

    protected function setupQueueRegistryAdapter()
    {
        if (null === $this->queueRegistryAdapter) {
            $this->queueRegistryAdapter = new RedisQueueRegistryAdapter($this->redisClient);
        }
    }

    protected function setupQueueRegistry()
    {
        if (null === $this->queueRegistry) {
            $this->queueRegistry = new QueueRegistry(
                $this->eventDispatcher,
                $this->queueRegistryAdapter,
                $this->queueFactory
            );
        }
    }

    protected function setupQueues()
    {
        if (null === $this->queues) {
            $configQueues = explode(',', $this->config['queues']);

            $queues = array();
            if (in_array('*', $configQueues)) {
                $wildcard = new \Resque\Component\Queue\WildcardQueue($this->queueRegistry);
                $queues[] = $wildcard;
            } else {
                foreach ($configQueues as $configQueue) {
                    $queues[] = $this->queueRegistry->createQueue($configQueue);
                }
            }
            $this->queues = $queues;
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
                $this->queueFactory,
                $this->jobInstanceFactory,
                $this->eventDispatcher,
                $this->system
            );
        }
    }

    protected function setupWorkerRegistryAdapter()
    {
        if (null === $this->workerRegistryAdapter) {
            $this->workerRegistryAdapter = new RedisWorkerRegistryAdapter($this->redisClient);
        }
    }

    protected function setupWorkerRegistry()
    {
        if (null === $this->workerRegistry) {
            $this->workerRegistry = new WorkerRegistry(
                $this->workerRegistryAdapter,
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
        // This method of worker setup requires an array of queues
        if (!is_array($this->queues)) {
            throw new ResqueRuntimeException("Queues not initialized correctly.");
        }

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
        $this->foreman = new Foreman($this->workerRegistry, $this->eventDispatcher, $this->system);
        $this->foreman->setLogger($this->logger);
    }

    protected function queueDescription()
    {
        return implode($this->queues, ',');
    }

    public function work()
    {
        $this->foreman->pruneDeadWorkers();
        $this->foreman->work($this->workers);
        echo sprintf(
            '%d workers attached to the %s queues successfully started.' . PHP_EOL,
            count($this->workers),
            $this->queueDescription()
        );

        echo sprintf(
            'Workers (%s)' . PHP_EOL,
            implode(', ', $this->workers)
        );

        // $this->foreman->wait($this->workers); @todo this is not intended, work out why this is needed.
    }
}
