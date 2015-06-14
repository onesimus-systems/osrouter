<?php
namespace Onesimus\Router\Tests;

use Onesimus\Router;

// Mock class to access protected
// properties of the Route class
class MockRoute extends Router\Route
{
    public function get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
    }

    public function set($name, $value)
    {
        $this->$name = $value;
    }
}

// Test class for route dispatch
class Controller
{
    public function main()
    {
        return 'This is a controller';
    }
}

class RouteTests extends \PHPUnit_Framework_TestCase
{
    public function testCreationWithControllerClass()
    {
        $route = new MockRoute('GET', '/home', 'homeController@main');

        $this->assertEquals('GET', $route->getMethod());
        $this->assertEquals('/home', $route->get('pattern'));
        $this->assertEquals('homeController', $route->get('class'));
        $this->assertEquals('main', $route->get('method'));
        $this->assertNull($route->get('callable'));
    }

    public function testCreationWithCallable()
    {
        $route = new MockRoute('POST', '/home1', function() {
            return 'This is a callable';
        });

        $this->assertEquals('POST', $route->getMethod());
        $this->assertEquals('/home1', $route->get('pattern'));
        $this->assertNull($route->get('class'));
        $this->assertNull($route->get('method'));
        $this->assertInstanceOf('Closure', $route->get('callable'));
    }

    public function testPureStaticMatch()
    {
        $route = new MockRoute('GET', '/home', 'homeController@main');
        $this->assertTrue($route->matches('/home', 'get'));
        $this->assertFalse($route->matches('/home', 'post'));
        $this->assertFalse($route->matches('/hom', 'get'));
        $this->assertFalse($route->matches('/home/dash', 'get'));
    }

    public function testSimplePatternMatch()
    {
        $route = new MockRoute('GET', '/home/{board}', 'homeController@main');
        $this->assertTrue($route->matches('/home/dash', 'get'));
        $this->assertFalse($route->matches('/home/dash', 'post'));
        $this->assertFalse($route->matches('/home', 'get'));
        $this->assertFalse($route->matches('/home/dash/something', 'get'));
    }

    public function testOptionalPatternMatch()
    {
        $route = new MockRoute('GET', '/home/{?board}', 'homeController@main');
        $this->assertTrue($route->matches('/home/dash', 'get'));
        $this->assertFalse($route->matches('/home/dash', 'post'));
        $this->assertTrue($route->matches('/home', 'get'));
        $this->assertFalse($route->matches('/home/dash/something', 'get'));
    }

    public function testVariableExtraction()
    {
        $route = new MockRoute('GET', '/home/{board}/{?area}', 'homeController@main');

        $this->assertEquals(['dash', null], $route->getVars('/home/dash'));
        $this->assertEquals(['dash', 'chat'], $route->getVars('/home/dash/chat'));
    }

    public function testDispatchWithClosure()
    {
        $route = new MockRoute('GET', '/home', function() {
            return 'This is a callable';
        });
        $route->setUrl('/home');

        $this->assertEquals('This is a callable', $route->dispatch());
    }

    public function testDispatchWithClass()
    {
        $route = new MockRoute('GET', '/home', 'Onesimus\Router\Tests\Controller@main');
        $route->setUrl('/home');

        $this->assertEquals('This is a controller', $route->dispatch());
    }

    public function testRouteScores()
    {
        $route = new MockRoute('ANY', '/home/{board}/{?area}', 'homeController@main');
        $route2 = new MockRoute('ANY', '/home/dash/{board}/{?area}', 'homeController@main');

        // Doesn't match route
        $this->assertEquals(0, $route->getScore('/home', 'get'));
        // Required variable
        $this->assertEquals(5, $route->getScore('/home/dash', 'get'));
        // Optional variable
        $this->assertEquals(6, $route->getScore('/home/dash/chat', 'get'));
        // Doesn't match route
        $this->assertEquals(0, $route->getScore('/home/dash/chat/users', 'get'));

        // Test longer static routes take presidence
        $path = '/home/dash/status';
        $this->assertGreaterThan($route->getScore($path, 'get'), $route2->getScore($path, 'get'));
    }
}
