<?php namespace Citco;

use Citco\Exception\MaxJobAgeExceededException;
use Citco\Exception\MaxJobsExceededException;
use Citco\Exception\RateOfRiseExceededException;
use Pheanstalk\Pheanstalk;

class Monistalkd {

	private $connection;
	private $maxJobs;
	private $rateOfRise;
	private $maxJobAge;

	public function __construct(Pheanstalk $connection)
	{
		$this->connection = $connection;
	}

	public function setRateOfRise($rateOfRise)
	{
		$this->rateOfRise = $rateOfRise;
	}

	public function setMaxJobs($maxJobs)
	{
		$this->maxJobs = $maxJobs;
	}

	public function setMaxJobAge($maxJobAge)
	{
		$this->maxJobAge = $maxJobAge;
	}

	public function check()
	{
		$tubes = $this->connection->listTubes();

		foreach ($tubes as $tube)
		{
			$this->checkMaxJobs($tube);
			$this->checkMaxJobAge($tube);
			$this->checkRateOfRise($tube);
		}
	}

	public function checkMaxJobs($tube)
	{
		if ($this->maxJobs)
		{
			$stat = $this->connection->statsTube($tube);

			if ((int) $stat['total-jobs'] >= $this->maxJobs)
			{
				throw new MaxJobsExceededException('Maximum number of jobs: ' . $stat['total-jobs'] . ' exceeded in tube: ' . $tube);
			}
		}
	}

	public function checkRateOfRise($tube)
	{
		if ($this->rateOfRise)
		{
			$stat = $this->connection->statsTube($tube);

			sleep(5);

			$newStat = $this->connection->statsTube($tube);

			$rateOfRise = ($newStat['total-jobs'] - $stat['total-jobs']) / 5;

			if ($rateOfRise >= $this->rateOfRise)
			{
				throw new RateOfRiseExceededException('Rate of rise: ' . $this->rateOfRise . ' jobs per second exceeded in tube: ' . $tube);
			}
		}
	}

	public function checkMaxJobAge($tube)
	{
		if ($this->maxJobAge)
		{
			$job = $this->connection->peekReady($tube);

			$stat = $this->connection->statsJob($job);

			if ($stat['age'] >= $this->maxJobAge)
			{
				throw new MaxJobAgeExceededException('Max job age: ' . $this->maxJobAge . ' seconds exceeded in tube: ' . $tube);
			}
		}
	}
}
