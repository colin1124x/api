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

        $send_callback = false;
        $bblb->send('get', 'test/api', array('a' => 'b'), null, null, function($req) use($tester, &$send_callback) {

            $send_callback = true;

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

        $this->assertTrue($send_callback);
    }

    public function testPortal()
    {
        $tester = $this;

        $config = array(
            'protocol' => 'http',
            'ip' => '123.4.5.6',
            'host' => 'ipl.member.webservice',
            'port' => 99,
            'path' => 'app/WebService/view/display.php',
        );

        $api = new Rde\Api($config);

        $api->requestError(function($self, $req, $e){

        });

        $api->timeout(1);

        $send_callback = false;
        $api->send('get', 'GameSwitch', array('hallid' => 6, 'userid' => 123), null, null, function($req) use($tester, &$send_callback) {

            $send_callback = true;

            /** @var Httpful\Request $req */
            $tester->assertEquals(
                'http://123.4.5.6:99/app/WebService/view/display.php/GameSwitch?hallid=6&userid=123',
                $req->uri,
                '檢查 api uri'
            );

        });

        $this->assertTrue($send_callback, '檢查 send callback');
    }
}
