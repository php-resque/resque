<?php

// Find and initialize Composer
$files = array(
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
    __DIR__ . '/../../../../autoload.php',
    __DIR__ . '/../vendor/autoload.php',
);

foreach ($files as $file) {
    if (file_exists($file)) {
        require_once $file;
        break;
    }
}

if (!class_exists('Composer\Autoload\ClassLoader', false)) {
    die(
        'You need to set up the project dependencies using the following commands:' . PHP_EOL .
        'curl -s http://getcomposer.org/installer | php' . PHP_EOL .
        'php composer.phar install' . PHP_EOL
    );
}

$QUEUE = getenv('QUEUE');
if (empty($QUEUE)) {
    die("Set QUEUE env var containing the list of queues to work.\n");
}

/**
 * REDIS_BACKEND can have simple 'host:port' format or use a DSN-style format like this:
 * - redis://user:pass@host:port
 *
 * Note: the 'user' part of the DSN URI is required but is not used.
 */
$REDIS_BACKEND = getenv('REDIS_BACKEND');

$BLOCKING = getenv('BLOCKING') !== false;

$interval = 5;
$INTERVAL = getenv('INTERVAL');
if (!empty($INTERVAL)) {
    $interval = $INTERVAL;
}

$count = 1;
$COUNT = getenv('COUNT');
if (!empty($COUNT) && $COUNT > 1) {
    $count = $COUNT;
}

$PREFIX = getenv('PREFIX');
if (!empty($PREFIX)) {
    $logger->log(Psr\Log\LogLevel::INFO, 'Prefix set to {prefix}', array('prefix' => $PREFIX));
    Resque_Redis::prefix($PREFIX);
}

//if ($count > 1) {
//    for ($i = 0; $i < $count; ++$i) {
//        $pid = Resque::fork();
//        if ($pid == -1) {
//            $logger->log(Psr\Log\LogLevel::EMERGENCY, 'Could not fork worker {count}', array('count' => $i));
//            die();
//        } // Child, start the worker
//        else {
//            if (!$pid) {
//                $queues = explode(',', $QUEUE);
//                $worker = new Resque_Worker($queues);
//                $worker->setLogger($logger);
//                $logger->log(Psr\Log\LogLevel::NOTICE, 'Starting worker {worker}', array('worker' => $worker));
//                $worker->work($interval, $BLOCKING);
//                break;
//            }
//        }
//    }
//} // Start a single worker
//else {
    $queues = explode(',', $QUEUE);

    $foreman = new \Resque\Foreman();

    $worker = new \Resque\Worker(
        array(
            new \Resque\Queue('foo')
        )
    );

    $foreman
        ->registerWorker($worker);

  //  $worker->setLogger($logger);

  //  $logger->log(Psr\Log\LogLevel::NOTICE, 'Starting worker {worker}', array('worker' => $worker));
  //  $worker->work($interval, $BLOCKING);

    $foreman->work();
//}
