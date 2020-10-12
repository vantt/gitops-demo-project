<?php

namespace App\Test\Unit;

use PHPUnit\Framework\TestCase;
use Mockery as m;


class DemoTest extends TestCase {

    public function test_OK(): void {
        $this->assertEquals('the same', 'the same');
    }

    // public function test_Fail(): void {
    //    $this->assertEquals('the same', 'the different');
    // }

}