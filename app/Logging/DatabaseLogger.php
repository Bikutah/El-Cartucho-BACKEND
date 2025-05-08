<?php

namespace App\Logging;

use Monolog\Logger;
use App\Logging\Handlers\DatabaseLogHandler;

class DatabaseLogger
{
    public function __invoke(array $config)
    {
        $logger = new Logger('database');
        $logger->pushHandler(new DatabaseLogHandler());
        return $logger;
    }
}
