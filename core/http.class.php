<?php
/**
 * Class for processing http requests and responses.
 */
class Http
{
    /**
     * Checks if the current request is GET.
     * @return bool
     */
    static public function isGetRequest(): bool
    {
        return strtolower($_SERVER['REQUEST_METHOD']) === 'get';
    }

    /**
     * Checks if the current request is POST.
     * @return bool
     */
    static public function isPostRequest(): bool
    {
        return strtolower($_SERVER['REQUEST_METHOD']) === 'post';
    }
    
    /**
     * Checks if the current request is GET or POST and also has an 'X-Requested-With' header.
     * @param string $method optional, method type ('get' / 'post')
     * @param bool $exit optional, if we need to run exit() function when it's not an ajax request
     * @return bool
     */
    static public function isAjaxRequest(string $method = '', bool $exit = false): bool
    {
        $headers = array_keys(getallheaders());
        $method = $method === '' ? $method : strtolower($method);
        $check = true;

        if($method === 'get' && !self :: isGetRequest())
            $check = false;
        else if($method === 'post' && !self :: isPostRequest())
            $check = false;

        if($check)
            $check = in_array('x-requested-with', $headers) || in_array('X-Requested-With', $headers);

        if(!$check && $exit)
        {
            header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
            exit();
        }
        
        return $check;
    }

    /**
     * Returns raw request data from php://input
     * @param bool $as_array optional, to return result as array (from json)
     * @return string
     */
    static public function getRawRequestData(bool $as_array = false)
    {
        $data = file_get_contents('php://input');

        return $as_array ? json_decode($data, true) : $data;
    }

    /**
     * Sends http header and json data, created from passed array.
     * @param array $json data for json output
     * @param mixed $flags optional, php json flag(s) constant(s)
     * @return string
     */
    static public function responseJson(array $json = [], $flags = 0): void
    {
        $json = json_encode($json, $flags);

        header('Content-type: application/json');
        echo $json;
    }

    /**
     * Sends http header and passed xml data.
     * @param string $xml xml string for output
     * @return string
     */
    static public function responseXml(string $xml): void
    {
        if(strpos($xml, '<?xml version=') !== 0)
            $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"\n".$xml;

        header('Content-type: application/xml');
        echo $xml;
    }

    /**
     * Sends http header and passed palin text data.
     * @param string $text text string for output
     * @return string
     */
    static public function responseText(string $text): void
    {
        header('Content-type: text/plain');
        echo $text;
    }

    /**
	 * Checks if the current connection is https.
	 * @return bool
	 */
    static public function isHttps()
    {
        return Router :: isHttps();
    }

    /**
	 * Checks if the current host is localhost.
	 * @return bool
	 */
    static public function isLocalHost()
    {
        return Router :: isLocalHost();
    }

    /**
	 * Sets one cookie with passed parametares.
     * @param string $key name of cookie
     * @param string $value value of cookie
     * @param string $params extra cookie parameters
	 */
    static public function setCookie(string $key, string $value, array $params = []): void
    {
        $expires = $params['expires'] ?? 0;
        $path = $params['path'] ?? Registry :: get('MainPath');
        $domain = $params['domain'] ?? '';
        $http_only = Registry :: get('HttpOnlyCookie') ?? true;

        setcookie($key, $value, $expires, $path, $domain, self :: isHttps(), $http_only);
    }
}