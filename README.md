# monistalkd
Beanstalkd monitor package

How to use the `php console/monitor.php` command:

This command will check the health of beanstalk and send an email alert to the email address set in `.env` configuration.

First you need to setup the `.env` file, there is a sample in `.env.example`: 
```
HOST=127.0.0.1
PORT=11300

MAX_JOBS=
RATE_OF_RISE=
MAX_JOB_AGE=50000

MAIL_FROM=error@example.com
MAIL_TO=error@example.com
MAIL_SUBJECT='Queue Error'

SMTP_HOST=smtp.mailtrap.io
SMTP_PORT=25
SMTP_USERNAME=
SMTP_PASSWORD=

TIMEZONE=UTC
```
If you leave `MAX_JOBS` , `RATE_OF_RISE` or `MAX_JOB_AGE` empty that check will be ignored.

All of the checks will be done on all of the available tubes.

`MAX_JOBS` is the maximum number of jobs in any tube at any time

`RATE_OF_RISE` is number of jobs added per second

`MAX_JOB_AGE` is the maximum age (in seconds) that any job in any tube can have.

After setting the configation run `composer install` to install the dependencies.

and you can run the command like: `php monitor.php > ./results.log`


