<?php
/**
 * Created by PhpStorm.
 * User: akseli
 * Date: 9.9.2017
 * Time: 14:00
 */

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
            $params = $method_reflection->getParameters();
        }
        else {
            $func_reflection = new \ReflectionFunction($func);
            $params = $func_reflection->getParameters();
        }

        $missing_parameters = [];
        $params_ordered = [];
        foreach($params as $p) {
            $name = $p->getName();
            $value = null;

            if (!isset($params_unordered[$name])) {
                if ($p->isOptional()) {
                    $value = $p->getDefaultValue();
                } else {
                    $missing_parameters[] = $name;
                }
            } else {
                $value = $params_unordered[$name];
            }
            $params_ordered[$name] = $value;
        }

        if ($missing_parameters) {
            throw new Exception('Missing request parameters: ' . implode(',', $missing_parameters));
        }

        $ret = call_user_func_array($func, $params_ordered);

        if (is_object($ret)){
            throw new Exception("Cannot return object");
        }
        elseif(!is_array($ret)){
            $ret =  ['result' => $ret];
        }
        return json_encode($ret);
    }

    public function error_response(Request $request, Exception $e) {

    }
}