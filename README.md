# Twitdiff

## What?
This is a quick and dirty PHP script that retrieves your current Twitter followers, grabs some basic info about them and saves it to a file.

Cron it, and it will send you an email each time it runs to let you know who started and stopped following you.

Uses the Twitter 1.1 API with oAuth so you'll need to create an application and grant yourself some access keys. 
You can do that here: [https://dev.twitter.com/apps](https://dev.twitter.com/apps)

## Setup

The following variables need to be set in config.php (move config.php.example to config.php) 

``$username`` - Your Twitter username

``$difffile_filename`` - A path on disk to store the diff file. Yesterday's will be stored at $difffile_filename-yesterday

`$countfile_filename` - A path on disk to store the count file. 

``$email_recipient`` - The email address of the person who will receive the report

``$consumer_key``, ``$consumer_secret`` - Your Twitter oAuth consumer key and secret

``$oauth_access_token``, ``$oauth_access_token_secret`` - Your Twitter oAuth access token and token secret


Run once to ensure it succeeds and to populate the first day's data.

Then cron it something like this:
``0 7 * * * /usr/bin/php /home/yourname/twitdiff/go.php >> /var/log/twitdiff.log 2>&1``

You will receive a daily report to your email address with the differences. 


## Bugs?
Probably. Patches welcome :)
