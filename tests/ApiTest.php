<?php

class ApiTest extends PHPUnit_Framework_TestCase
{
    public function testBBLB()
    {
        $tester = $this;
        $config = array(
            'protocol' => 'http',
            'ip' => '123.4.5.6',
            'host' => 'test.domain',
            'port' => 99,
            'path' => 'BBLB/api',
        );
        $bblb = new Rde\Api\BBLB($config);

        $bblb->send('get', 'test/api', array('a' => 'b'), null, function($req) use($tester) {

            /** @var Httpful\Request $req */
            $tester->assertEquals(
                'http://123.4.5.6:99/BBLB/api/test/api?a=b',
                $req->uri,
                '檢查 api uri'
            );

            $tester->assertEquals(
                'GET',
                $req->method,
                '檢查 api 方法'
            );

            $tester->assertTrue(isset($req->headers['Host']), '檢查 Host 有設定');

            $tester->assertEquals(
                'test.domain',
                $req->headers['Host'],
                '檢查 Host 設定值'
            );

            return false;
        });

    }
}
