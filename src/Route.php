<?php

namespace Onesimus\Router;

class Route
{
    protected $httpmethod;
    protected $pattern;
    protected $class;
    protected $method;
    protected $filter;
    protected $url;
    protected $callable;

    public function __construct($httpmethod, $pattern, $options)
    {
        if (!is_array($options)) {
            $options = ['use' => $options, 'filter' => ''];
        }

        if (is_callable($options['use'])) {
            $this->callable = $options['use'];
        } else {
            list($class, $method) = explode('@', $options['use'], 2);
            $this->class = $class;
            $this->method = $method;
        }

        $this->httpmethod = $httpmethod;
        $this->pattern = $pattern;
        $this->filter = $options['filter'];
    }

    public function getMethod()
    {
        return $this->httpmethod;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function matches($url)
    {
        if ($this->pattern == $url || $this->patternMatch($url)) {
            return true;
        }
        return false;
    }

    /**
     *  Search for the best fit route given the URL
     */
    private function patternMatch($url)
    {
        $url = explode('/', $url);
        $pattern = explode('/', $this->pattern);
        $matches = true;

        if (count($url) > count($pattern)) {
            return false;
        }

        foreach ($pattern as $pkey => $pvalue) {
            if (!isset($url[$pkey])) {
                if (substr($pvalue, 0, 2) === '{?') {
                    continue;
                } else {
                    $matches = false;
                    break;
                }
            }

            if ($pvalue != $url[$pkey] && $pvalue[0] != '{') {
                $matches = false;
                break;
            }
        }

        return $matches;
    }

    public function getScore($url)
    {
        $url = explode('/', $url);
        $pattern = explode('/', $this->pattern);
        $score = 0;

        if (count($url) > count($pattern)) {
            return 0;
        }

        foreach ($pattern as $index => $value) {
            if (!isset($url[$index])) {
                if (substr($value, 0, 2) === '{?') {
                    continue;
                } else {
                    return 0;
                }
            }

            if ($value != $url[$index] && $value[0] != '{') {
                return 0;
            } elseif ($value == $url[$index] && $value[0] != '{') {
                $score += 2;
            } elseif ($value[0] == '{' || substr($value, 0, 2) == '{?') {
                $score++;
            }
        }

        return $score;
    }

    /**
     *  If the route pattern has variables in it, find the corresponding
     *  positions in the URL and return those as arguments to the routed method
     */
    public function getVars($url)
    {
        $pattern = explode('/', $this->pattern);
        $url = explode('/', $url);
        $vars = [];
        foreach ($pattern as $key => $value) {
            if (substr($value, 0, 1) === '{' || substr($value, 0, 2) === '{?') {
                $vars []= isset($url[$key]) ? $url[$key] : null;
            }
        }
        return $vars;
    }

    public function dispatch($params = null)
    {
        if ($this->filter) {
            if (!Router::handleFilter($this->filter)) {
                throw new \Exception("Filter failed");
            }
        }

        if (!is_null($this->callable)) {
            call_user_func($this->callable, $this->getVars());
            return;
        }

        if (!$this->class || !class_exists($this->class)) {
            throw new \Exception("Controller '{$this->class}' wasn't found.");
            return;
        }

        $controller = new $this->class($params);
        if (method_exists($controller, $this->method)) {
            call_user_func_array(array($controller, $this->method), $this->getVars($this->url));
        } else {
            throw new \Exception("Method '{$method}' wasn't found in Class '{$class}'.");
        }
    }
}
