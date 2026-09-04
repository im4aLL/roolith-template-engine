<?php
use PHPUnit\Framework\TestCase;
use Roolith\Template\Engine\Exceptions\Exception;
use Roolith\Template\Engine\Interfaces\ViewInterface;
use Roolith\Template\Engine\TemplatePathResolver;
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

    public function testShouldHaveStringAndArrayTypeHints()
    {
        $method = new ReflectionMethod(View::class, 'setViewFolder');
        $this->assertSame('string', (string) $method->getParameters()[0]->getType());

        $method = new ReflectionMethod(View::class, 'compile');
        $params = $method->getParameters();
        $this->assertSame('string', (string) $params[0]->getType());
        $this->assertSame('array', (string) $params[1]->getType());
    }

    public function testShouldNormalizeViewFolderTrailingSlash()
    {
        $viewInstance = new View($this->viewPath . '/');

        $this->assertEquals($this->viewPath, $this->accessProtected($viewInstance, 'viewFolder'));

        $result = $viewInstance->compile('home', ['content' => 'home content', 'title' => 'home page']);
        $this->assertStringContainsString('home page', $result);
    }

    public function testShouldRejectInvalidViewFolder()
    {
        $viewInstance = new View($this->viewPath);

        foreach (['', __DIR__ . '/does-not-exist', __FILE__] as $folder) {
            try {
                $viewInstance->setViewFolder($folder);
                $this->fail("Expected InvalidArgumentException for view folder [$folder]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid view folder', $e->getMessage());
            }
        }
    }

    public function testShouldRejectInvalidViewFolderInConstructor()
    {
        try {
            new View(__DIR__ . '/does-not-exist');
            $this->fail('Expected InvalidArgumentException for invalid view folder in constructor');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Invalid view folder', $e->getMessage());
        }
    }

    public function testShouldEscapeVariable()
    {
        $viewInstance = $this->getInstance();
        $viewInstance->setTemplateData(['test' => 'aaa']);

        $this->assertEquals('aaa', $viewInstance->escape('test'));
    }

    public function testShouldThrowWhenEscapingUndefinedVariable()
    {
        $viewInstance = $this->getInstance();
        $viewInstance->setTemplateData(['test' => 'aaa']);

        $this->expectException(\Roolith\Template\Engine\Exceptions\Exception::class);
        $viewInstance->escape('not');
    }

    public function testShouldEscapeXssPayload()
    {
        $viewInstance = $this->getInstance();
        $viewInstance->setTemplateData(['test' => '<script>alert("xss")</script>']);

        $expected = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;';

        $this->assertEquals($expected, $viewInstance->escape('test'));
        $this->assertEquals($expected, $viewInstance->e('<script>alert("xss")</script>'));
        $this->assertEquals('&lt;b&gt;bold&lt;/b&gt; &amp; &#039;quotes&#039;', $viewInstance->e("<b>bold</b> & 'quotes'"));
    }

    public function testShouldRenderNullAsEmptyString()
    {
        $viewInstance = $this->getInstance();
        $viewInstance->setTemplateData(['test' => null]);

        $this->assertSame('', $viewInstance->escape('test'));
        $this->assertSame('', $viewInstance->e(null));
    }

    public function testShouldHandleUtf8()
    {
        $viewInstance = $this->getInstance();

        $valid = 'héllo 日本語';
        $viewInstance->setTemplateData(['test' => $valid]);
        $this->assertSame($valid, $viewInstance->escape('test'));
        $this->assertSame($valid, $viewInstance->e($valid));

        $invalid = "\xC3\x28";
        $result = $viewInstance->e($invalid);
        $this->assertStringContainsString("\u{FFFD}", $result);
        $this->assertNotSame('', $result);
    }

    public function testShouldAddSlashBeforeUrl()
    {
        $viewInstance = $this->getInstance();
        $url = 'assets/something.txt';

        $this->assertEquals('/' . $url, $viewInstance->url($url));
    }

    public function testShouldJoinBaseUrlWithoutTrailingSlash()
    {
        $viewInstance = $this->getInstance();
        $viewInstance->setBaseUrl('http://example.com');

        $this->assertEquals('http://example.com/assets/something.txt', $viewInstance->url('assets/something.txt'));
    }

    public function testShouldJoinBaseUrlWithTrailingSlash()
    {
        $viewInstance = $this->getInstance();
        $viewInstance->setBaseUrl('http://example.com/');

        $this->assertEquals('http://example.com/assets/something.txt', $viewInstance->url('assets/something.txt'));
    }

    public function testShouldTrimLeadingSlashFromSuffix()
    {
        $viewInstance = $this->getInstance();
        $viewInstance->setBaseUrl('http://example.com/');

        $this->assertEquals('http://example.com/assets/something.txt', $viewInstance->url('/assets/something.txt'));
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
    }

    public function testShouldThrowForMissingViewFile()
    {
        $viewInstance = $this->getInstance();

        $this->expectException(\Roolith\Template\Engine\Exceptions\Exception::class);
        $viewInstance->compile('file-doesnt-exists');
    }

    public function testShouldCompileNestedViewFile()
    {
        $viewInstance = $this->getInstance();
        $data = [
            'content' => 'nested',
        ];
        $result = $viewInstance->compile('nested/nested', $data);

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('nested', $result);
    }

    public function testShouldResolveSlashAsCanonicalSeparator()
    {
        $viewInstance = $this->getInstance();
        $deprecations = [];
        set_error_handler(function ($errno, $errstr) use (&$deprecations) {
            $deprecations[] = $errstr;

            return true;
        }, E_USER_DEPRECATED);

        try {
            $result = $viewInstance->compile('nested/nested', ['content' => 'nested']);
        } finally {
            restore_error_handler();
        }

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('nested', $result);
        $this->assertSame([], $deprecations, 'Canonical slash separator should not trigger deprecation');
    }

    public function testShouldSupportDotSeparatorAsDeprecatedAlias()
    {
        $viewInstance = $this->getInstance();
        $deprecations = [];
        set_error_handler(function ($errno, $errstr) use (&$deprecations) {
            $deprecations[] = $errstr;

            return true;
        }, E_USER_DEPRECATED);

        try {
            $dotResult = $viewInstance->compile('nested.nested', ['content' => 'nested']);
        } finally {
            restore_error_handler();
        }

        $slashResult = $viewInstance->compile('nested/nested', ['content' => 'nested']);

        $this->assertSame($slashResult, $dotResult);
        $this->assertNotEmpty($deprecations, 'Dot separator should trigger deprecation');
        $this->assertStringContainsString('deprecated', $deprecations[0]);
    }

    public function testShouldResolvePartialSlashAndDotToSameFile()
    {
        $viewInstance = $this->getInstance();
        $slashResult = $viewInstance->compile('partials/header', ['title' => 'home page']);

        $deprecations = [];
        set_error_handler(function ($errno, $errstr) use (&$deprecations) {
            $deprecations[] = $errstr;

            return true;
        }, E_USER_DEPRECATED);

        try {
            $dotResult = $viewInstance->compile('partials.header', ['title' => 'home page']);
        } finally {
            restore_error_handler();
        }

        $this->assertSame($slashResult, $dotResult);
        $this->assertStringContainsString('home page', $slashResult);
        $this->assertNotEmpty($deprecations, 'Dot separator should trigger deprecation');
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

    public function testShouldRestoreBufferLevelAfterThrowingTemplate()
    {
        $viewInstance = $this->getInstance();
        $levelBefore = ob_get_level();

        try {
            $viewInstance->compile('throwing', ['foo' => 'bar']);
            $this->fail('Expected RuntimeException from throwing template');
        } catch (\RuntimeException $e) {
            $this->assertSame('template failure', $e->getMessage());
        }

        $this->assertSame($levelBefore, ob_get_level(), 'Output buffer level should be restored after throwing template');
        $this->assertSame([], $viewInstance->getTemplateData(), 'Template data should be reset after throwing template');
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

    public function testShouldNotLeakInjectDataBetweenSiblingPartials()
    {
        $viewInstance = $this->getInstance();
        $result = $viewInstance->compile('leak-siblings');

        $this->assertStringContainsString('CLEAN', $result);
        $this->assertStringNotContainsString('LEAKED', $result);
    }

    public function testShouldNotLeakTemplateDataBetweenConsecutiveCompiles()
    {
        $viewInstance = $this->getInstance();

        $first = $viewInstance->compile('leak-consecutive', ['consecutiveSecret' => 'first']);
        $this->assertStringContainsString('LEAKED:first', $first);

        $second = $viewInstance->compile('leak-consecutive');
        $this->assertStringContainsString('CLEAN', $second);
        $this->assertStringNotContainsString('LEAKED', $second);
        $this->assertSame([], $viewInstance->getTemplateData());
    }

    public function testShouldThrowForMissingInjectedPartialAndRestoreTemplateData()
    {
        $viewInstance = $this->getInstance();
        $viewInstance->setTemplateData(['keep' => 'yes']);

        try {
            $viewInstance->inject('does-not-exist');
            $this->fail('Expected Exception for missing injected partial');
        } catch (Exception $e) {
            $this->assertStringContainsString('does-not-exist', $e->getMessage());
            $this->assertStringContainsString('resolved:', $e->getMessage());
        }

        $this->assertSame(['keep' => 'yes'], $viewInstance->getTemplateData());
    }

    public function testShouldManageTemplateData()
    {
        $viewInstance = $this->getInstance();

        $this->assertSame([], $viewInstance->getTemplateData());

        $viewInstance->setTemplateData(['a' => 1]);
        $this->assertSame(['a' => 1], $viewInstance->getTemplateData());

        $viewInstance->addTemplateData(['b' => 2]);
        $this->assertSame(['a' => 1, 'b' => 2], $viewInstance->getTemplateData());

        $viewInstance->addTemplateData(['a' => 99]);
        $this->assertSame(['a' => 99, 'b' => 2], $viewInstance->getTemplateData());

        $viewInstance->resetTemplateData();
        $this->assertSame([], $viewInstance->getTemplateData());
    }

    public function testShouldEscapeScalarValues()
    {
        $viewInstance = $this->getInstance();

        $this->assertSame('123', $viewInstance->e(123));
        $this->assertSame('0', $viewInstance->e(0));
        $this->assertSame('3.14', $viewInstance->e(3.14));
        $this->assertSame('1', $viewInstance->e(true));
        $this->assertSame('', $viewInstance->e(false));
        $this->assertSame('&lt;b&gt;', $viewInstance->e('<b>'));

        $viewInstance->setTemplateData(['count' => 42]);
        $this->assertSame('42', $viewInstance->escape('count'));
    }

    public function testShouldEscapeStringableObject()
    {
        $viewInstance = $this->getInstance();

        $object = new class {
            public function __toString(): string
            {
                return '<b>hi</b>';
            }
        };

        $this->assertSame('&lt;b&gt;hi&lt;/b&gt;', $viewInstance->e($object));
    }

    public function testShouldDefaultBaseUrlToFalseAndAllowReset()
    {
        $viewInstance = $this->getInstance();
        $this->assertFalse($viewInstance->getBaseUrl());

        $viewInstance->setBaseUrl('http://example.com');
        $this->assertSame('http://example.com', $viewInstance->getBaseUrl());

        $viewInstance->setBaseUrl(false);
        $this->assertFalse($viewInstance->getBaseUrl());
        $this->assertSame('/assets/a.txt', $viewInstance->url('assets/a.txt'));
    }

    public function testShouldHandleEmptyUrlSuffix()
    {
        $viewInstance = $this->getInstance();

        $this->assertSame('/', $viewInstance->url(''));

        $viewInstance->setBaseUrl('http://example.com');
        $this->assertSame('http://example.com/', $viewInstance->url(''));

        $viewInstance->setBaseUrl('http://example.com/');
        $this->assertSame('http://example.com/', $viewInstance->url(''));
    }

    public function testShouldFailCompileWithoutViewFolder()
    {
        $viewInstance = new View();

        $this->assertNull($viewInstance->getPathResolver()->getViewFolder());

        try {
            $viewInstance->compile('home');
            $this->fail('Expected InvalidArgumentException when view folder is not set');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Invalid view folder', $e->getMessage());
        }
    }

    public function testShouldPropagateCustomExtensionFromResolver()
    {
        $resolver = new TemplatePathResolver($this->viewPath, 'phtml');
        $viewInstance = new View(null, $resolver);

        $this->assertSame('phtml', $viewInstance->getPathResolver()->getFileExtension());
        $this->assertStringEndsWith('home.phtml', $viewInstance->getPathResolver()->resolve('home'));

        $viewInstance->setPathResolver(new TemplatePathResolver($this->viewPath, 'phtml'));
        $this->assertSame('phtml', $viewInstance->getPathResolver()->getFileExtension());
    }

    public function testConstructorFolderShouldOverrideDifferentResolverFolder()
    {
        $resolver = new TemplatePathResolver($this->viewPath);
        $nestedFolder = $this->viewPath . '/nested';
        $viewInstance = new View($nestedFolder, $resolver);

        $this->assertSame($nestedFolder, $viewInstance->getPathResolver()->getViewFolder());

        $result = $viewInstance->compile('nested', ['content' => 'nested']);
        $this->assertStringContainsString('nested', $result);
    }

    public function testShouldNotOverwriteLocalsViaCollidingDataKeys()
    {
        $viewInstance = $this->getInstance();

        $result = $viewInstance->compile('home', [
            'content' => 'hi',
            'title' => 't',
            'filename' => 'COLLIDE',
            'data' => 'COLLIDE',
            'level' => 'COLLIDE',
            'output' => 'COLLIDE',
            'path' => 'COLLIDE',
        ]);

        $this->assertStringContainsString('hi', $result);
        $this->assertSame([], $viewInstance->getTemplateData());
    }

    public function testShouldRestoreBufferLevelAfterSuccessfulCompile()
    {
        $viewInstance = $this->getInstance();
        $levelBefore = ob_get_level();

        $viewInstance->compile('home', ['content' => 'c', 'title' => 't']);

        $this->assertSame($levelBefore, ob_get_level());
        $this->assertSame([], $viewInstance->getTemplateData());
    }

    public function testShouldImplementViewInterfaceFully()
    {
        $viewInstance = $this->getInstance();
        $this->assertInstanceOf(ViewInterface::class, $viewInstance);

        foreach (['setViewFolder', 'compile', 'inject', 'url', 'e', 'escape', 'setBaseUrl', 'getBaseUrl'] as $method) {
            $this->assertTrue(method_exists($viewInstance, $method), "View should implement $method");
        }

        $this->assertTrue((new ReflectionMethod(View::class, 'inject'))->hasReturnType());
        $this->assertTrue((new ReflectionMethod(View::class, 'e'))->hasReturnType());
    }
}