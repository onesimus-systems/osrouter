<?php

namespace Onesimus\Router;

class Router
{
    private static $instance;
    private static $routes = [];

    private static $filters = [];

    private function __construct() {}

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     *  Register route for a GET request
     */
    public static function get($url, $options)
    {
        self::register('GET', $url, $options);
        return;
    }

    /**
     *  Register a route for a POST request
     */
    public static function post($url, $options)
    {
        self::register('POST', $url, $options);
        return;

    }

    /**
     *  Register a route for any type of HTTP request
     */
    public static function any($url, $options)
    {
        self::register('ANY', $url, $options);
        return;

    }

    /**
     * Register a group of routes
     */
    public static function group(array $properties, array $routes)
    {
        // $routes: [0] = HTTP method, [1] = pattern, [2] = controller/method route
        $baseProperties = ['filter' => '', 'prefix' => '', 'rprefix' => ''];
        $properties = array_merge($baseProperties, $properties);

        foreach ($routes as $route) {
            $httpmethod = $route[0];

            if (!method_exists(__CLASS__, $httpmethod)) {
                continue;
            }

            $pattern = $properties['prefix'].$route[1];
            $controller = $route[2];
            $options = [
                'use' => $properties['rprefix'].$controller,
                'filter' => $properties['filter']
            ];
            self::$httpmethod($pattern, $options);
        }
    }

    /**
     *  Common register function, adds route to $routeList
     */
    private static function register($method, $url, $options)
    {
        $key = $method.'@'.$url;
        self::$routes[$key] = new Route($method, $url, $options);
    }

    /**
     * Get array of current routes
     */
    public static function getRoutes()
    {
        return self::$routes;
    }

    /**
     *  Initiate the routing for the given URL
     */
    public static function route(Http\Request $request, $test = null)
    {
        $path = str_replace(rtrim($request->get('SERVER_NAME'), '/'), '', $request->FULL_URI);
        $key = $request->getMethod().'@'.$path;
        $keyAny = 'ANY@'.$path;

        $matchedRoute = null;
        $matchedScore = 0;

        if (isset(self::$routes[$key])) {
            $matchedRoute = self::$routes[$key];
        } elseif (isset(self::$routes[$keyAny])) {
            $matchedRoute = self::$routes[$keyAny];
        } else {
            foreach (self::$routes as $key2 => $route) {
                if ($route->getMethod() != $request->getMethod() && $route->getMethod() != 'ANY') {
                    continue;
                }

                if ($test) {
                    echo 'Route: '.$key2.' Score: '.$route->getScore($path).' Current: '.$matchedScore.'<br>';
                }

                $score = $route->getScore($path);
                if ($score > $matchedScore) {
                    $matchedRoute = $route;
                    $matchedScore = $score;
                }
            }
        }

        $matchedRoute->setUrl($path);
        return $matchedRoute;
    }

    public static function filter($name, \Closure $callback)
    {
        self::$filters[$name] = $callback;
    }

    /**
     *  Perform any before actions on the route
     */
    public static function handleFilter($action = '')
    {
        if (!$action) {
            return true;
        }

        if (array_key_exists($action, self::$filters)) {
            $callback = self::$filters[$action];
            return $callback();
        }

        return false;
    }
}
