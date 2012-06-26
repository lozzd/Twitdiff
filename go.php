<?php

$username = "lozzd";
$difffile_filename = "/home/lozzd/twitter_diff.txt";
$email_recipient = "laurie@denness.net";

logline("Starting run. ");
logline("Rotating difffile.. ");
rename($difffile_filename, $difffile_filename. "-yesterday");
logline("Opening diff file... ");
$difffile = fopen($difffile_filename,'w');

logline("Getting list of followers from Twitter...");
if (!$followers_json = file_get_contents("http://api.twitter.com/1/followers/ids.json?screen_name={$username}")) {
	logline("Couldn't get followers, sorry. ");
	exit(2);
}

if(!$followers = json_decode($followers_json)) {
	logline("It seems that wasn't a JSON response. Sorry. ");
	exit(2);
}

$count_followers = count($followers->ids);
logline("You have {$count_followers} followers as of ". date("r"));
$email_body = "You have {$count_followers} followers as of ". date("r"). "\n";

// The Twitter API only lets you get infor for 100 users at once. so chunk into that size. 
foreach ($followers->ids as $this_id) {
	$user_array[$this_id] = $this_id;
}
$chunks = array_chunk($user_array, 100, true);
$num_chunks = count($chunks);

// Get the data from Twitter
logline("Getting data for {$count_followers} followers");

$i=1;

foreach($chunks as $this_chunk) {
	logline("Getting chunk {$i} of {$num_chunks}"); 
	$data_chunk[] = json_decode(file_get_contents("https://api.twitter.com/1/users/lookup.json?user_id=". implode($this_chunk, ",")));
	$i++;
}

foreach ($data_chunk as $this_chunk) {
	foreach($this_chunk as $this_user) {
		$user_data[$this_user->id] = $this_user;
	}	
}
ksort($user_data);
// By this point we now have an array called $user_data with the current followers and their data. 

logline("Writing todays followers to file...");
foreach ($user_data as $this_user) {
	$sanitised_desc = str_replace(array("\r", "\r\n", "\n"), '', $this_user->description);
	fwrite($difffile, "({$this_user->id}) {$this_user->screen_name} - {$sanitised_desc}\n");
}
fclose($difffile);

// Diff the two files and send it via email

logline("Running diff...");

$diff = `diff -U 0 {$difffile_filename}-yesterday {$difffile_filename}`;

$email_body .= $diff;

logline("Emailing diff... ");

mail($email_recipient, "Twitdiff for " . date("l d F Y") , $email_body);

logline("Completed. ");


// A nicer way to print console messages
function logline($message) {
        echo date(DATE_RFC822) . " " . $message . "\n";
}

?>
