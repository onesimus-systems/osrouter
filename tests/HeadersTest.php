<?php
namespace Onesimus\Router\Tests;

use Onesimus\Router\Http\Headers;
use PHPUnit\Framework\TestCase;

class HeadersInternal extends Headers
{
    public function getHeaders()
    {
        return $this->headers;
    }
}

class HeadersTests extends TestCase
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

    public function testEmpty()
    {
        $headers = new HeadersInternal();

        $this->assertEquals(0, count($headers->getHeaders()));
    }

    public function testWithSeedValues()
    {
        $headers = new HeadersInternal([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer sometoken',
        ]);

        $this->assertEquals(2, count($headers->getHeaders()));
        $this->assertEquals('application/json', $headers->get('Content-Type'));
        $this->assertEquals('Bearer sometoken', $headers->get('Authorization'));
    }

    public function testWithSetValues()
    {
        $headers = new HeadersInternal([
            'Content-Type' => 'application/json',
        ]);

        $this->assertEquals('application/json', $headers->get('Content-Type'));

        $headers->set('Content-Type', 'text/html');
        $this->assertEquals('text/html', $headers->get('Content-Type'));
    }

    public function testWithNormalizeNames()
    {
        $headers = new HeadersInternal([
            'Content-Type' => 'application/json',
        ]);

        $this->assertEquals('application/json', $headers->get('CONTENT_TYPE'));
        $this->assertEquals('application/json', $headers->get('content_type'));
        $this->assertEquals('application/json', $headers->get('Content_Type'));
        $this->assertEquals('application/json', $headers->get('CONTENT-TYPE'));
        $this->assertEquals('application/json', $headers->get('CONTENT TYPE'));
        $this->assertEquals('application/json', $headers->get('content type'));
        $this->assertEquals('application/json', $headers->get('Content Type'));
    }

    public function testFromEnvironment()
    {
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
        $headers = Headers::fromEnvironment();

        $this->assertEquals('application/json', $headers->get('Content-Type'));
    }
}
