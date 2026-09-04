<?php
use PHPUnit\Framework\TestCase;
use Roolith\Template\Engine\TemplatePathResolver;
use Roolith\Template\Engine\View;

class TemplatePathResolverTest extends TestCase
{
    protected string $viewPath;

    protected function setUp(): void
    {
        $this->viewPath = __DIR__ . '/viewsForTest';
    }

    private function makeResolver(?string $folder = null): TemplatePathResolver
    {
        if ($folder === null) {
            $folder = $this->viewPath;
        }

        return new TemplatePathResolver($folder);
    }

    /** @return string[] */
    private function captureDeprecations(callable $fn): array
    {
        $deprecations = [];
        set_error_handler(function ($errno, $errstr) use (&$deprecations) {
            $deprecations[] = $errstr;

            return true;
        }, E_USER_DEPRECATED);

        try {
            $fn();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }

    public function testShouldResolveSimpleNameToCandidatePath()
    {
        $resolver = $this->makeResolver();

        $path = $resolver->resolve('home');

        $this->assertStringEndsWith('home.php', $path);
        $this->assertFileExists($path);
    }

    public function testShouldResolveNestedSlashWithoutDeprecation()
    {
        $resolver = $this->makeResolver();

        $deprecations = $this->captureDeprecations(function () use ($resolver, &$path) {
            $path = $resolver->resolve('nested/nested');
        });

        $this->assertSame([], $deprecations);
        $this->assertFileExists($path);
    }

    public function testShouldResolveDotAsDeprecatedAlias()
    {
        $resolver = $this->makeResolver();

        $dotPath = null;
        $deprecations = $this->captureDeprecations(function () use ($resolver, &$dotPath) {
            $dotPath = $resolver->resolve('nested.nested');
        });

        $slashPath = $resolver->resolve('nested/nested');

        $this->assertSame($slashPath, $dotPath);
        $this->assertNotEmpty($deprecations);
        $this->assertStringContainsString('deprecated', $deprecations[0]);
    }

    public function testShouldReturnRealpathForExistingTemplate()
    {
        $resolver = $this->makeResolver();

        $expected = realpath($this->viewPath . '/home.php');
        $this->assertSame($expected, $resolver->resolve('home'));
    }

    public function testShouldReturnCandidatePathForMissingTemplate()
    {
        $resolver = $this->makeResolver();

        $path = $resolver->resolve('file-doesnt-exists');

        $this->assertStringContainsString('file-doesnt-exists.php', $path);
        $this->assertFileDoesNotExist($path);
    }

    public function testShouldRejectParentDirectoryTraversal()
    {
        $resolver = $this->makeResolver();

        foreach (['..', '../composer', 'nested/../home', 'nested..nested'] as $name) {
            try {
                $resolver->resolve($name);
                $this->fail("Expected InvalidArgumentException for view name [$name]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid view name', $e->getMessage());
            }
        }
    }

    public function testShouldRejectAbsolutePaths()
    {
        $resolver = $this->makeResolver();

        foreach (['/etc/hostname', '/home', '\\windows\\secret'] as $name) {
            try {
                $resolver->resolve($name);
                $this->fail("Expected InvalidArgumentException for view name [$name]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid view name', $e->getMessage());
            }
        }
    }

    public function testShouldRejectStreamWrappers()
    {
        $resolver = $this->makeResolver();

        foreach (['php://filter/convert.base64-encode/resource=home', 'file:///etc/hostname', 'home:evil', 'data://text/plain,hi'] as $name) {
            try {
                $resolver->resolve($name);
                $this->fail("Expected InvalidArgumentException for view name [$name]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid view name', $e->getMessage());
            }
        }
    }

    public function testShouldRejectEmptyAndInvalidNames()
    {
        $resolver = $this->makeResolver();

        foreach (['', 'has space', 'semi;colon', 'back\\slash'] as $name) {
            try {
                $resolver->resolve($name);
                $this->fail("Expected InvalidArgumentException for view name [$name]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid view', $e->getMessage());
            }
        }
    }

    public function testShouldRequireViewFolder()
    {
        $resolver = new TemplatePathResolver();

        $this->assertNull($resolver->getViewFolder());

        try {
            $resolver->resolve('home');
            $this->fail('Expected InvalidArgumentException for missing view folder');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Invalid view folder', $e->getMessage());
        }
    }

    public function testShouldValidateViewFolderOnSet()
    {
        $resolver = new TemplatePathResolver();

        foreach (['', __DIR__ . '/does-not-exist', __FILE__] as $folder) {
            try {
                $resolver->setViewFolder($folder);
                $this->fail("Expected InvalidArgumentException for view folder [$folder]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid view folder', $e->getMessage());
            }
        }
    }

    public function testShouldNormalizeTrailingSlash()
    {
        $resolver = new TemplatePathResolver($this->viewPath . '/');

        $this->assertSame($this->viewPath, $resolver->getViewFolder());
        $this->assertFileExists($resolver->resolve('home'));
    }

    public function testShouldSupportCustomFileExtension()
    {
        $resolver = new TemplatePathResolver($this->viewPath, 'phtml');

        $this->assertSame('phtml', $resolver->getFileExtension());
        $this->assertStringEndsWith('home.phtml', $resolver->resolve('home'));

        $resolver->setFileExtension('.php');
        $this->assertSame('php', $resolver->getFileExtension());
    }

    public function testViewShouldAcceptInjectedResolver()
    {
        $resolver = new TemplatePathResolver($this->viewPath);
        $view = new View(null, $resolver);

        $this->assertSame($resolver, $view->getPathResolver());

        $result = $view->compile('home', ['content' => 'home content', 'title' => 'home page']);
        $this->assertStringContainsString('home page', $result);
    }

    public function testViewShouldSyncViewFolderWithResolver()
    {
        $view = new View($this->viewPath);
        $resolver = $view->getPathResolver();

        $this->assertSame($this->viewPath, $resolver->getViewFolder());

        $view->setViewFolder($this->viewPath);
        $this->assertSame($this->viewPath, $view->getPathResolver()->getViewFolder());
    }

    public function testViewConstructorFolderShouldOverrideResolverFolder()
    {
        $resolver = new TemplatePathResolver($this->viewPath);
        $view = new View($this->viewPath, $resolver);

        $this->assertSame($this->viewPath, $view->getPathResolver()->getViewFolder());
    }

    public function testViewSetPathResolverShouldSyncViewFolder()
    {
        $view = new View($this->viewPath);
        $resolver = new TemplatePathResolver($this->viewPath);

        $view->setPathResolver($resolver);

        $this->assertSame($resolver, $view->getPathResolver());
        $this->assertSame($this->viewPath, $resolver->getViewFolder());

        $result = $view->compile('home', ['content' => 'home content', 'title' => 'home page']);
        $this->assertStringContainsString('home page', $result);
    }
}
