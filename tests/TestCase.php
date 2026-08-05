<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite') {
            \Illuminate\Support\Facades\DB::connection()->getPdo()->sqliteCreateFunction('TRANSLATE', function ($string, $from, $to) {
                $fromArr = mb_str_split($from);
                $toArr = mb_str_split($to);
                $map = [];
                for ($i = 0; $i < count($fromArr); $i++) {
                    if (isset($toArr[$i])) {
                        $map[$fromArr[$i]] = $toArr[$i];
                    }
                }
                return strtr($string, $map);
            }, 3);
        }
    }}
