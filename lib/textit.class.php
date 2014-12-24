<?php

class TextIt_Exception extends Exception {}

class TextIt {

		const API_VERSION = '1.0';    
    const END_POINT = 'https://api.textit.in/api/v1';
    
    var $api;
    var $output;
		var $endpoints = array(
			'contacts', //To list or modify contacts.
			'fields', //To list or modify contact fields.
			'messages', //To list and create new SMS messages.
			'relayers', //To list, create and remove new Android phones.
			'calls', //To list incoming, outgoing and missed calls as reported by the Android phone.
			'flows', //To list active flows
			'runs', //To list or start flow runs for contacts
			'campaigns', //To list or modify campaigns on your account.
			'events', //To list or modify campaign events on your account.
			'boundaries' //To retrieve the geometries of the administrative boundaries on your account.
		);
		
		function __construct($api) {
       if ( empty($api) ) { 
				throw new TextIt_Exception('Invalid API token');
			 } else {
				$this->api = $api;
			 }
    }
		
		 /**
	 * Work horse. Every API call use this function to actually make the request to TextIt's servers.
	 *
	 * @link https://textit.in/api/v1
	 *
	 * @param string $method API method name
	 * @param array $args query arguments
	 * @param string $http GET or POST request type
	 * @return array|TextIt_Exception
	 */
	function request($method, $args = array(), $http = 'POST') {
		
		if (!in_array($method,$this->endpoints)) {
			throw new TextIt_Exception('Unknown method');
		}

		if( !isset($args['key']) )
			$args['key'] = $this->api;
			
		$key = $args['key'];
		unset($args['key']);

		$url = self::END_POINT . "/{$method}.json";

		switch ($http) {

			case 'GET':
				//some distribs change arg sep to &amp; by default
				$sep_changed = false;
				if (ini_get("arg_separator.output")!="&"){
						$sep_changed = true;
						$orig_sep = ini_get("arg_separator.output");
						ini_set("arg_separator.output", "&");
				}

				$url .= count($args) ? '?' . http_build_query($args) : '';
				
				if ($sep_changed){
						ini_set("arg_separator.output", $orig_sep);
				}
				$response = $this->http_request($url, $key, $args, 'GET');
				break;

			case 'POST':
				$response = $this->http_request($url, $key, $args, 'POST');
				break;

			default:
				throw new TextIt_Exception('Unknown request type');
		}

		$response_code  = $response['header']['http_code'];
		$body           = $response['body'];

		$body = json_decode($body, true);

		if( 201 == $response_code ) {
			return $body;
		} else {
			error_log("Wordpress TextIt SDK Error: " . print_r($response,1));
			throw new TextIt_Exception( "Wordpress TextIt SDK Error: {$body['code']}: {$body['message']}", $response_code);
		}
	}
	
	function http_request($url, $key, $fields = array(), $method = 'POST') {

    if ( !in_array( $method, array('POST','GET') ) ) $method = 'POST';
				
				$method = $method == 'POST' ? 1 : 0;

        //some distribs change arg sep to &amp; by default
        $sep_changed = false;
        if (ini_get("arg_separator.output")!="&"){
            $sep_changed = true;
            $orig_sep = ini_get("arg_separator.output");
            ini_set("arg_separator.output", "&");
        }

        $fields = is_array($fields) ? json_encode($fields) : $fields;

        if ($sep_changed) {
            ini_set("arg_separator.output", $orig_sep);
        }
        
        $useragent = WordpressTextItSDK::getUserAgent();
        
        if( function_exists('curl_init') && function_exists('curl_exec') ) {
        
            if( !ini_get('safe_mode') ){
                set_time_limit(2 * 60);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, $method);
						if (!$method) {
							curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
						}
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);	// @Bruno Braga:
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);	//	Thanks for the hack!
						curl_setopt($ch, CURLOPT_USERAGENT,$useragent);
						curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Token ' . $key, 'Content-Type: application/json'));
						curl_setopt($ch, CURLOPT_HEADER, false);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2 * 60 * 1000);
                
                $response   = curl_exec($ch);
                $info       = curl_getinfo($ch);
                $error      = curl_error($ch);
                
            curl_close($ch);
            
        } elseif( function_exists( 'fsockopen' ) ) {
	        $parsed_url = parse_url($url);

	        $host = $parsed_url['host'];
	        if ( isset($parsed_url['path']) ) {
		        $path = $parsed_url['path'];
	        } else {
		        $path = '/';
	        }

            $params = '';
            if (isset($parsed_url['query'])) {
                $params = $parsed_url['query'] . '&' . $fields;
            } elseif ( trim($fields) != '' ) {
                $params = $fields;
            }

	        if (isset($parsed_url['port'])) {
		        $port = $parsed_url['port'];
	        } else {
		        $port = ($parsed_url['scheme'] == 'https') ? 443 : 80;
	        }

	        $response = false;

	        $errno    = '';
	        $errstr   = '';
					
					ob_start();
					$fp = fsockopen( 'ssl://'.$host, $port, $errno, $errstr, 5 );

          if( $fp !== false ) {
					
                stream_set_timeout($fp, 30);
                
                $payload = "$method $path HTTP/1.0\r\n" .
		            "Host: $host\r\n" . 
		            "Connection: close\r\n"  .
                "User-Agent: $useragent\r\n" .
                "Content-type: application/x-www-form-urlencoded\r\n" .
                "Content-length: " . strlen($params) . "\r\n" .
                "Connection: close\r\n\r\n" .
                    $params;
                fwrite($fp, $payload);
                stream_set_timeout($fp, 30);
                
                $info = stream_get_meta_data($fp);
                while ((!feof($fp)) && (!$info["timed_out"])) {
                    $response .= fread($fp, 4096);
                    $info = stream_get_meta_data($fp);
                }
                
                fclose( $fp );
                ob_end_clean();
                
                list($headers, $response) = explode("\r\n\r\n", $response, 2);

                if(ini_get("magic_quotes_runtime")) $response = stripslashes($response);
								$info = array('http_code' => 200);
								
            } else {
              ob_end_clean();
    	        $info = array('http_code' => 500);
    	        throw new Exception($errstr,$errno);
            }
            $error = '';
        } else {
            throw new TextIt_Exception("No valid HTTP transport found", -99);
        }
        
        return array('header' => $info, 'body' => $response, 'error' => $error);
    }
}

?>