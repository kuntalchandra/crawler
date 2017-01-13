<?php

/**
* Utility class for Crawler.
*
* @author Kuntal Chandra
*/
class Util
{

	/**
	 * Returns the filtered data, or FALSE if the filter fails.
	 *
	 * @param  string $url [description]
	 * @return [type]      [description]
	 */
	public static function validateUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Generates URL by checking validity of relative URL.
     * Tries to guess the relative URL from abosulte one if the relative URL is not a valid URL.
     *
     * @param  string $base     [description]
     * @param  string $relative [description]
     * @return [type]           [description]
     */
    public static function buildUrl($base, $relative)
    {
        $relativeComponents = parse_url($relative);

        if (empty($relativeComponents['scheme']) || empty($relativeComponents['host'])) {
        	$baseComponents = parse_url($base);
        	$absolute = $baseComponents['scheme'] . '://' . $baseComponents['host'];
        	$relative = self::rel2abs($relative, $absolute);
        }

        return self::validateUrl($relative);
    }

    /**
     * TODO: This logic can be improved
     * Relative to absolute URL conversion code has been taken from
     * http://stackoverflow.com/questions/1243418/php-how-to-resolve-a-relative-url. Source has been alterted as required.
     * Aleternatively http_build_url http://php.net/manual/fa/function.http-build-url.php could be used if pecl_http is installed.
     *
     * @param  string $rel  [description]
     * @param  string $base [description]
     * @return string       [description]
     */
    public static function rel2abs($rel, $base)
    {
    	/* return if already absolute URL */
        if (parse_url($rel, PHP_URL_SCHEME) != '' || substr($rel, 0, 2) == '//') return $rel;

        $firstChar = mb_substr($rel, 0, 1, 'utf-8');

        /* queries and anchors */
        if ($firstChar =='#' || $firstChar == '?') return $base.$rel;

        $scheme = $host = $path = '';
        /* parse base URL and convert to local variables:
         $scheme, $host, $path */
         extract(parse_url($base));

        /*extract(parse_url($rel));

        if (isset($path) && (!isset($host) || !isset($scheme))) {
        	//there are relative URL which comes from user's content e.g. note, comment etc. Those are wrapped in a quote
        	$rel = preg_replace("/'/", "", $rel);
        	extract(parse_url($rel));
        }

        if (!isset($host) || !isset($scheme)) {		//still host or scheme couldn't found; construction won't be completed
        	return false;
        }*/

        /* remove non-directory element from path */
        $path = preg_replace('#/[^/]*$#', '', $path);

        /* destroy path if relative url points to root */
        if ($firstChar == '/') $path = '';

        /* dirty absolute URL */
        $abs = "$host$path/$rel";

        /* replace '//' or '/./' or '/foo/../' with '/' */
        $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
        for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}

        /* absolute URL is ready! */
        return $scheme.'://'.$abs;
    }
}
