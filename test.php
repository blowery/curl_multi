<?php

/**
 * A test of using curl_multi_* and friends
 */

// this assumes we have a server running somewhere with some known endpoints
// /200 returns an HTTP 200, as quickly as it can
// /200?wait=10 returns an HTTP 200 after waiting 10s. 10 can be anything, in seconds
// /302?to=<url> returns a 302 to a new URL
// /301?to=<url> returns a 301 to a new URL
// /304 returns an HTTP 304


$curl_handles = array();
$master = curl_multi_init();

$urls = [
	'http://localhost:3001/200',
	'http://localhost:3001/200?wait=5',
	'http://localhost:3001/301',
	'http://localhost:3001/302',
	'http://localhost:3001/304',
	'http://localhost:3001/404',
	'http://localhost:3002/blah',
	'http://localhost:3001/slowloris',
	'http://localhost:3001/500',
];

for( $i = 0; $i < count( $urls ); $i++ ) {
	$handle = curl_init( $urls[$i] );
	curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $handle, CURLOPT_FOLLOWLOCATION, true );
	curl_multi_add_handle( $master, $handle );
	$curl_handles[$i] = $handle;
}

echo "starting\n";
do {
	$mrc = curl_multi_exec( $master, $running );
	echo "new state " . curl_multi_strerror( $mrc ) . "\n";
	echo "startup\n";
} while ( $mrc == CURLM_CALL_MULTI_PERFORM );

echo "running\n";
while( $running && $mrc == CURLM_OK ) {

	//echo "selecting\n";
	$sel = curl_multi_select( $master, 5.0 );
	//echo "selected $sel\n";

	if ( $sel === -1 ) {
		echo "sleeping\n";
		usleep( 1000 * 1 );
	}

	$old_running = $running;
	do {
		$mrc = curl_multi_exec( $master, $running );
	} while( $mrc == CURLM_CALL_MULTI_PERFORM );

	// echo "new state " . curl_multi_strerror( $mrc ) . "\n";
	if ( $old_running != $running ) {
		echo "state change!\n";
		while ( ( $info = curl_multi_info_read( $master ) ) !== false ) {
			$handle_info = (object) curl_getinfo( $info['handle'] );
			$res         = curl_strerror( $info['result'] );
			echo "\nresult: $res\n";
			echo "info: HTTP $handle_info->http_code: $handle_info->url\n";
			//var_dump( $handle_info );
			echo "\n";
		};
	}
}

echo "done\n";
