<?php

namespace Reborn\Mongo;

class Bench
{
	protected $start;

	protected $end;

	protected $memUsage;

	public function start()
	{
		$this->start = microtime(true);
	}

	public function stop()
	{
		$this->end = microtime(true);
		$this->memUsage = memory_get_usage(true);
	}

	public function howLong()
	{
		$diff = $this->end - $this->start;
		return number_format($diff, 4).' ms';
	}

	public function memory()
	{
		$mem = (round($this->memUsage / pow(1024, 2), 3)." MB");
		return $mem;
	}
}
