<?php

include('src/util/Util.php');
include('src/service/CurlMultiProcess.php');
include('src/constants/Crawled.php');

/**
* Crawler base class
*
* @author Kuntal Chandra
*/
class Crawler
{
	/**
     * Chunk size to fork multiple URL in parallel
     */
    const BATCH_SIZE = 10;

	/**
	 * Maximum links to be crawled
	 *
	 * @var integer
	 */
	private $crawlLimit = 0;

    /**
     * Base URL to start crawling
     *
     * @var string
     */
    private $urlBase = null;

    /**
     * List of URL to be crawled
     *
     * @var array
     */
    private $repo = [];

    /**
     * List of URL already crawled
     *
     * @var array
     */
    private $crawled = [];

    /**
	 *
	 * @param string  $urlBase    [description]
	 * @param integer $crawlLimit [description]
	 */
	function __construct($urlBase, $crawlLimit)
	{
		$this->urlBase = $urlBase;
        $this->crawlLimit = $crawlLimit;
	}

	/**
	 * Initiates the crawling process
	 *
	 * @return [type] [description]
	 */
	public function init()
	{
		echo "\nInitiating crawl process with - " . $this->urlBase . " where maximum crawl limit is " . $this->crawlLimit . "\n";

		try {
			if ($this->addRepo($this->urlBase) === false) {
				throw new \Exception("Invalid URL to initiate crawl\n");
			}
		} catch (Exception $e) {
			echo "Exception thrown: " . __FILE__ . " " . __LINE__ . " : " . $e->getMessage() . "\n";
			exit();
		}

		$this->crawl();
	}

	/**
	 * Crawls URL from own repository. Re-fills repository by adding up new crawlable URL from crawled data. Caches crawled data into file.
	 *
	 * @return [type] [description]
	 */
	private function crawl()
    {
    	if (empty($this->repo)) {
    		echo "\nNo more links are there to crawl\n";
    		exit();
    	}

    	$chunks = array_chunk($this->repo, self::BATCH_SIZE);
    	$curlMulti = new CurlMultiProcess();

    	foreach ($chunks as $chunk) {
    		$results = $curlMulti->exec($chunk);
    		$this->resetCrawledRepo($chunk);
    		$this->buildRepo($results);
    		$this->saveContents($results);
    	}

    	$this->crawl();
    }

	/**
	 * Adds up new URL to crawl repository. Prevents earlier crawled URL to get added into repo again.
	 *
	 * @param string $url [description]
	 */
	private function addRepo($url)
	{
		$flag = false;

		if (Util::validateUrl($url) !== false && !in_array($url, $this->crawled)) {
			//echo "\n$url has been added to the crawler repository\n";
			$flag = array_push($this->repo, $url);
		}

		return $flag;
	}

	/**
	 * Helper method to add current crawling set into crawled list. Removes from repository also.
	 *
	 * @param  array  $crawledSet [description]
	 * @return [type]             [description]
	 */
	private function resetCrawledRepo(array $crawledSet)
	{
		$this->crawled = array_merge($this->crawled, $crawledSet);		//merge crawled set to crawled list
		$this->repo = array_diff($this->repo, $crawledSet);				//remove crawled set from repo
	}

	/**
	 * Builds repository by adding up new URL from crawled data, until crawl limit is reached. Generates abosulte URL from relative URL.
	 *
	 * @param  array  $results [description]
	 * @return [type]          [description]
	 */
	private function buildRepo(array $results)
	{
		$crawlCount = (count($this->repo) + count($this->crawled));
		$counter = 0;

		if ($crawlCount >= $this->crawlLimit) {
			echo "\nCrawl limit reached. No more URL would be added into repo\n";

			return;
		}

		$pattern = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
		$set = [];

		foreach ($results as $result) {
			try {
				if ($result['error'] != "false" || empty($result['body'])) {
					echo "\n" . $result['error'] . "\n";
					continue;
				}

				if (preg_match_all("/$pattern/siU", $result['body'], $matches)) {
					$set = array_merge($set, $matches[2]);
				}
			} catch (\Exception $e) {
				error_log("\nError: |" . __FILE__ . "|" . __LINE__ . "|" . $e->getMessage() . "\r\n", 3, Crawled::LOG);
			}
		}

		if (empty($set)) {
			echo "\nCrawlable URL not found from parsed results\n";

			return;
		}

		$set = array_unique($set);

		foreach ($set as $url) {
			$url = Util::buildUrl($this->urlBase, $url);
			$flag = $this->addRepo($url);

			if ($flag !== false) {
				$counter++;
			}
		}

		echo "\n$counter URL has been added into crawl repository. Will be reset to maximum limit, if reached.\n";
		$crawlCount += $counter;

		if ($crawlCount > $this->crawlLimit) {
			$this->repo = array_slice($this->repo, 0, $this->crawlLimit);
		}
	}

	/**
	 * Store crawled contents in file
	 *
	 * @param  array  $results [description]
	 * @return [type]          [description]
	 */
	private function saveContents(array $results)
    {
    	foreach ($results as $url => $content) {
    		if ($content['error'] != "false" || empty($content['body'])) {
				continue;
			}

			$body = $content['body'];
    		$components = parse_url($url);
    		$prefix = empty($components['host']) ? 'crawled_' : $components['host'];
    		$file = tempnam(Crawled::CONTENT_DIR, $prefix);
	        $handle = fopen($file, 'w');
	        fwrite($handle, $body);
	        fclose($handle);
    	}
    }
}
