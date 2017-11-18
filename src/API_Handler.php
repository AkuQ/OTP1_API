<?php

namespace StormChat;

use Exception;
use Symfony\Component\HttpFoundation\Request;

class API_Handler
{
    public function __construct() {
    }

    public function parse_request(Request $request) {
        $content = $request->getContent();

        if (!$content) {
            $data = [];
        }
        else {
            $data = json_decode($content, true);
        }

        if ($data === null) {
            throw new Exception("Bad JSON");
        }
        else {
            $request->request->replace($data);
        }
    }

    public function respond(Request $request, callable $func) {
        $params_unordered = $request->request->all();

        if (is_array($func)) {
            $instance = $func[0];
            $class = get_class($instance);
            $method = $func[1];
            $method_reflection = new \ReflectionMethod($class, $method);
            $reflection_params = $method_reflection->getParameters();
        }
        else {
            $func_reflection = new \ReflectionFunction($func);
            $reflection_params = $func_reflection->getParameters();
        }

        $missing_parameters = [];
        $params_ordered = [];
        foreach($reflection_params as $p) {
            $name = $p->getName();

            if (isset($params_unordered[$name])) {
                $value = $params_unordered[$name];
            }
            else if ($p->isOptional()) {
                $value = $p->getDefaultValue();
            }
            else {
                $missing_parameters[] = $name;
                $value = null;
            }
            $params_ordered[$name] = $value;
        }

        if ($missing_parameters) {
            return json_encode([
                'result' => 0,
                'error' => [
                    'name' => 'missing_params',
                    'missing_params' => $missing_parameters
                ]
            ]);
        }

        $ret = call_user_func_array($func, $params_ordered);
        $error = ['name' => 'no_error'];

        if (is_object($ret)){  #todo: check for objects recursively
            throw new Exception("Cannot return object");
        }
        else if ($ret === null) {
            $ret = 1;
        }
        else if (is_bool($ret)){
            $ret = (int)$ret;
        }
        $ret =  ['result' => $ret, 'error' => $error];

        $json = json_encode($ret);

        if($json == false) {
            $ret = $ret['result'];
            throw new Exception("Result $ret not encodable in JSON");
        }
        return $json;
    }

    public function error_response(Request $request, Exception $e) {
        throw new Exception('Undefined method'); #todo
    }
}