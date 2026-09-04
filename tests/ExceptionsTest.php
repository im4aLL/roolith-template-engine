<?php
use PHPUnit\Framework\TestCase;
use Roolith\Template\Engine\Exceptions\Exception;
use Roolith\Template\Engine\Exceptions\InvalidArgumentException;

class ExceptionsTest extends TestCase
{
    public function testExceptionShouldExtendBaseException()
    {
        $e = new Exception('oops');

        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertSame('oops', $e->getMessage());
    }

    public function testInvalidArgumentExceptionShouldExtendTemplateException()
    {
        $e = new InvalidArgumentException('bad arg');

        $this->assertInstanceOf(Exception::class, $e);
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertSame('bad arg', $e->getMessage());
    }
}
