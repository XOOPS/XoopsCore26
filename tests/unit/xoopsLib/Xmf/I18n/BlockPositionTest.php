<?php

declare(strict_types=1);

namespace Xmf\Test\I18n;

use Xmf\I18n\BlockPosition;
use Xmf\I18n\Direction;

class BlockPositionTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        Direction::clearCache();
    }

    protected function tearDown(): void
    {
        Direction::clearCache();
    }

    public function testToLogicalLeftReturnsStart(): void
    {
        $this->assertSame('start', BlockPosition::toLogical(1));
    }

    public function testToLogicalRightReturnsEnd(): void
    {
        $this->assertSame('end', BlockPosition::toLogical(0));
    }

    public function testToLogicalCenterForUnknownValue(): void
    {
        $this->assertSame('center', BlockPosition::toLogical(5));
    }

    public function testToLogicalWithCustomValues(): void
    {
        $this->assertSame('start', BlockPosition::toLogical(10, 10, 20));
        $this->assertSame('end', BlockPosition::toLogical(20, 10, 20));
        $this->assertSame('center', BlockPosition::toLogical(30, 10, 20));
    }

    public function testToCssClassLeft(): void
    {
        $this->assertSame('block-start', BlockPosition::toCssClass(1));
    }

    public function testToCssClassRight(): void
    {
        $this->assertSame('block-end', BlockPosition::toCssClass(0));
    }

    public function testToCssClassCenter(): void
    {
        $this->assertSame('block-center', BlockPosition::toCssClass(99));
    }

    public function testGetStartValueLtr(): void
    {
        $this->assertSame(1, BlockPosition::getStartValue(Direction::LTR));
    }

    public function testGetStartValueRtl(): void
    {
        $this->assertSame(0, BlockPosition::getStartValue(Direction::RTL));
    }

    public function testGetEndValueLtr(): void
    {
        $this->assertSame(0, BlockPosition::getEndValue(Direction::LTR));
    }

    public function testGetEndValueRtl(): void
    {
        $this->assertSame(1, BlockPosition::getEndValue(Direction::RTL));
    }

    public function testGetStartValueWithCustomValues(): void
    {
        $this->assertSame(10, BlockPosition::getStartValue(Direction::LTR, 10, 20));
        $this->assertSame(20, BlockPosition::getStartValue(Direction::RTL, 10, 20));
    }

    public function testGetEndValueWithCustomValues(): void
    {
        $this->assertSame(20, BlockPosition::getEndValue(Direction::LTR, 10, 20));
        $this->assertSame(10, BlockPosition::getEndValue(Direction::RTL, 10, 20));
    }

    public function testDefaultConstants(): void
    {
        $this->assertSame(1, BlockPosition::DEFAULT_LEFT);
        $this->assertSame(0, BlockPosition::DEFAULT_RIGHT);
    }
}
