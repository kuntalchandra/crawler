<?php

/**
* Excutes multiple processes in parallel using cURL. Uses built in curl_multi_exec()
*
* @author Kuntal Chandra
*/
class CurlMultiProcess
{
	private $sleepTime;

	function __construct($sleep = null)
	{
		$this->sleepTime = empty($sleep) === true ? 10000 : $sleep;
	}

	/**
	 * Executes multiple URL in parallel using curl_multi_exec()
	 *
	 * @param  array   $nodes           [description]
	 * @param  integer $responseTimeOut [description]
	 * @return array                    [description]
	 */
	public function exec(array $nodes, $responseTimeOut = 10)
    {
    	$nodeCount = count($nodes);
        $curlArr = [];
        $i = 0;
        $master = curl_multi_init();
        $referer = (array_key_exists('HTTP_HOST', $_SERVER) && array_key_exists('REQUEST_URI', $_SERVER)) ? 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : null;

        $options = [
	        	CURLOPT_RETURNTRANSFER => true,
	            CURLOPT_HEADER => false,
	            CURLOPT_CUSTOMREQUEST => 'GET',
	            CURLOPT_FOLLOWLOCATION => true,
	            CURLOPT_ENCODING => "",
	            CURLOPT_CONNECTTIMEOUT => $responseTimeOut,
	            CURLOPT_TIMEOUT => $responseTimeOut,
	            CURLOPT_SSL_VERIFYHOST => 0,
	            CURLOPT_SSL_VERIFYPEER => false,
	            CURL_HTTP_VERSION_1_1 => 1
	        ];

	    if (!empty($referer)) {
	    	$options[CURLOPT_REFERER] = $referer;
	    }

	    foreach ($nodes as $key => $url) {     //set options for each node
            $curlArr[$i] = curl_init();
            curl_setopt($curlArr[$i], CURLOPT_URL, $url);
            curl_setopt_array($curlArr[$i], $options);
            curl_multi_add_handle($master, $curlArr[$i]);
            $i++;
        }

        $timeStart = microtime(true);
        $running = null;
        $i = 0;
        $response = [];

        do {
            curl_multi_exec($master, $running);		//fork uri in parallel
            usleep($this->sleepTime);				//release virtual memory
        } while ($running > 0);

        foreach ($nodes as $key => $value) {		//get response detail for each node
            $body = curl_multi_getcontent($curlArr[$i]);
            $tmpArr = curl_getinfo($curlArr[$i]);
            $httpCode = $tmpArr['http_code'];

            curl_multi_remove_handle($master, $curlArr[$i]);
            curl_close($curlArr[$i]);

            //track if there is response timed out
            $error = ($tmpArr['total_time'] < $responseTimeOut) ? "false" : "Timed out: " . $tmpArr['url'] . " time: " . $tmpArr['total_time'] . " http code: " . $httpCode . " size: " . $tmpArr['size_download'] . " connect_time: " . $tmpArr['connect_time'] . " namelookup_time: " . $tmpArr['namelookup_time'];

            $response[$tmpArr['url']] = ['body' => $body, 'httpCode' => $httpCode, 'error' => $error];
            $i++;
        }

        $timeEnd = microtime(true);
        $time = $timeEnd - $timeStart;
        error_log("\nTime taken: " . $time, 3, Crawled::LOG);   //log total execution time
        curl_multi_close($master);

        return $response;
    }
}
