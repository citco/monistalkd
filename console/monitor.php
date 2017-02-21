<?php

require __DIR__ . '/../vendor/autoload.php';

$configs = new \Dotenv\Dotenv(__DIR__);
$configs->load();

date_default_timezone_set(getenv('TIMEZONE'));

$host = getenv('HOST');
$port = getenv('PORT');
$maxJobs = getenv('MAX_JOBS');
$rateOfRise = getenv('RATE_OF_RISE');
$maxJobAge = getenv('MAX_JOB_AGE');

$connection = new \Pheanstalk\Pheanstalk($host, $port);

$monitor = new \Citco\Monistalkd($connection);
$monitor->setMaxJobs($maxJobs);
$monitor->setMaxJobAge($maxJobAge);
$monitor->setRateOfRise($rateOfRise);

try
{
	$monitor->check();
}
catch (\Citco\Exception\MonitorException $e)
{
	$subject = getenv('MAIL_SUBJECT');
	$from = array(getenv('MAIL_FROM'));
	$to = array(
		getenv('MAIL_TO'),
	);

	$text = $e->getMessage();
	$html = $e->getMessage();

	$transport = Swift_SmtpTransport::newInstance(getenv('SMTP_HOST'), getenv('SMTP_PORT'));
	$transport->setUsername(getenv('SMTP_USERNAME'));
	$transport->setPassword(getenv('SMTP_PASSWORD'));
	$swift = Swift_Mailer::newInstance($transport);

	$message = new Swift_Message($subject);
	$message->setFrom($from);
	$message->setBody($html, 'text/plain');
	$message->setTo($to);

	$swift->send($message);
}
