<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    #[Test]
    public function that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
