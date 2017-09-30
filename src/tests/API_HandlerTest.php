<?php

namespace StormChat\tests;

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Exception;
use PHPUnit_Framework_TestCase;
use StormChat\API_Handler;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class API_HandlerTest extends PHPUnit_Framework_TestCase
{
    public function test_throw_error_if_request_content_is_not_json(){
        $handler = new API_Handler();

        $request = self::getMockBuilder(Request::class)
            ->setMethods(['getContent'])
            ->getMock();
        $request->method('getContent')->willReturn('this is not a json');

        self::expectException(Exception::class);
        /** @noinspection PhpParamsInspection */
        $handler->parse_request($request);
    }

    public function test_handle_request_with_empty_content() {
        $handler = new API_Handler();

        $request = self::getMockBuilder(Request::class)
            ->setMethods(['getContent'])
            ->getMock();
        $request->method('getContent')->willReturn('');
        /** @var Request $request */

        $handler->parse_request($request);
        self::assertEmpty($request->request->all());
    }

    public function test_parses_request_json_to_array() {
        $handler = new API_Handler();

        $request = self::getMockBuilder(Request::class)
            ->setMethods(['getContent'])
            ->getMock();
        $request->method('getContent')->willReturn('{"this":1, "is":"a", "json":[1,2,3]}');
        /** @var Request $request */

        $expected = [
            "this" => 1,
            "is" => "a",
            "json" => [1,2,3],
        ];
        $handler->parse_request($request);
        self::assertEquals($expected, $request->request->all());
    }

    public function test_passes_parameters_to_callable_and_return_json(){
        $handler = new API_Handler();

        $request = new Request();
        $params = self::getMockBuilder(ParameterBag::class)
            ->setMethods(['all'])
            ->getMock();
        $params->method('all')->willReturn( ['a' => 'Print', 'b' => 'in', 'c' => 'this', 'd' => 'order']);
        $request->request = $params;

        $expected = '{"result":"Print in this order"}';
        $actual = $handler->respond($request, function ($a, $b, $c, $d){
            return  "$a $b $c $d";
        });
        self::assertEquals($expected, $actual);
    }

    public function test_use_array_key_as_json_key_when_func_returns_array(){
        $handler = new API_Handler();

        $request = new Request();
        $params = self::getMockBuilder(ParameterBag::class)
            ->setMethods(['all'])
            ->getMock();
        $params->method('all')->willReturn( ['a' => 'Print', 'b' => 'in', 'c' => 'this', 'd' => 'order']);
        $request->request = $params;

        $expected = '{"result":["Print","in","this","order"]}';
        $actual = $handler->respond($request, function ($a, $b, $c, $d){
            return  [$a, $b, $c, $d,];
        });
        self::assertEquals($expected, $actual);

        $expected = '{"result":{"a":"Print","b":"in","c":"this","d":"order"}}';
        $actual = $handler->respond($request, function ($a, $b, $c, $d){
            return  ['a' => $a, 'b' => $b, 'c' => $c, 'd' => $d,];
        });
        self::assertEquals($expected, $actual);
    }

    public function test_passes_unordered_parameters_to_callable(){
        $handler = new API_Handler();

        $request = new Request();
        $params = self::getMockBuilder(ParameterBag::class)
            ->setMethods(['all'])
            ->getMock();
        $params->method('all')->willReturn( ['d' => 'order', 'b' => 'in', 'a' => 'Print', 'c' => 'this',]);
        $request->request = $params;
        $expected = '{"result":"Print in this order"}';

        $actual = $handler->respond($request, function ($a, $b, $c, $d){
            return  "$a $b $c $d";
        });
        self::assertEquals($expected, $actual);
    }

    public function test_uses_default_parameters_where_missing(){
        $handler = new API_Handler();

        $request = new Request();
        $params = self::getMockBuilder(ParameterBag::class)
            ->setMethods(['all'])
            ->getMock();
        $params->method('all')->willReturn( ['b' => 'in', 'a' => 'Print', 'c' => 'this',]);
        $request->request = $params;

        $expected = '{"result":"Print in this order"}';
        $actual = $handler->respond($request, function ($a, $b, $c, $d='order'){
            return  "$a $b $c $d";
        });
        self::assertEquals($expected, $actual);
    }

    public function test_disregard_additional_arguments() {
        $handler = new API_Handler();

        $request = new Request();
        $params = self::getMockBuilder(ParameterBag::class)
            ->setMethods(['all'])
            ->getMock();
        $params->method('all')->willReturn(['b' => 'in', 'a' => 'Print', 'c' => 'this', 'useless' => 'HELLO!']);
        $request->request = $params;

        $expected = '{"result":"Print in this order"}';
        $actual = $handler->respond($request, function ($a, $b, $c, $d = 'order') {
            return "$a $b $c $d";
        });
        self::assertEquals($expected, $actual);
    }

    public function test_throw_error_if_parameters_missing(){  #TODO: return an error response instead of plain throw
        $handler = new API_Handler();

        $request = new Request();
        $params = self::getMockBuilder(ParameterBag::class)
            ->setMethods(['all'])
            ->getMock();
        $params->method('all')->willReturn(['b' => 'in', 'a' => 'Print']);
        $request->request = $params;

        self::expectException(Exception::class);
        $handler->respond($request, function ($a, $b, $c, $d = 'order') {
            return "$a $b $c $d";
        });
    }

    public function test_calls_class_method() {
        $handler = new API_Handler();

        eval('namespace ' . __NAMESPACE__ . '; class TestClass {public function foo($a, $b, $c, $d="order"){return  "$a $b $c $d";}}');
        /** @noinspection PhpUndefinedClassInspection */
        $instance = new TestClass();

        $request = new Request();
        $params = self::getMockBuilder(ParameterBag::class)
            ->setMethods(['all'])
            ->getMock();
        $params->method('all')->willReturn( ['b' => 'in', 'a' => 'Print', 'c' => 'this',]);
        $request->request = $params;

        $expected = '{"result":"Print in this order"}';
        $actual = $handler->respond($request, [$instance, 'foo']);
        self::assertEquals($expected, $actual);
    }
}

