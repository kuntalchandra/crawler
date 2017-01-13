<?php

/*$base = 'http://money.rediff.com';
$rel = 'http://zarabol.rediff.com/nifty/7107057';
print_r(parse_url($base));
var_dump($rel);
print_r(parse_url($rel));
die;*/

include('crawler/Crawler.php');

try {
	$options = getopt('u:');             //base url to start with

	if (empty($options['u'])) {
		throw new \Exception("Base URL is empty. Crawling can't be processed\n");
	}

	$urlBase = trim($options['u']);
} catch (\Exception $e) {
	echo "Exception thrown: " . __FILE__ . " " . __LINE__ . " : " . $e->getMessage() . "\n";
	exit();
}

try {
	$options = getopt('l:');             //max number of links to crawl
	$linkCount = empty($options['l']) ? 100 : (int) trim($options['l']);	//by default crawl upto 100 links max
} catch (\Exception $e) {
	echo "Exception thrown: " . __FILE__ . " " . __LINE__ . " : " . $e->getMessage() . "\n";
	exit();
}

$crawl = new Crawler($urlBase, $linkCount);
$crawl->init();
