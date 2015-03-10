## rde/api

### 建構基本 api 溝通物件 
``` php
    $api = new Rde\Api\BBLB(array(
        'protocol' => Rde\Api::guessProtocol(),
        'host' => Rde\Api::guessHost(),
        'ip' => Rde\Api::guessIp(),
        'port' => Rde\Api::guessPort(),
        'path' => 'BBLB/api/',
        'auth_basic_user' => 'xxx',
        'auth_basic_pwd' => 'xxxxxxxxxxx',
    ));

    $data = $api->get(
        'test/api', 
        array('a' => 'b'), 
        function($res_body, $response){
            // accept callback
        },
        function($code, $err_msg, $res_raw_body){
            // reject callback
        },
        function($request){
            // profile callback
            // just for test
        }
    );
```