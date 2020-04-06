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

class Headers implements \IteratorAggregate
{
    protected $headers = [];

    public function __construct(array $headers = [])
    {
        $this->replace($headers);
    }

    public function replace(array $data)
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function get($header)
    {
        return $this->headers[static::normalizeKey($header)];
    }

    public function set($header, $data)
    {
        $this->headers[static::normalizeKey($header)] = $data;
    }

    public function __get($header)
    {
        return $this->get($header);
    }

    public function __set($header, $data)
    {
        $this->set($header, $data);
    }

    public function __isset($header)
    {
        return isset($this->headers[static::normalizeKey($header)]);
    }

    public function __unset($header)
    {
        $this->remove($header);
    }

    public function remove($header)
    {
        unset($this->headers[static::normalizeKey($header)]);
    }

    /**
     * Transform header name into canonical form
     * @param  string $key
     * @return string
     */
    protected static function normalizeKey($key)
    {
        $key = strtolower($key);
        $key = str_replace(array('-', '_'), ' ', $key);
        $key = preg_replace('#^http #', '', $key);
        $key = ucwords($key);
        $key = str_replace(' ', '-', $key);

        return $key;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->headers);
    }

    public static function fromEnvironment()
    {
        $headers = [];

        foreach($_SERVER as $key => $value) {
            if (substr($key, 0, 5) <> 'HTTP_') {
                continue;
            }

            $header = static::normalizeKey(substr($key, 5));
            $headers[$header] = $value;
        }

        return new self($headers);
    }
}
