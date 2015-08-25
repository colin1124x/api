<?php namespace Rde;

use Httpful\Request;
use Httpful\Response;
use Httpful\Mime;
use Httpful\Http;

class Api
{
    private $url;
    private $auth_basic_user;
    private $auth_basic_pwd;
    private $timeout;
    private $headers = array();
    private $request_before_handlers = array();
    private $request_after_handlers = array();
    private $request_error_handlers = array();

    private $mime;
    private $parser;

    public function __construct(array $config)
    {
        $this->url = $this->build_url($config);
        isset($config['host']) and $this->headers['Host'] = $config['host'];
        isset($config['auth_basic_user']) and $this->auth_basic_user = $config['auth_basic_user'];
        isset($config['auth_basic_pwd']) and $this->auth_basic_pwd = $config['auth_basic_pwd'];
        // 預設使用json
        $this->mime = Mime::JSON;
        $this->parser = function($body){
            return json_decode($body, true);
        };
    }

    public function format($mime, \Closure $parser = null)
    {
        $this->mime = $mime;
        $this->parser = $parser;
    }

    final public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    final public function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    final public function getHeader($key)
    {
        return isset($this->headers[$key]) ? $this->headers[$key] : null;
    }

    final public function getHeaders()
    {
        return $this->headers;
    }

    final public function timeout($seconds)
    {
        $this->timeout = (int) $seconds;
    }

    final public function build_url(array $config)
    {
        $protocol = isset($config['protocol']) ? $config['protocol'] : null;
        $ip = isset($config['ip']) ? $config['ip'] : null;
        $host = isset($config['host']) ? $config['host'] : null;
        $port = isset($config['port']) ? ':'.$config['port'] : null;
        $path = isset($config['path']) ? trim($config['path'], '/') : null;

        $domain = $ip ?: $host;
        if ( ! $domain) throw new \RuntimeException('缺少 ip 或 host');

        return rtrim("{$protocol}://{$domain}{$port}/{$path}", '/');
    }

    final public function requestBefore($callable)
    {
        is_callable($callable) and $this->request_before_handlers[] = $callable;
    }

    final public function requestAfter($callable)
    {
        is_callable($callable) and $this->request_after_handlers[] = $callable;
    }

    final public function requestError($callable)
    {
        is_callable($callable) and $this->request_error_handlers[] = $callable;
    }

    final public function fire(array $handlers, $args)
    {
        foreach ($handlers as $callback) {
            try {
                call_user_func_array($callback, $args);
            } catch (\Exception $e) {}
        }
    }

    final public function send($method, $api, array $params = null, $accept = null, $reject = null, $profile = null)
    {
        $method = strtoupper($method);
        $full_url = $this->url.'/'.trim($api, '/');

        // 設定目標網址
        $request = Request::init($method)->uri($full_url);
        $request->whenError(function($msg){
            // 必須註冊Error handler 才能阻止Request 呼叫 error_log($msg)
            throw new \Exception($msg);
        });

        // 假如有指定 auth basic 驗證帳密
        null !== $this->auth_basic_user and
            $request->authenticateWith($this->auth_basic_user, $this->auth_basic_pwd);

        // 設定解析方式
        $request->parseWith($this->parser)
        ->expects($this->mime);

        if ( ! empty($params)) {
            switch ($method) {
                case Http::GET:
                    $request->uri($full_url.'?'.http_build_query($params));
                    break;

                case Http::POST:
                case Http::PUT:
                case Http::DELETE:
                    $request->sends(Mime::FORM)->body($params);
                    break;
            }
        }

        $headers = $this->getHeaders();
        if ( ! empty($headers)) {
            $request->addHeaders($headers);
        }

        null !== $this->timeout and $request->timeout($this->timeout);

        if (is_callable($profile) && false === $profile($request)) {
            return null;
        }

        $response = null;
        $exception = null;
        try {
            $this->fire($this->request_before_handlers, array($this, $request));

            /** @var Response $response */
            $response = $request->send();

            if (200 === $response->code) {

                $this->fire($this->request_after_handlers, array($this, $request, $response));

                is_callable($accept) and
                    call_user_func(
                        $accept, 
                        $response->body, 
                        $response, 
                        array(
                            "method" => $method,
                            "payload" => $params,
                        )
                    );

                return $response->body;
            }

            $exception = new \Exception("response status error [{$response->code}]");

        } catch (\Exception $e) {

            $exception = $e;

        }

        is_callable($reject) and $reject(
            $exception->getCode(),
            $exception->getMessage(),
            $response ? $response->raw_body : '',
            $exception);

        $raw_headers = $response ? $response->raw_headers : '';
        $this->fire($this->request_error_handlers, array($this, $raw_headers, $exception));

        return false;
    }

    public function withCookies(array $cookies)
    {
        $cookie_string = array();
        foreach ($cookies as $key => $val) {
            $cookie_string[] = "{$key}={$val}";
        }
        ! empty($cookie_string) and $this->setHeader('Cookie', join($cookie_string, '; '));
    }

    public function get($api, array $params = null, $accept = null, $reject = null, $profile = null)
    {
        return $this->send(Http::GET, $api, $params, $accept, $reject, $profile);
    }

    public function post($api, array $params = null, $accept = null, $reject = null, $profile = null)
    {
        return $this->send(Http::POST, $api, $params, $accept, $reject, $profile);
    }

    public function put($api, array $params = null, $accept = null, $reject = null, $profile = null)
    {
        return $this->send(Http::PUT, $api, $params, $accept, $reject, $profile);
    }

    public function delete($api, array $params = null, $accept = null, $reject = null, $profile = null)
    {
        return $this->send(Http::DELETE, $api, $params, $accept, $reject, $profile);
    }

    public static function guessProtocol()
    {
        return 443 === self::guessPort() ? 'https' : 'http';
    }

    public static function guessHost()
    {
        return $_SERVER['HTTP_HOST'];
    }

    public static function guessIp()
    {
        return '127.0.0.1';
    }

    public static function guessPort()
    {
        return (int) $_SERVER['SERVER_PORT'];
    }
}
