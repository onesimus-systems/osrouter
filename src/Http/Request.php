<?php
/**
 * OSRouter is a simple HTTP router for PHP.
 *
 * @author Lee Keitel <keitellf@gmail.com>
 * @copyright 2015 Lee Keitel, Onesimus Systems
 *
 * @license BSD 3-Clause
 */
namespace Onesimus\Router\Http;

class Request
{
    const METHOD_HEAD = 'HEAD';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    const METHOD_OPTIONS = 'OPTIONS';

    private static $instance;

    private $properties;

    public static function getRequest()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function mock(array $settings = [])
    {
        $defaults = [
            'SERVER_ADDR' => '127.0.0.1',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_FILENAME' => '',
            'QUERY_STRING' => '',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'USER_AGENT' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'URL_SCHEME' => 'http',
            'REQUEST_URI' => '',
            'FULL_URI' => ''
        ];
        self::$instance = new self(array_merge($defaults, $settings));

        return self::$instance;
    }

    private function __construct($settings = null)
    {
        if ($settings) {
            $this->properties = $settings;
        } else {
            $env = [];

            $env['SERVER_ADDR'] = $this->isSet($_SERVER['SERVER_ADDR']);
            $env['SERVER_NAME'] = $this->isSet($_SERVER['SERVER_NAME']);
            $env['SERVER_PORT'] = $this->isSet($_SERVER['SERVER_PORT']);
            $env['REMOTE_ADDR'] = $this->isSet($_SERVER['REMOTE_ADDR']);
            $env['REQUEST_METHOD'] = $this->isSet($_SERVER['REQUEST_METHOD']);
            $env['SCRIPT_FILENAME'] = $this->isSet($_SERVER['SCRIPT_FILENAME']);
            $env['REQUEST_URI'] = explode('?', $_SERVER['REQUEST_URI'])[0];
            $env['URL_SCHEME'] = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') ? 'http' : 'https';
            $env['QUERY_STRING'] = $this->isSet($_SERVER['QUERY_STRING']);

            if ($env['SERVER_PORT'] !== 80 && $env['SERVER_PORT'] !== 443) {
                // Normal ports
                $env['FULL_URI'] = $env['URL_SCHEME'] . '://' . $env['SERVER_NAME'] . $env['REQUEST_URI'];
            } else {
                // Custom ports
                $env['FULL_URI'] = $env['URL_SCHEME'] . '://' .
                                    rtrim($env['SERVER_NAME'],'/') . ':' . $env['SERVER_PORT']
                                    . $env['REQUEST_URI'];
            }

            $rawInput = @file_get_contents('php://input');
            if (!$rawInput) {
                $rawInput = '';
            }
            $env['INPUT'] = $rawInput;

            $this->properties = $env;
        }
    }

    private function isSet($var, $default = '')
    {
        return isset($var) ? $var : $default;
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function get($key)
    {
        return $this->properties[$key];
    }

    public function __set($key, $name)
    {
        return $this->set($key, $name);
    }

    public function set($key, $name)
    {
        return $this->properties[$key] = $name;
    }

    public function postParam($key = null, $default = null)
    {
        if (!isset($this->properties['POST'])) {
            $output = array();
            if (function_exists('mb_parse_str')) {
                mb_parse_str($this->properties['INPUT'], $output);
            } else {
                parse_str($this->properties['INPUT'], $output);
            }
            $this->properties['POST'] = $output;
        }
        if ($key) {
            if (array_key_exists($key, $this->properties['POST'])) {
                return urldecode($this->properties['POST'][$key]);
            } else {
                return $default;
            }
        } else {
            return $this->properties['POST'];
        }
    }

    public function getParam($key = null, $default = null)
    {
        if (!isset($this->properties['GET'])) {
            $output = array();
            if (function_exists('mb_parse_str')) {
                mb_parse_str($this->properties['QUERY_STRING'], $output);
            } else {
                parse_str($this->properties['QUERY_STRING'], $output);
            }
            $this->properties['GET'] = $output;
        }
        if ($key) {
            if (array_key_exists($key, $this->properties['GET'])) {
                return urldecode($this->properties['GET'][$key]);
            } else {
                return $default;
            }
        } else {
            return $this->properties['GET'];
        }
    }

    public function put($key = null, $default = null)
    {
        return $this->post($key, $default);
    }

    public function patch($key = null, $default = null)
    {
        return $this->post($key, $default);
    }

    public function delete($key = null, $default = null)
    {
        return $this->post($key, $default);
    }

    public function getMethod()
    {
        return $this->properties['REQUEST_METHOD'];
    }

    /**
     * Is this a GET request?
     * @return bool
     */
    public function isGet()
    {
        return $this->getMethod() === self::METHOD_GET;
    }

    /**
     * Is this a POST request?
     * @return bool
     */
    public function isPost()
    {
        return $this->getMethod() === self::METHOD_POST;
    }

    /**
     * Is this a PUT request?
     * @return bool
     */
    public function isPut()
    {
        return $this->getMethod() === self::METHOD_PUT;
    }

    /**
     * Is this a PATCH request?
     * @return bool
     */
    public function isPatch()
    {
        return $this->getMethod() === self::METHOD_PATCH;
    }

    /**
     * Is this a DELETE request?
     * @return bool
     */
    public function isDelete()
    {
        return $this->getMethod() === self::METHOD_DELETE;
    }

    /**
     * Is this a HEAD request?
     * @return bool
     */
    public function isHead()
    {
        return $this->getMethod() === self::METHOD_HEAD;
    }

    /**
     * Is this a OPTIONS request?
     * @return bool
     */
    public function isOptions()
    {
        return $this->getMethod() === self::METHOD_OPTIONS;
    }
}
