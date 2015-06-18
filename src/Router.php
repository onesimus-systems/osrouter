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

class Router
{
    protected static $instance;
    protected static $routes = [];
    protected static $_404route;

    protected static $filters = [];

    protected function __construct() {}

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
    public static function get($url, $callback, $options = [])
    {
        self::register('GET', $url, $callback, $options);
        return;
    }

    /**
     *  Register a route for a POST request
     */
    public static function post($url, $callback, $options = [])
    {
        self::register('POST', $url, $callback, $options);
        return;

    }

    /**
     *  Register a route for any type of HTTP request
     */
    public static function any($url, $callback, $options = [])
    {
        self::register('ANY', $url, $callback, $options);
        return;

    }

    /**
     * Register a route to be returned if a 404 is encounted
     *
     * @param  string/Closure $callback Class@method or Closure to call
     * @param  array  $options
     */
    public static function register404Route($callback, $options = [])
    {
        self::$_404route = new Route('ANY', '/404', $callback, $options);
        return;
    }

    /**
     * Register a group of routes
     */
    public static function group(array $properties, array $routes)
    {
        // Translate a single filter to an array of one filter
        if (isset($properties['filter'])) {
            if (!is_array($properties['filter'])) {
                $properties['filter'] = [$properties['filter']];
            }
        } else {
            $properties['filter'] = [];
        }

        $baseProperties = ['prefix' => '', 'rprefix' => ''];
        $properties = array_merge($baseProperties, $properties);

        // $routes: [0] = HTTP method, [1] = pattern, [2] = controller/method route
        foreach ($routes as $route) {
            $httpmethod = $route[0];

            if (!method_exists(__CLASS__, $httpmethod)) {
                continue;
            }

            $pattern = $properties['prefix'].$route[1];
            $callback = $properties['rprefix'].$route[2];
            $options = [
                'filter' => $properties['filter']
            ];
            self::$httpmethod($pattern, $callback, $options);
        }
    }

    /**
     *  Common register function, adds route to $routeList
     */
    private static function register($method, $url, $callback, $options = [])
    {
        $key = $method.'@'.$url;
        self::$routes[$key] = new Route($method, $url, $callback, $options);
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
    public static function route(Http\Request $request)
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
                if ($route->getMethod() != 'ANY' && $route->getMethod() != $request->getMethod()) {
                    continue;
                }

                $score = $route->getScore($path, $request->getMethod());
                if ($score > $matchedScore) {
                    $matchedRoute = $route;
                    $matchedScore = $score;
                }
            }
        }

        if ($matchedRoute) {
            $matchedRoute->setUrl($path);
        } elseif (self::$_404route) {
            $matchedRoute = self::$_404route;
        } else {
            throw new Exceptions\RouteException('Route not found');
        }
        return $matchedRoute;
    }

    /**
     * Register a filter with the router
     *
     * @param  string   $name     Name of the filter
     * @param  \Closure $callback Function to execute
     */
    public static function filter($name, \Closure $callback)
    {
        self::$filters[$name] = $callback;
    }

    /**
     * Does the router have a particular filter
     *
     * @param  string  $name Name of filter to check
     * @return boolean
     */
    public static function hasFilter($name)
    {
        return array_key_exists($name, self::$filters);
    }

    /**
     * Execute filter $action
     */
    public static function handleFilter($action = '')
    {
        if (!$action) {
            return true;
        }

        if (self::hasFilter($action)) {
            $callback = self::$filters[$action];
            return $callback();
        }

        return false;
    }
}
