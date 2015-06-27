<?php
/**
 * OSRouter is a simple HTTP router for PHP.
 *
 * @author Lee Keitel <keitellf@gmail.com>
 * @copyright 2015 Lee Keitel, Onesimus Systems
 *
 * @license BSD 3-Clause
 */
namespace Onesimus\Router;

class Route
{
    // HTTP method this route responds to
    // Typically set to GET, POST, or ANY
    protected $httpmethod;

    // URI pattern this route matches
    protected $pattern;

    // Closure assigned to this route if given
    protected $callable;

    // Class to spawn upon dispatch
    protected $class;

    // Class method to call upon dispatch
    protected $method;

    // Filters to apply, applied in order of assignment
    protected $filter;

    // URL of request
    protected $url;

    /**
     * Create a new route
     *
     * @param string $httpmethod Method this route responds to
     * @param string $pattern    URI pattern this route matches
     * @param string/Closure $callback Class@method or closure to call on dispatch
     * @param array/string $options
     *        If a string, $options is a single filter
     *        If an array, the key 'filter' is an array of filter(s) to run before dispatch
     */
    public function __construct($httpmethod, $pattern, $callback, $options = ['filter' => []])
    {
        if (!$options) {
            $options = ['filter' => []];
        }

        if (!is_array($options)) {
            $options = ['filter' => [$options]];
        }

        if (!is_array($options['filter'])) {
            $options['filter'] = [$options['filter']];
        }

        if ($callback instanceof \Closure) {
            $this->callable = $callback;
        } else {
            list($class, $method) = explode('@', $callback, 2);
            $this->class = $class;
            $this->method = $method;
        }

        $this->httpmethod = $httpmethod;
        $this->pattern = $pattern;
        $this->filter = $options['filter'];
    }

    /**
     * Returns HTTP method assigned to route
     * @return string
     */
    public function getMethod()
    {
        return $this->httpmethod;
    }

    /**
     * Sets request URI for variable extraction
     * @param string $url Request URI
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Return T/F if the route has a Closure for its dispatch
     * @return boolean
     */
    public function hasClosure()
    {
        return is_null($this->callable);
    }

    /**
     * Determines T/F if this route matches the given $url and $method
     *
     * @param  string $url    Url to attempt match
     * @param  string $method HTTP method to match
     * @return bool
     */
    public function matches($url, $method)
    {
        if ($this->httpmethod !== 'ANY' && strtolower($method) !== strtolower($this->httpmethod)) {
            return false;
        }

        // Exact match OR pattern match
        return $this->pattern == $url || $this->patternMatch($url);
    }

    /**
     * Determines if this route matches a variable path
     *
     * @param  string $url Url to attemt match
     * @return bool
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
                    // Piece not in URL but is optional
                    continue;
                } else {
                    // Piece not in URL and is required
                    return false;
                }
            }

            if ($pvalue != $url[$pkey] && substr($pvalue, 0, 1) != '{') {
                // Doesn't contain a required part
                return false;
            }
        }

        return $matches;
    }

    /**
     * Assigns a score to this route based on the $url
     *
     * This function is used to decide between possibly ambiguous routes if using only
     * the matches() method. For example, for the route /home/dash/char, the route
     * /home/dash/{?area} will be given a higher score than /home/{page}/{?area} even
     * though they both match. Matching static parts are given 2 points, matching variable
     * parts are given one point. As such, the first route would score 7 points while the other
     * would score 6 points. Thus the first route matches better and will take precedence.
     *
     * @param  string $url    Url to score
     * @param  string $method HTTP method of request
     * @return integer        Strength of the match
     */
    public function getScore($url, $method)
    {
        if ($this->httpmethod !== 'ANY' && strtolower($method) !== strtolower($this->httpmethod)) {
            return 0;
        }

        $url = explode('/', $url);
        $pattern = explode('/', $this->pattern);
        $score = 0;

        if (count($url) > count($pattern)) {
            return 0;
        }

        foreach ($pattern as $index => $value) {
            $isVar = substr($value, 0, 1) === '{';
            $isOpt = substr($value, 0, 2) === '{?';

            if (!isset($url[$index])) {
                if ($isOpt) {
                    // Piece not in URL but is optional
                    continue;
                } else {
                    // Piece not in URL and is required
                    return 0;
                }
            }

            if ($value != $url[$index] && !$isVar) {
                return 0; // Doesn't contain a required part
            } elseif ($value == $url[$index] && !$isVar) {
                $score += 2; // Matching static part
            } elseif ($isVar || $isOpt) {
                $score++; // Has variable part
            }
        }

        return $score;
    }

    /**
     * Extracts variable values from the given $url based on the route pattern
     *
     * @param  string $url URL to use for extraction
     * @return array       Values from URL in order from left to right
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

    /**
     * Calls the closure or class/method pair assigned to route
     *
     * @param  mixed $params Parameters to pass to the class construction or closure
     *                       If a route calls a closure, $params is prepended to the
     *                       URL pattern values array. If the route calls a class
     *                       and method, $params is given the class upon construction
     *                       and only the pattern values are given to the method
     * @return mixed         Whatever is returned by the closure or method
     */
    public function dispatch($params = null)
    {
        // Process filters
        if ($this->filter) {
            foreach ($this->filter as $filter) {
                if (!Router::hasFilter($filter)) {
                    throw new Exceptions\FailedFilterException("Filter '{$filter}' not registered");
                }
                if (!Router::handleFilter($filter)) {
                    throw new Exceptions\FailedFilterException("Filter '{$filter}' failed");
                }
            }
        }

        // Extract variables from URL
        $vars = $this->getVars($this->url);

        // Call Closure if available
        if (!is_null($this->callable)) {
            if ($params) {
                array_unshift($vars, $params);
            }
            return call_user_func_array($this->callable, $vars);
        }

        // If no Closure, instantiate class
        if (!$this->class || !class_exists($this->class)) {
            throw new Exceptions\RouteException("Controller '{$this->class}' wasn't found.");
        }

        $controller = new $this->class($params);
        // Call class method
        if (method_exists($controller, $this->method) && is_callable([$controller, $this->method])) {
            return call_user_func_array(array($controller, $this->method), $vars);
        } else {
            throw new Exceptions\RouteException("Method '{$this->method}' wasn't found in Class '{$this->class}' or is not public.");
        }
    }
}
