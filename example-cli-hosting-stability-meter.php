<?php

/**
 * Example of PHP client for the Hosting Stability Meter
 * Performs benchmarks and sends results and some hardware information to https://HostingStabilityMeter.Com
 * You'd rather use the plugin https://hostingstabilitymeter.com/about/wordpress_plugin
 * But if you decided to use this script you should not run it more than once an hour -
 * - more frequent benchmarks results will be ignored
 * @version       1.0
 * @author        HostingStabilityMeter team (welcome@hostingstabilitymeter.com)
 * @copyright (C) 2020 HostingStabilityMeter team (https://hostingstabilitymeter.com)
 * @license       GPLv2 or later: http://www.gnu.org/licenses/gpl-2.0.html
 * @see           https://hostingstabilitymeter.com/about
 */

require_once 'class.hosting-stability-meter-benchmarks.php';

# Initial variables, please be sure they are filled correctly
$hostkey  = '';				// Please fill it manually once and keep it constant between calls
$hostname = gethostname();		// You may fill it manually, please keep it constant between calls
$hostaddr = gethostbyname( $hostname );	// You may fill it manually too
$agent    = 'php-cli-1.0';		// You may fill it manually too, it must be <=32 bytes length

# Values checking
if ( empty( $hostkey ) ) exit( 'You\'d rather fill $hostkey variable before using this script.' );
if ( empty( $hostname ) ) exit( 'You\'d rather fill $hostname variable before using this script.' );
$hostaddr = filter_var( $hostaddr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
if ( $hostaddr === false ) exit( '$hostaddr value should be correct non-local IP address.' );
if ( empty( $agent ) || strlen( $agent ) > 32 ) exit( '$agent value should be not empty and <=32 bytes length.' );

# Array of tests to run: ( test_mame => ( test_duration, test_iterations ) )
$tests = array(
    HostingStabilityMeterBenchmarks::CPU  => array( 1, 1000 ),	// 'cpu'
    HostingStabilityMeterBenchmarks::DISK => array( 1, 1000 ),	// 'disk'
);

# Array for tests results
$stats_array_2_send = array();

foreach ( $tests as $test_name => $test_params ) {
    $stats_result = HostingStabilityMeterBenchmarks::benchmark( $test_name, $test_params[ 0 ], $test_params[ 1 ] );
    if ( $stats_result ) {
	$stats_array_2_send[] = HostingStabilityMeterBenchmarks::create_stats_record_array( array(
	    'timestamp' => time(),
	    'test_name' => $test_name,
	    'test_count' => $stats_result[ 0 ],
	    'duration_seconds' => $stats_result[ 1 ]
	));
    }
}
#var_dump( $stats_array_2_send );

# Host hash to prevent false reports from other hosts for this $hostaddr
$hosthash = md5( $hostname . $hostkey );	// it MUST be md5hash-like and MUST be constant between calls

# Array of all needed POST data
$post_array = HostingStabilityMeterBenchmarks::create_post_array( array(
                    'hostname' => $hostname,
                    'ip'       => $hostaddr,
                    'hosthash' => $hosthash,
                    'agent'    => $agent,
                    'stats'    => json_encode( $stats_array_2_send ),
                    'hostinfo' => json_encode( HostingStabilityMeterBenchmarks::create_server_info_array() )
                    ) );
            
if ( $post_array === false ) exit( 'You\'d rather check your $post_array - it\'s empty.' );
#var_dump( $post_array );

# Send via CURL
$ch = curl_init( HostingStabilityMeterBenchmarks::STATS_URL );
curl_setopt( $ch, CURLOPT_POST, 1 );
curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $post_array ) );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
curl_setopt( $ch, CURLOPT_MAXREDIRS, 2 );
curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
$result = curl_exec( $ch );
curl_close( $ch );

print( $result );
