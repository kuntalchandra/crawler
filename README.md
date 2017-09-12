Crawler
=========
This repo is a basic example of crawling application.

### What is it?
A crawler is a program that starts with a URL on the web e.g. https://news.google.co.in, fetches the web-page corresponding to that URL, and parses all the links on that page into a repository of links. Next, it fetches the contents of any of the URL from the repository just created, parses the links from this new content into the repository and continues this process for all links in the repository until stopped or after a given number of links are fetched.

There are several popular crawling and web scraping framework e.g. [Scrapy](https://github.com/scrapy/scrapy). Crawler is a minimal implementation of a crawling process, not in-depth as Scrapy.

### Dependencies
The usage has been explained in Linux environment but execution will be compatible with any other operating systems. Apache and PHP setup is required to execute the crawler.
Additionaly mbstring module needs to be installed.
```shell
$ sudo apt-get install php7.0-mbstring
```

### Usage
```shell
$ ./crawler -u https://news.google.co.in --limit 500
```

Output will be logged in detail, including execution time for each pass
```shell
$ tail -f /var/log/crawler.log
```

Crawled content will be stored here
```shell
$ ls -lt /var/tmp/crawled
```
![Crawled data](src/img/Selection_001.png?raw=true "Crawled data")

### Purpose
This program is a basic example and not intended for any commercial purpose. There are different areas which need to be improved, including its documentation.

This program uses PHP cURL multi functions in behind to scrap multiple URL contents in parallel. Check out its detailed implementation
```
src/service/CurlMultiProcess.php
```

### Where it can be improved?
- Crawler bash script; that's nothing but a wrapper now.
- Optimize Crawler->crawl()
- Improve relative to absolute URL conversion process

### Meta
Kuntal Chandra – [@kuntalchandra](https://twitter.com/kuntalchandra) – chandra.kuntal@gmail.com

Distributed under the MIT license. See ``LICENSE`` for more information.
