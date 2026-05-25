<?php

function curlGetContent($url)
{
    $ch = curl_init($url);
    $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
    $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
    $header[] = "Cache-Control: max-age=0";
    $header[] = "Connection: keep-alive";
    $header[] = "Keep-Alive: 300";
    $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
    $header[] = "Accept-Language: en-us,en;q=0.5";
    $header[] = "Pragma: "; // browsers keep this blank.

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_REFERER, 'http://www.google.com');
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return array('header' => ['http_code' => '500'], 'body' => '', 'error' => $error);
    }

    // Then, after your curl_exec call:
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = get_headers_from_curl_response(substr($response, 0, $header_size));
    $body = substr($response, $header_size);
    
    // Fix HTTP code format
    $header['http_code'] = $httpCode;
    
    return array('header' => $header, 'body' => $body);
}

/**
 * Get the appropriate base URL based on environment
 */
function getBaseUrl()
{
    // Check if we're in local development environment
    $isLocal = (
        php_sapi_name() === 'cli' && 
        (strpos(__DIR__, 'laragon') !== false || strpos(__DIR__, 'localhost') !== false)
    ) || (
        isset($_SERVER['HTTP_HOST']) && 
        (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
         strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
         strpos($_SERVER['HTTP_HOST'], 'xskt_phalcon') !== false)
    );
    
    if ($isLocal) {
        // For local development, use HTTP instead of HTTPS
        return 'http://localhost/xskt_phalcon';
    }
    
    // For VPS/production, try to use localhost first, then fallback to domain
    if (php_sapi_name() === 'cli') {
        // When running from CLI on VPS, use localhost to avoid external DNS issues
        $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;
        $protocol = ($port == 443 || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')) ? 'https' : 'http';
        return "{$protocol}://localhost";
    }
    
    // For web requests, use the configured API URL or default
    $apiUrl = getenv('API_URL');
    return $apiUrl ?: 'https://soicau247.com';
}
function parseJsonMinhNgoc($content, $prefix = '')
{
    $content = str_replace([$prefix, ';'], '', $content);
    $content = preg_replace("/\{([a-zA-Z0-9]+)\:/", '{"$1":', $content);
    $content = preg_replace("/\,([a-zA-Z0-9]+)\:/", ',"$1":', $content);
    $content = preg_replace("/\:([0-9]+)\,/", ':"$1",', $content);
    return json_decode($content);
}
function get_headers_from_curl_response($response)
{
    $headers = array();

    $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

    foreach (explode("\r\n", $header_text) as $i => $line)
        if ($i === 0)
            $headers['http_code'] = $line;
        else {
            list($key, $value) = explode(': ', $line);

            $headers[$key] = $value;
        }

    return $headers;
}
function getMinhNgocLiveDomains()
{
    $content = curlGetContent('https://www.minhngoc.net/xo-so-truc-tiep/mien-nam.html');
    $domainList = explode('"', explode('var lisDomain="', $content['body'], 2)[1], 2)[0];
    return explode(',', $domainList);
}
function checkMinhNgocDomains($domain)
{
    $content = curlGetContent($domain . '/xstt/js_m1.js');
    if (parseJsonMinhNgoc($content['body'], 'kqxs.mn=')) return true;
    return false;
}
function selectMinhNgocLiveDomains()
{
    $domainList = file_get_contents(APP_PATH . '/cache/minh-ngoc-domains.txt');
    if (!$domainList) {
        $domainList = getMinhNgocLiveDomains();
        file_put_contents(APP_PATH . '/cache/minh-ngoc-domains.txt', implode(',', $domainList));
    } else {
        $domainList = explode(',', $domainList);
    }
    foreach ($domainList as $domain) {
        if (checkMinhNgocDomains($domain)) return $domain;
    }
    return false;
}
function isValidResult($value)
{
    if (empty($value)) {
        return false;
    }
    if (is_array($value)) {
        foreach ($value as $num) {
            // Nếu bất kỳ phần tử nào chứa '*' hoặc '+' thì không hợp lệ
            if (preg_match('/[\\*+]/', $num)) {
                return false;
            }
        }
        return true;
    }
    // Nếu chuỗi chứa '*' hoặc '+' thì không hợp lệ
    return !preg_match('/[\\*+]/', $value);
}
