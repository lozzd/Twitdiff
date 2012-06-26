# Twitdiff

## What? 
This is a quick and dirty PHP script that retrieves your current Twitter followers, grabs some basic info about them and saves it to a file.

Cron it, and it will send you an email each time it runs to let you know who started and stopped following you. 

It won't work if your Twitter is private. 

## Setup 

The following variables need to be set:

``$username`` - Your Twitter username

``$difffile_filename`` - A path on disk to store the diff file. Yesterday's will be stored at $difffile_filename-yesterday

``$email_recipient`` - The email address of the person who will receive the report


Then cron it something like this:
``0 7 * * * /usr/bin/php /home/yourname/twitdiff/go.php >> /var/log/twitdiff.log 2>&1``

## Bugs?
Probably. Patches welcome :)
