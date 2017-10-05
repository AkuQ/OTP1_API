<?php

use Silex\WebTestCase;
use StormChat\Controller;

require_once __DIR__. '/../autoload.php';

class routesTest extends WebTestCase {

    // SETUP:
    /** @var PHPUnit_Framework_MockObject_MockObject */
    private $controller_mock;

    /**
     * Creates the application.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        $app =  require __DIR__.'/../../index.php';
        $app['debug'] = true;
        unset($app['exception_handler']);

        $this->controller_mock = $controller_mock = self::getMockBuilder(Controller::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept([])
            ->getMock();

        $app['controller'] = function () use ($controller_mock) {
            return $controller_mock;
        };

        return $app;
    }


    //HELPERS:

    static function json_content_as_array(Symfony\Component\HttpFoundation\Response $response) {
        self::assertTrue($response->isOk());
        $content = $response->getContent();
        self::assertJson($content);
        return json_decode($content, true);
    }


    //TESTS:

    public function test_get_time(){
        $client = self::createClient();
        $client->request('POST', '/get_time', [], [], [], $content=null);
        $content = self::json_content_as_array($client->getResponse());
        self::assertRegExp('~^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$~', $content['result']);
    }

    public function test_create_user(){
        $username = '~SEPHIROT69~';
        $out_id = 123;

        $this->controller_mock->method('create_user')
            ->with(self::equalTo($username))
            ->willReturn($out_id);

        $client = self::createClient();
        $client->request('POST', '/users/create', [], [], [],  $content=json_encode(['name' => $username]));
        $content = self::json_content_as_array($client->getResponse());
        self::assertEquals(['result' => $out_id], $content);
    }

    public function test_list_rooms(){
        $out_list = [
            ['id' => 1, 'name' => 'room_a'],
            ['id' => 2, 'name' => 'room_b'],
            ['id' => 3, 'name' => 'room_c'],
        ];

        $this->controller_mock->method('list_rooms')
            ->willReturn($out_list);

        $client = self::createClient();
        $client->request('POST', '/rooms/list', [], [], [], $content=null);
        $content = self::json_content_as_array($client->getResponse());
        self::assertEquals(['result' => $out_list], $content);
    }

    public function test_list_rooms_when_empty(){
        $this->controller_mock->method('list_rooms')
            ->willReturn([]);

        $client = self::createClient();
        $client->request('POST', '/rooms/list', [], [], [], $content=null);
        $content = self::json_content_as_array($client->getResponse());
        self::assertEquals(['result' => []], $content);
    }

    public function test_create_room(){
        $room_name = 'joku';
        $room_password = 'pw1';
        $out_id = 321;

        $this->controller_mock->method('create_room')
            ->with(self::equalTo($room_name), self::equalTo($room_password))
            ->willReturn($out_id);

        $client = self::createClient();
        $client->request('POST', '/rooms/create', [], [], [],
            json_encode(['name' => $room_name, 'password' => $room_password]));
        $content = self::json_content_as_array($client->getResponse());
        self::assertEquals(['result' => $out_id], $content);
    }

    public function test_list_users_in_room(){
        $room_id = 'some_id';
        $out_users = [
            ['name' => 'qwerty', 'id' => 404],
            ['name' => 'azerty', 'id' => 500],
        ];

        $this->controller_mock->method('list_users')
            ->with(self::equalTo($room_id))
            ->willReturn($out_users);

        $client = self::createClient();
        $client->request('POST', '/users/list', [], [], [],
            json_encode(['chat_id' => $room_id]));
        $content = self::json_content_as_array($client->getResponse());
        self::assertEquals(['result' => $out_users], $content);
    }

    public function test_join_room(){
        $user_id = 45609;
        $room_id = 8953879;
        $room_password = 'pw1';

        $this->controller_mock->method('join_room')
            ->with(self::equalTo($room_id), self::equalTo($user_id), self::equalTo($room_password))
            ->willReturn(true);

        $client = self::createClient();
        $client->request('POST', '/rooms/join', [], [], [],
            json_encode(['chat_id' => $room_id, 'password' => $room_password, 'user_id' => $user_id]));
        $content = self::json_content_as_array($client->getResponse());
        self::assertEquals(['result' => 1], $content);
    }

    public function test_leave_room(){
        $user_id = 45609;

        $this->controller_mock->method('join_room')
            ->with(self::equalTo($user_id))
            ->willReturn(true);

        $client = self::createClient();
        $client->request('POST', '/rooms/leave', [], [], [],
            json_encode(['user_id' => $user_id]));
        $content = self::json_content_as_array($client->getResponse());
        self::assertEquals(['result' => 1], $content);
    }


    # todo: join + leave room

    public function test_list_messages(){
        $room_id = 'some_id';
        $since = '77';
        $out_messages = [
            ['id' => 90, 'user_id' => 404, 'message' => 'FIRST'],
            ['id' => 101, 'user_d' => 500, 'message' => 'your mom'],
            ['id' => 120, 'user_d' => 500, 'message' => 'isäs oli'],
        ];

        $this->controller_mock->method('list_messages')
            ->with(self::equalTo($room_id), self::equalTo($since))
            ->willReturn($out_messages);

        $client = self::createClient();
        $client->request('POST', '/messages/list', [], [], [],
            json_encode(['chat_id' => $room_id, 'since' => $since]));
        $content = self::json_content_as_array($client->getResponse());
        self::assertEquals(['result' => $out_messages], $content);
    }

    public function test_post_message(){
        $message = 'hello world1!';
        $room_id = 77777777777777;
        $user_id = 1234;
        $out_id = 6346;

        $this->controller_mock->method('post_message')
            ->with(self::equalTo($user_id), self::equalTo($room_id), self::equalTo($message))
            ->willReturn($out_id);

        $client = self::createClient();
        $client->request('POST', '/messages/post', [], [], [],
            json_encode(['chat_id' => $room_id, 'message' => $message, 'user_id' => $user_id]));
        $content = self::json_content_as_array($client->getResponse());
        self::assertEquals(['result' => $out_id], $content);
    }
}