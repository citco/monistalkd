<?php namespace Citco;

use Citco\Exception\MaxJobAgeExceededException;
use Citco\Exception\MaxJobsExceededException;
use Citco\Exception\RateOfRiseExceededException;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;

class MonistalkdTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Monistalkd
	 */
	private $monitor;

	public function testMaxAge()
	{
		$connection = $this->getConnection();
		$connection->shouldReceive('statsJob')->andReturn(['age' => 100]);
		$job = \Mockery::mock(Job::class);
		$job->shouldReceive('getData');
		$connection->shouldReceive('peekReady')->andReturn($job);

		$this->initHandler($connection);
		$this->monitor->setMaxJobAge(10);

		$this->expectException(MaxJobAgeExceededException::class);

		$this->monitor->checkMaxJobAge('test');
	}

	public function testMaxJobs()
	{
		$connection = $this->getConnection();
		$connection->shouldReceive('statsTube')->andReturn(['total-jobs' => 100]);
		$this->initHandler($connection);
		$this->monitor->setMaxJobs(10);

		$this->expectException(MaxJobsExceededException::class);

		$this->monitor->checkMaxJobs('test');
	}

	public function testRateOfRise()
	{
		$connection = $this->getConnection();
		$connection->shouldReceive('statsTube')->twice()->andReturn(['total-jobs' => 10], ['total-jobs' => 100]);
		$this->initHandler($connection);
		$this->monitor->setRateOfRise(1);

		$this->expectException(RateOfRiseExceededException::class);

		$this->monitor->checkRateOfRise('test');
	}

	private function getConnection()
	{
		$handler = \Mockery::mock(Pheanstalk::class);

		return $handler;
	}

	private function initHandler($connection)
	{
		$this->monitor = new Monistalkd($connection);
	}
}
