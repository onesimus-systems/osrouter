OSRouter
--------

OSRouter is a simple, fast HTTP router for PHP. It can perform simple routes with variable and optional URL parts. OSRouter can extract parts of a URL as variable that can be passed to a closure or a controller method.

Requirements
------------

- PHP >= 5.4.0

Usage
-----

```php
use \Onesimus\Router\Router;
use \Onesimus\Router\Http\Request;

// First we add some routes
// The root path is sent to the index method of the HomeController class
Router::get('/', 'HomeController@index');
// Any path with a page such as /dash, /admin, etc
Router::get('/{page}', 'RenderController@render');
// API, required module, optional method
// Executes the closure
Router::post('/api/{module}/{?method}', function() {
	// Closure for route
	return;
});

// Get request object for current HTTP request
$request = Request::getRequest();
// Get the matching route for the given request
$route = Router::route($request);
// Dispatch route
// Execute the closure or the class/method combo
$route->dispatch();

// You can also pass an argument to the called closure
// or the class being instantiated
$route->dispatch($app);
```

The main methods are `Router::get()`, `::post()`, and `::any()`. The signature for each is `($pattern, $callback, $options = [])`. $pattern is the URI pattern the route will match using the format `/static/{variable}/{?option-variable}`. $callback is either a string such as `Controller@method` or a closure function. $options can be a single string in which case it will interpreted as a single filter, or it can be an array with the syntax `['filter' => ['filter1', 'filter2']]`. The outer array is to allow for extra options that may be added later on.

To route in an application located in a subdirectory of the webserver, you'll need to remove the directory prefix from the REQUEST_URI field in the Request object. For example, if the base of your application was located at `http://example.com/blog`, you will need to strip "/blog" from the request uri before processing a route. Otherwise, all routes will be checked against "/blog/something" instead of just "/something".

You can also register routes in a group

```php
Router::group(['prefix' => '/admin'],
	['get', '/groups/{?id}', 'Controller@groups'],
	['get', '/users/{?id}', 'Controller@user']
);
```

Groups are defined using the `Router::group(array $options, array $routes)` method. $options is a keyed array with the keys 'filter', 'prefix', and 'rprefix'. 'Filter' is an array of filters that apply to the group. A single filter can be given as a string as well. 'Prefix' is a prefix added to the HTTP pattern in each group. In the example above, the routes will be `/admin/groups/{?id}` and `/admin/users/{?id}`. 'Rprefix' is prepended to each controller statement. For example, if `'rprefix' => '\Namespace\Admin\'` was added to the group above, the controller statements would be `\Namespace\Admin\Controller@groups` and `\Namespace\Admin\Controller@user`.

**Note**: Closures cannot be assigned to a route defined in a group. To assign a closure, assign the route separately.

You can define a 404 route using the method `Router::register404Route($callback, $options = [])`. If no 404 route is defined and a route isn't found, a `RouteException` will be thrown.

Filters
-------

Filters can be defined and assigned to routes. Routes can have multiple filters, they will be handled in the order they're defined on the route. If all filters return a non-falsey value, the route will be green-lit and the dispatch will continue. Otherwise a `FailedFilterException` will be thrown. Also, a `RouteException` will be thrown if the filter isn't defined.

Example:

```php
// First register filter
// This function will return true if the session is authenticated
// or false otherwise. You may want to do some sort of redirect here
// as well for a failed authentication for example to a login page.
Router::filter('auth', function() {
    return is_authenticated();
});

// Define route for '/admin' path with filter of 'auth'
Router::get('/admin', 'AdminController@index', 'auth');
```

When the above route is dispatched, the 'auth' filter will be executed and if it returns a non-falsey value, the AdminController will be given control as normal. You can define multiple filters using an array.

```php
Router::get('/admin', 'AdminController@index', ['auth', 'inAdminGroup']);
```

In this case both filters must return truthy.

Router\Http\Request
-------------------

Router\Http\Response
--------------------
