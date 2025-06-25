<?php

namespace Tests\Unit;

use App\Http\Controllers\RegistrationController;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RegistrationControllerUnitTest extends TestCase
{
    private $controller;

    /**
     * Set up before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new RegistrationController();
    }

    #[Test]
    public function sanitize_search_keeps_normal_text()
    {
        $method = $this->getPrivateMethod('sanitizeSearch');
        
        $result = $method->invoke($this->controller, 'Joji Frank');
        
        $this->assertEquals('Joji Frank', $result);
    }

    #[Test]
    public function sanitize_search_removes_sql_keywords()
    {
        $method = $this->getPrivateMethod('sanitizeSearch');
        
        $input = 'Joji UNION SELECT * FROM users Frank';
        $result = $method->invoke($this->controller, $input);
        
        $this->assertStringNotContainsString('UNION', strtoupper($result));
        $this->assertStringNotContainsString('SELECT', strtoupper($result));
        $this->assertStringContainsString('Joji', $result);
        $this->assertStringContainsString('Frank', $result);
    }

    #[Test]
    public function sanitize_search_removes_html_tags()
    {
        $method = $this->getPrivateMethod('sanitizeSearch');
        
        $input = '<b>Joji</b> Frank';
        $result = $method->invoke($this->controller, $input);
        
        $this->assertStringNotContainsString('<b>', $result);
        $this->assertStringNotContainsString('</b>', $result);
        $this->assertStringContainsString('Joji', $result);
        $this->assertStringContainsString('Frank', $result);
    }

    #[Test]
    public function sanitize_search_handles_empty_input()
    {
        $method = $this->getPrivateMethod('sanitizeSearch');
        
        $this->assertNull($method->invoke($this->controller, ''));
        $this->assertNull($method->invoke($this->controller, null));
        
        $result = $method->invoke($this->controller, '   ');
        $this->assertEquals('', $result);
    }

    #[Test]
    public function sanitize_search_converts_quotes_to_html_entities()
    {
        $method = $this->getPrivateMethod('sanitizeSearch');
        
        $input = "Joji'middle\"Frank";
        $result = $method->invoke($this->controller, $input);
        
        $this->assertStringContainsString('&#039;', $result);
        $this->assertStringContainsString('&quot;', $result);
        $this->assertStringContainsString('Joji', $result);
        $this->assertStringContainsString('middle', $result);
        $this->assertStringContainsString('Frank', $result);
    }

    #[Test]
    public function sanitize_search_removes_dangerous_characters()
    {
        $method = $this->getPrivateMethod('sanitizeSearch');
        
        $input = "Joji<script>alert()</script>Frank";
        $result = $method->invoke($this->controller, $input);
        
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('</script>', $result);
        $this->assertStringContainsString('alert()', $result);
        $this->assertStringContainsString('Joji', $result);
        $this->assertStringContainsString('Frank', $result);
        
        $this->assertEquals('Jojialert()Frank', $result);
    }

    /**
     * Helper method to access private methods for testing
     */
    private function getPrivateMethod($methodName)
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }
}