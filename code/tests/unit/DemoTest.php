<?php

namespace App\Test\Unit;

use PHPUnit\Framework\TestCase;
use Mockery as m;


class DemoTest extends TestCase {
    

    public function test_OK(): void {
        $this->assertEquals('the same', 'the same');
    }

    public function test_Fail(): void {
      $url = $this->provider->getBaseUrl();
      $this->assertEquals('http://baseUrl.com', $url);

      $uri = parse_url($url);
      $this->assertEquals('http', $uri['scheme']);
      $this->assertEquals('baseUrl.com', $uri['host']);
  }

}