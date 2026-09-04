<?php
use PHPUnit\Framework\TestCase;
use Roolith\Template\Engine\Interfaces\ViewInterface;
use Roolith\Template\Engine\View;

class ViewTest extends TestCase
{
    protected $viewPath = __DIR__ . '/viewsForTest';

    private function accessProtected($obj, $prop)
    {
        try {
            $reflection = new ReflectionClass($obj);
            $property = $reflection->getProperty($prop);
            $property->setAccessible(true);

            return $property->getValue($obj);
        } catch (ReflectionException $e) {
            return false;
        }
    }

    private function getInstance()
    {
        return new View($this->viewPath);
    }

    public function testShouldSetViewFolder()
    {
        $viewInstance = new View($this->viewPath);

        $this->assertInstanceOf(ViewInterface::class, $viewInstance);
        $this->assertEquals($this->viewPath, $this->accessProtected($viewInstance, 'viewFolder'));
    }

    public function testShouldEscapeVariable()
    {
        $viewInstance = $this->getInstance();
        $viewInstance->setTemplateData(['test' => 'aaa']);

        $this->assertEquals('aaa', $viewInstance->escape('test'));

        $this->expectException(\Roolith\Template\Engine\Exceptions\Exception::class);
        $viewInstance->escape('not');
    }

    public function testShouldAddSlashBeforeUrl()
    {
        $viewInstance = $this->getInstance();
        $url = 'assets/something.txt';

        $this->assertEquals('/' . $url, $viewInstance->url($url));
    }

    public function testShouldCompileViewFile()
    {
        $viewInstance = $this->getInstance();
        $data = [
            'content' => 'home content',
            'title' => 'home page',
        ];
        $result = $viewInstance->compile('home', $data);

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('home page', $result);
        $this->assertStringContainsString('home content', $result);

        $this->expectException(\Roolith\Template\Engine\Exceptions\Exception::class);
        $viewInstance->compile('file-doesnt-exists');
    }

    public function testShouldCompileNestedViewFile()
    {
        $viewInstance = $this->getInstance();
        $data = [
            'content' => 'nested',
        ];
        $result = $viewInstance->compile('nested.nested', $data);

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('nested', $result);
    }

    public function testShouldRejectParentDirectoryTraversal()
    {
        $viewInstance = $this->getInstance();

        foreach (['..', '../composer', 'nested/../home', 'nested..nested'] as $name) {
            try {
                $viewInstance->compile($name);
                $this->fail("Expected InvalidArgumentException for view name [$name]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid view name', $e->getMessage());
            }
        }
    }

    public function testShouldRejectAbsolutePaths()
    {
        $viewInstance = $this->getInstance();

        foreach (['/etc/hostname', '/home', '\\windows\\secret'] as $name) {
            try {
                $viewInstance->compile($name);
                $this->fail("Expected InvalidArgumentException for view name [$name]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid view name', $e->getMessage());
            }
        }
    }

    public function testShouldRejectStreamWrappers()
    {
        $viewInstance = $this->getInstance();

        foreach (['php://filter/convert.base64-encode/resource=home', 'file:///etc/hostname', 'home:evil', 'data://text/plain,hi'] as $name) {
            try {
                $viewInstance->compile($name);
                $this->fail("Expected InvalidArgumentException for view name [$name]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid view name', $e->getMessage());
            }
        }
    }

    public function testMissingTemplateMessageShouldContainResolvedPath()
    {
        $viewInstance = $this->getInstance();

        try {
            $viewInstance->compile('file-doesnt-exists');
            $this->fail('Expected Exception for missing template');
        } catch (\InvalidArgumentException $e) {
            $this->fail('Missing template should throw Exception, not InvalidArgumentException');
        } catch (\Roolith\Template\Engine\Exceptions\Exception $e) {
            $this->assertStringContainsString('file-doesnt-exists', $e->getMessage());
            $this->assertStringContainsString('resolved:', $e->getMessage());
        }
    }
}