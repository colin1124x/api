## rde/api
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/8136db13-5ced-44bd-83c5-bf5169980355/mini.png)](https://insight.sensiolabs.com/projects/8136db13-5ced-44bd-83c5-bf5169980355)

### 建構基本 api 溝通物件 
``` php
    $api = new Rde\Api(array(
        'protocol' => Rde\Api::guessProtocol(),
        'host' => Rde\Api::guessHost(),
        'ip' => Rde\Api::guessIp(),
        'port' => Rde\Api::guessPort(),
        'path' => 'base/api/path',
        'auth_basic_user' => 'xxx',
        'auth_basic_pwd' => 'xxxxxxxxxxx',
    ));

    $data = $api->get(
        'test/api', 
        array("a" => "b"), 
        function($body, $response, array("method" => 'GET', "payload" => array("a" => "b"))){
            // accept callback
        },
        function($code, $err_msg, $res_raw_body, $exception){
            // reject callback
        },
        function($request){
            // profile callback
            // just for test
        }
    );
```

