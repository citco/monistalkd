<?php namespace Citco;

use Citco\Exception\MaxJobAgeExceededException;
use Citco\Exception\MaxJobsExceededException;
use Citco\Exception\RateOfRiseExceededException;
use Pheanstalk\Exception\ServerException;
use Pheanstalk\Pheanstalk;

class Monistalkd {

	private $connection;
	private $maxJobs;
	private $rateOfRise;
	private $maxJobAge;

	const ROR_CHECK_SECONDS = 5;

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

			sleep(static::ROR_CHECK_SECONDS);

			$newStat = $this->connection->statsTube($tube);

			$rateOfRise = ($newStat['total-jobs'] - $stat['total-jobs']) / static::ROR_CHECK_SECONDS;

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
			try
			{

				$job = $this->connection->peekReady($tube);
				$stat = $this->connection->statsJob($job);
			}
			catch (ServerException $e)
			{
				if (stripos($e->getMessage(), 'NOT_FOUND') !== false)
				{
					$stat = null;
				}
				else
				{
					throw $e;
				}
			}

			if ($stat && $stat['age'] >= $this->maxJobAge)
			{
				throw new MaxJobAgeExceededException('Max job age: ' . $this->maxJobAge . ' seconds exceeded in tube: ' . $tube);
			}
		}
	}
}
