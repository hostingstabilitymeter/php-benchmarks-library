<?php

/**
 * Class HostingStabilityMeterBenchmarks
 * Base class for HostingStabilityMeter.com clients - plugins, modules, scripts, etc.
 * @version       1.0
 * @author        HostingStabilityMeter team (welcome@hostingstabilitymeter.com)
 * @copyright (C) 2020 HostingStabilityMeter team (https://hostingstabilitymeter.com)
 * @license       GPLv2 or later: http://www.gnu.org/licenses/gpl-2.0.html
 * @see           https://github.com/
 */

class HostingStabilityMeterBenchmarks {
        const CPU  = 'cpu';
        const DISK = 'disk';
        const DB   = 'db';

        const STATS_URL = 'https://stat.hostingstabilitymeter.com/slurp_stats.php';
        
        const TEST_DURATION_DEF  = 1;  // in seconds
	const TEST_COUNT_DEF     = 100;
        const TEST_METHOD_PREFIX = 'test_';

        const DUMMY_TABLE_NAME = 'hostingstabilitymeter_dummy';

	const PROC_CPUINFO = '/proc/cpuinfo';
	const PROC_MEMINFO = '/proc/meminfo';

        const POST_FIELD_HOSTNAME = 'hostname';
        const POST_FIELD_IP       = 'ip';
        const POST_FIELD_HOSTHASH = 'hosthash';
        const POST_FIELD_AGENT    = 'agent';
        const POST_FIELD_STATS    = 'stats';
        const POST_FIELD_HOSTINFO = 'hostinfo';

	/**
	 * Calls test function N times no more than M seconds, calculates elapsed time and calls
	 * @static
	 * @return  Array [ int calls number, float elapsed time in seconds ] | false
	 */
	public static function benchmark( $test_name, $test_duration = self::TEST_DURATION_DEF, $test_count = self::TEST_COUNT_DEF, $test_params_array = NULL) {
		if ( ! self::is_test_name_ok( $test_name ) ) {
			return false;
		}
		$test_function = self::TEST_METHOD_PREFIX . $test_name;
		$duration = 0;
		$iteration = 1;
		$time_start = microtime( true );
		while ( $duration < $test_duration && $iteration < $test_count ) {
		    if ( isset( $test_params_array ) ) {
                	    self::$test_function( $test_params_array );
		    } else {
                	    self::$test_function();
		    }
		    $duration = microtime( true ) - $time_start;
		    $iteration++;
                }
		return array( $iteration, $duration );
        }

        /**
         * Creates post fields array
	 * @static
	 * @param  Array [ 'hostname' => string , 'ip' => string , 'hosthash' => string , 'agent' => string ,  'stats' => JSON string ,  'hostinfo' => JSON string ]
         * @return  Array of needed fields for POST request
         */
        public static function create_post_array( $params_array ) {
            if (
                empty( $params_array[ 'hostname' ] )
                || empty( $params_array[ 'ip' ] )
                || empty( $params_array[ 'hosthash' ] )
                || empty( $params_array[ 'agent' ] )
                || empty( $params_array[ 'stats' ] )
                || empty( $params_array[ 'hostinfo' ] )
                || ! filter_var( $params_array[ 'ip' ], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )
            ) {
                return false;
            }
            return array(
                self::POST_FIELD_HOSTNAME => $params_array[ 'hostname' ],
                self::POST_FIELD_IP       => $params_array[ 'ip' ],
                self::POST_FIELD_HOSTHASH => $params_array[ 'hosthash' ],
                self::POST_FIELD_AGENT    => $params_array[ 'agent' ],
                self::POST_FIELD_STATS    => $params_array[ 'stats' ],
                self::POST_FIELD_HOSTINFO => $params_array[ 'hostinfo' ]
            );
        }

        /**
         * Creates stats record array
	 * @static
	 * @param  Array [ 'timestamp' => int , 'test_name' => string , 'test_count' => int , 'duration_seconds' => float ]
         * @return  Array of stats record values
         */
        public static function create_stats_record_array( $params_array ) {
            if (
                empty( $params_array[ 'timestamp' ] )
                || empty( $params_array[ 'test_name' ] )
                || empty( $params_array[ 'test_count' ] )
                || empty( $params_array[ 'duration_seconds' ] )
                || ! is_int( $params_array[ 'timestamp' ] )
                || intval( $params_array[ 'timestamp' ] ) < 0
                || ! is_int( $params_array[ 'test_count' ] )
                || intval( $params_array[ 'test_count' ] ) < 1
                || ! is_float( $params_array[ 'duration_seconds' ] )
                || floatval( $params_array[ 'duration_seconds' ] ) < 0
                || ! self::is_test_name_ok( $params_array[ 'test_name' ] )
            ) {
                return false;
            }
            return array(
                $params_array[ 'timestamp' ],
                $params_array[ 'test_name' ],
                $params_array[ 'test_count' ],
                $params_array[ 'duration_seconds' ]
            );
        }

        /**
         * Creates array with server info - CPU, RAM and part of phpinfo
	 * @static
         * @return  Array with 'php', 'mem', 'cpu' keys
         */
	public static function create_server_info_array() {
	    $result = array();
	    $result[ 'php'][ 'os' ] = PHP_OS;
	    $result[ 'php'][ 'uname' ] = array(
		'r' => php_uname( 'r' ),
		'v' => php_uname( 'v' ),
		'm' => php_uname( 'm' )
	    );

	    if ( ! stristr( PHP_OS, 'win' ) ) {

		if ( is_readable( self::PROC_MEMINFO ) ) {
//MemTotal:        7852640 kB
            	    $stats = @file_get_contents( self::PROC_MEMINFO );
            	    if ( $stats !== false ) {

                	$stats = explode( PHP_EOL, $stats );
                	foreach ( $stats as $stat_line ) {
                    	    $stat_array = explode( ':', trim( $stat_line) );
                    	    if ( count( $stat_array ) == 2 ) {
				$tag = strtolower( trim( $stat_array[ 0 ] ) );
				switch ( $tag ) {
				    case 'memtotal' :
					$result[ 'mem' ][ 'total' ] = trim( $stat_array[ 1 ] );
					break;
				}
                    	    }
			    if ( isset( $result[ 'mem' ][ 'total' ] ) ) {
				break;
			    }
                	}
			unset( $stats, $stat_array, $stat_line );
            	    }
    		}

		if ( is_readable( self::PROC_CPUINFO ) ) {
//vendor_id       : GenuineIntel
//cpu family      : 6
//model           : 58
//model name      : Intel(R) Core(TM) i5-3550S CPU @ 3.00GHz
//cpu cores       : 4
//bogomips        : 5986.62
            	    $stats = @file_get_contents( self::PROC_CPUINFO );
            	    if ( $stats !== false ) {

                	$stats = explode( PHP_EOL, $stats );
                	foreach ( $stats as $stat_line ) {
                    	    $stat_array = explode( ':', trim( $stat_line) );
                    	    if ( count( $stat_array ) == 2 ) {
				$tag = strtolower( trim( $stat_array[ 0 ] ) );
				switch ( $tag ) {
				    case 'vendor_id' :
					$result[ 'cpu' ][ 'vendor_id' ] = trim( $stat_array[ 1 ] );
					break;
				    case 'cpu family' :
					$result[ 'cpu' ][ 'family' ] = trim( $stat_array[ 1 ] );
					break;
				    case 'model' :
					$result[ 'cpu' ][ 'model' ] = trim( $stat_array[ 1 ] );
					break;
				    case 'model name' :
					$result[ 'cpu' ][ 'model_name' ] = trim( $stat_array[ 1 ] );
					break;
				    case 'cpu cores' :
					$result[ 'cpu' ][ 'cores' ] = trim( $stat_array[ 1 ] );
					break;
				    case 'bogomips' :
					$result[ 'cpu' ][ 'bogomips' ] = trim( $stat_array[ 1 ] );
					break;
				}
                    	    }
			    if ( isset( $result[ 'cpu' ][ 'vendor_id' ] ) && isset( $result[ 'cpu' ][ 'family' ] ) && isset( $result[ 'cpu' ][ 'model' ] ) && isset( $result[ 'cpu' ][ 'model_name' ] ) && isset( $result[ 'cpu' ][ 'cores' ] )  && isset( $result[ 'cpu' ][ 'bogomips' ] ) ) {
				break;
			    }
                	}
			unset( $stats, $stat_array, $stat_line );
            	    }
    		}
	    }
	    return( $result );
	}

        /**
	 * Inner function - Makes CPU test
	 * @static
	 */
	private static function test_cpu() {
		$arr = array();
		$len = 1024;
		$delta = 3.14159;
		for ( $i = 0; $i < $len; $i++ ) {
			$arr[] = $i * $delta;
		}
		$str = '';
		if ( function_exists( 'json_encode' ) ) {
                        $str .= json_encode( $arr );
                } else {
			$str .= serialize( $arr );
		}
		unset( $arr );
		unset( $str );
	}

	/**
	 * Inner function - Makes disk test
	 * @static
	 */
	private static function test_disk() {
		$len = 1024 * 1024;
		$temp = tmpfile();
		fwrite( $temp, str_repeat('Z', $len ) );
		fseek( $temp, 1024 );
		$s = fread( $temp, 1024 );
		fclose( $temp );
	}
        
	/**
	 * Inner function - Makes database test
	 * @static
	 * @param  Array [ 'prefix' => <dummy table prefix>, 'query_object' => <dbh object>, 'query_method' => <function name> ]
	 */
	private static function test_db( $params_array ) {
            $table_name = ( isset( $params_array[ 'prefix' ] ) ? $params_array[ 'prefix' ] : '' ) . self::DUMMY_TABLE_NAME;
            $dummy_data = str_repeat ( 'Z' , 256 );
            $str_data = sprintf( '("%s")', $dummy_data );
            for ( $i = 1; $i < 63; $i++) {
                $str_data .= sprintf( ',("%s")', $dummy_data );
            }
            call_user_func(array( $params_array[ 'query_object' ], $params_array[ 'query_method' ] ), "CREATE TABLE if not exists $table_name (dummydata text NOT NULL DEFAULT '')" );
            call_user_func(array( $params_array[ 'query_object' ], $params_array[ 'query_method' ] ), "insert into $table_name (dummydata) values " . $str_data . ";" );
            call_user_func(array( $params_array[ 'query_object' ], $params_array[ 'query_method' ] ), "select * from $table_name;" );
            call_user_func(array( $params_array[ 'query_object' ], $params_array[ 'query_method' ] ), "update $table_name set dummydata = '" . __CLASS__ . "';" );
            call_user_func(array( $params_array[ 'query_object' ], $params_array[ 'query_method' ] ), "delete from $table_name;" );
            call_user_func(array( $params_array[ 'query_object' ], $params_array[ 'query_method' ] ), "DROP TABLE if exists $table_name;" );
        }

	/**
	 * Inner function - Checks is test name good
	 * @static
	 * @param  String test name, must be one of self::CPU, self::DB, self::DISK
	 */
	private static function is_test_name_ok( $test_name ) {
            if (
		$test_name === self::CPU
                || $test_name === self::DB
                || $test_name === self::DISK
	    ) {
		return true;
	    } else {
		return false;
	    }
	}
}
