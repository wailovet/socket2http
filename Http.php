<?php

/**
 * Http请求类.
 *
 * from https://github.com/dsyph3r/curl-php/blob/master/lib/Network/Curl/Curl.php
 */
class Http
{
    /**
     * Constants for available HTTP methods.
     */
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const PATCH = 'PATCH';
    const DELETE = 'DELETE';
    private $request_data;

    public function getRequest()
    {
        return $this->request_data;
    }

    public function getData()
    {
        return $this->request_data['data'];
    }

    public function getJsonToArray()
    {
        return json_decode($this->request_data['data'], true);
    }

    public function getJson()
    {
        return json_encode(json_decode($this->request_data['data'], true));
    }

    /**
     * CURL句柄.
     *
     * @var resource handle
     */
    protected $curl;

    /**
     * Create the cURL resource.
     */
    public function __construct()
    {
        $this->curl = curl_init();
    }

    /**
     * Clean up the cURL handle.
     */
    public function __destruct()
    {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
    }

    /**
     * Get the cURL handle.
     *
     * @return resource cURL handle
     */
    public function getCurl()
    {
        return $this->curl;
    }

    /**
     * Make a HTTP GET request.
     *
     * @param string $url
     * @param array $params
     * @param array $options
     *
     * @return Http
     */
    public function get($url, $params = array(), $options = array())
    {
        $this->request($url, self::GET, $params, $options);
        return $this;
    }

    public function localhostGet($host, $path = "/", $params = array(), $options = array())
    {
        $options['headers'][] = "host:{$host}";
        $this->get("http://127.0.0.1{$path}", $params, $options);
        return $this;
    }
    
    public function localhostPost($host, $path = "/", $params = array(), $options = array())
    {
        $options['headers'][] = "host:{$host}";
        $this->post("http://127.0.0.1{$path}", $params, $options);
        return $this;
    }

    /**
     * Make a HTTP POST request.
     *
     * @param string $url
     * @param array $params
     * @param array $options
     *
     * @return Http
     */
    public function post($url, $params = array(), $options = array())
    {
        $this->request($url, self::POST, $params, $options);
        return $this;
    }

    /**
     * Make a HTTP PUT request.
     *
     * @param string $url
     * @param array $params
     * @param array $options
     *
     * @return Http
     */
    public function put($url, $params = array(), $options = array())
    {
        $this->request($url, self::PUT, $params, $options);
        return $this;
    }

    /**
     * Make a HTTP PATCH request.
     *
     * @param string $url
     * @param array $params
     * @param array $options
     *
     * @return Http
     */
    public function patch($url, $params = array(), $options = array())
    {
        $this->request($url, self::PATCH, $params, $options);
        return $this;
    }

    /**
     * Make a HTTP DELETE request.
     *
     * @param string $url
     * @param array $params
     * @param array $options
     *
     * @return Http
     */
    public function delete($url, $params = array(), $options = array())
    {
        $this->request($url, self::DELETE, $params, $options);
        return $this;
    }

    /**
     * Make a HTTP request.
     *
     * @param string $url
     * @param string $method
     * @param array $params
     * @param array $options
     * @return Http
     * @throws Exception
     */
    protected function request($url, $method = self::GET, $params = array(), $options = array())
    {
        $this->request_data = null;
        if ($method === self::GET || $method === self::DELETE) {
            $url .= (stripos($url, '?') ? '&' : '?') . http_build_query($params);
            $params = array();
        }
        curl_setopt($this->curl, CURLOPT_HEADER, 1);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        //使用证书情况
        if (isset($options['sslcert_path']) && isset($options['sslkey_path'])) {
            if (!file_exists($options['sslcert_path']) || !file_exists($options['sslkey_path'])) {
                throw new \Exception('Certfile is not correct');
            }
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 2);//严格校验
            curl_setopt($this->curl, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($this->curl, CURLOPT_SSLCERT, $options['sslcert_path']);
            curl_setopt($this->curl, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($this->curl, CURLOPT_SSLKEY, $options['sslkey_path']);
        } else {
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
        }
        // Check for files
        if (isset($options['files']) && count($options['files'])) {
            foreach ($options['files'] as $index => $file) {
                $params[$index] = $this->createCurlFile($file);
            }
            version_compare(PHP_VERSION, '5.5', '<') || curl_setopt($this->curl, CURLOPT_SAFE_UPLOAD, TRUE);
            curl_setopt($this->curl, CURLOPT_POST, 1);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
        } else {
            if (isset($options['json'])) {
                $params = json_encode($params, true);
                $options['headers'][] = 'content-type:application/json';
            }
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
        }
        // Check for custom headers
        if (isset($options['headers']) && count($options['headers'])) {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $options['headers']);
        }
        // Check for basic auth
        if (isset($options['auth']['type']) && 'basic' === $options['auth']['type']) {
            curl_setopt($this->curl, CURLOPT_USERPWD, $options['auth']['username'] . ':' . $options['auth']['password']);
        }
        $response = $this->doCurl();
        // Separate headers and body
        $headerSize = $response['curl_info']['header_size'];
        $header = substr($response['response'], 0, $headerSize);
        $body = substr($response['response'], $headerSize);
        $results = array(
            'curl_info' => $response['curl_info'],
            'content_type' => $response['curl_info']['content_type'],
            'status' => $response['curl_info']['http_code'],
            'headers' => $this->splitHeaders($header),
            'data' => $body,
        );
        $this->request_data = $results;
        return $this;
    }

    /**
     * make cURL file.
     *
     * @param string $filename
     *
     * @return \CURLFile|string
     */
    protected function createCurlFile($filename)
    {
        if (function_exists('curl_file_create')) {
            return curl_file_create($filename);
        }
        return "@$filename;filename=" . basename($filename);
    }

    /**
     * Split the HTTP headers.
     *
     * @param string $rawHeaders
     *
     * @return array
     */
    protected function splitHeaders($rawHeaders)
    {
        $headers = array();
        $lines = explode("\n", trim($rawHeaders));
        $headers['HTTP'] = array_shift($lines);
        foreach ($lines as $h) {
            $h = explode(':', $h, 2);
            if (isset($h[1])) {
                $headers[$h[0]] = trim($h[1]);
            }
        }
        return $headers;
    }

    /**
     * Perform the Curl request.
     * @return array
     * @throws Exception
     */
    protected function doCurl()
    {
        $response = curl_exec($this->curl);
        if (curl_errno($this->curl)) {
            throw new \Exception(curl_error($this->curl), 1);
        }
        $curlInfo = curl_getinfo($this->curl);
        $results = array(
            'curl_info' => $curlInfo,
            'response' => $response,
        );
        return $results;
    }
}