<?php namespace Rde;

use Httpful\Request;
use Httpful\Response;
use Httpful\Mime;
use Httpful\Http;

abstract class Api
{
    private $url;
    private $auth_basic_user;
    private $auth_basic_pwd;
    private $headers = array();

    public function __construct(array $config)
    {
        $this->url = $this->build_url($config);
        isset($config['host']) and $this->headers['Host'] = $config['host'];
        isset($config['auth_basic_user']) and $this->auth_basic_user = $config['auth_basic_user'];
        isset($config['auth_basic_pwd']) and $this->auth_basic_pwd = $config['auth_basic_pwd'];
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

    final public function build_url(array $config)
    {
        $protocol = isset($config['protocol']) ? $config['protocol'] : null;
        $ip = isset($config['ip']) ? $config['ip'] : null;
        $port = isset($config['port']) ? ':'.$config['port'] : null;
        $path = isset($config['path']) ? trim($config['path'], '/') : null;

        return rtrim("{$protocol}://{$ip}{$port}/{$path}", '/');
    }

    final public function send($method, $api, array $params = null, $callback = null, $profile = null)
    {
        $method = strtoupper($method);
        $full_url = $this->url.'/'.trim($api, '/');
        $request = Request::init($method)->uri($full_url)
            ->authenticateWith($this->auth_basic_user, $this->auth_basic_pwd)
            ->whenError(function($msg){
                throw new \RuntimeException(get_called_class().' say: '.$msg);
            })
            ->parseWith(function($body) {
                return json_decode($body, true);
            })
            ->expects(Mime::JSON);

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

        if (is_callable($profile) && false === $profile($request)) {
            return null;
        }

        /** @var Response $response */
        $response = $request->send();

        is_callable($callback) and
            call_user_func($callback, $request, $response);

        if (200 !== $response->code) {
            return false;
        }

        return $response->body;
    }

    public function get($api, array $params = null, $callback = null)
    {
        return $this->send(Http::GET, $api, $params, $callback);
    }

    public function post($api, array $params = null, $callback = null)
    {
        return $this->send(Http::POST, $api, $params, $callback);
    }

    public function put($api, array $params = null, $callback = null)
    {
        return $this->send(Http::PUT, $api, $params, $callback);
    }

    public function delete($api, array $params = null, $callback = null)
    {
        return $this->send(Http::DELETE, $api, $params, $callback);
    }
}
