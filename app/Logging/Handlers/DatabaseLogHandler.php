<?php

namespace App\Logging\Handlers;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use DB;

class DatabaseLogHandler extends AbstractProcessingHandler
{
    public function __construct($level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        DB::table('logs')->insert([
            'level' => $record['level_name'],
            'channel' => $record['channel'],
            'message' => $record['message'],
            'context' => json_encode($record['context']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
