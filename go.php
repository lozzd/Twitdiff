<?php

include_once 'config.php';


logline("Starting run. ");
logline("Rotating difffile.. ");
rename($difffile_filename, $difffile_filename. "-yesterday");
rename($countfile_filename, $countfile_filename. "-yesterday");
logline("Opening diff file... ");
$difffile = fopen($difffile_filename,'w');
$countfile = fopen($countfile_filename,'w');



logline("Getting list of followers from Twitter...");

if (!$followers_json = doOAuthCall("https://api.twitter.com/1.1/followers/ids.json", array("screen_name" => $username))) {
	logline("Couldn't get followers, sorry. ");
	exit(2);
}

if(!$followers = json_decode($followers_json)) {
    logline("It seems that wasn't a JSON response. Sorry. ");
    exit(2);
}

$count_followers = count($followers->ids);
fwrite($countfile, $count_followers);
logline("You have {$count_followers} followers as of ". date("r"));
$email_body = "You have {$count_followers} followers as of ". date("r"). "\n";

$yesterday_count = file_get_contents($countfile_filename. "-yesterday");
$count_diff = $count_followers - $yesterday_count;

$email_body .= "Difference to yesterday: {$count_diff}\n\n";

// The Twitter API only lets you get info for 100 users at once. so chunk into that size. 
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
    if(!$data_chunk[] = json_decode(doOAuthCall("https://api.twitter.com/1.1/users/lookup.json", array("user_id" => implode($this_chunk, ","), "include_entities" => "false")))) {
        logline("Getting chunk failed, retrying...");
        if(!$data_chunk[] = json_decode(doOAuthCall("https://api.twitter.com/1.1/users/lookup.json", array("user_id" => implode($this_chunk, ","), "include_entities" => "false")))) {
            logline("Getting chunk failed twice, sorry.");
        }
    }
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
fclose($countfile);

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

function buildBaseString($baseURI, $method, $params) {
    $r = array();
    ksort($params);
    foreach($params as $key=>$value){
        $r[] = "$key=" . rawurlencode($value);
    }
    return $method."&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
}

function buildAuthorizationHeader($oauth) {
    $r = 'Authorization: OAuth ';
    $values = array();
    foreach($oauth as $key=>$value)
        $values[] = "$key=\"" . rawurlencode($value) . "\"";
    $r .= implode(', ', $values);
    return $r;
}

function doOAuthCall($url, array $params) {
    global $consumer_key, $oauth_access_token, $consumer_secret, $oauth_access_token_secret; 

    // Generate the oAuth array
    $oauth = array( 'oauth_consumer_key' => $consumer_key,
        'oauth_nonce' => time(),
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_token' => $oauth_access_token,
        'oauth_timestamp' => time(),
        'oauth_version' => '1.0'
    );
    $oauth = $oauth + $params;
    $composite_key = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);


    $base_info = buildBaseString($url, 'GET', $oauth);

    $oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
    $oauth['oauth_signature'] = $oauth_signature;

    $header = array(buildAuthorizationHeader($oauth), 'Expect:');
    $options = array( CURLOPT_HTTPHEADER => $header,
        CURLOPT_HEADER => false,
        CURLOPT_URL => "{$url}?" . http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
    );

    $feed = curl_init();
    curl_setopt_array($feed, $options);
    if (!$json = curl_exec($feed)) {
        return false;
    }
    curl_close($feed);

    return $json;
}

?>
