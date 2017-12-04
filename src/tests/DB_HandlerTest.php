<?php

namespace StormChat {
    require_once __DIR__ . "/../autoload.php";

    class FakeDate {
        public static $date = ['year' => 2000, 'mon' => 11, 'mday' => 11, 'h' => 23, 'i' => 40, 's' => 20];


        public static function date($format) {
            $date = FakeDate::$date;
            $date = $date['year'] . "-" . $date['mon'] . "-" . $date['mday'] . " " .
                $date['h'] . ":" . $date['i'] . ":" . $date['s'];
            $date = new \DateTime($date);
            return $date->format($format);
        }
    }

    function getdate()
    {
        return FakeDate::$date;
    }

    function date($format)
    {
       return FakeDate::date($format);
    }
}

namespace StormChat\tests {

    use PHPUnit_Framework_TestCase;
    use StormChat\DB_Handler;
    use StormChat\FakeDate;

    require_once __DIR__ . "/../autoload.php";


    class DB_HandlerTest extends PHPUnit_Framework_TestCase
    {
        /** @var \mysqli */
        private static $connection = null;
        /** @var DB_Handler */
        private static $handler = null;

        static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            self::$handler = new DB_Handler([
                'host' => 'localhost',
                'user' => 'TEST_OTP_API',
                'password' => 'password1',
                'db' => 'TEST_OTP_API'
            ]);
            self::$connection = self::$handler->connect();
            $sql_create_tables = file_get_contents(__DIR__ . "/../../db/create_tables.sql");
            $sql = explode(";", $sql_create_tables);
            print self::$connection->error;
            self::$connection->query("SET FOREIGN_KEY_CHECKS = 0");
            self::$connection->query("DROP TABLE IF EXISTS chat, user, message, workspace, workspace_line, line_lock");
            self::$connection->query("SET FOREIGN_KEY_CHECKS = 1");
            foreach ($sql as $item) {
                if($item) {
                    self::$connection->query($item);
                }
            }
        }

        function setUp()
        {
            parent::setUp(); // TODO: Change the autogenerated stub
            self::$connection->query("SET FOREIGN_KEY_CHECKS = 0");
            self::$connection->query("TRUNCATE chat");
            self::$connection->query("TRUNCATE user");
            self::$connection->query("TRUNCATE message");
            self::$connection->query("TRUNCATE workspace");
            self::$connection->query("TRUNCATE workspace_line");
            self::$connection->query("TRUNCATE line_lock");
            self::$connection->query("SET FOREIGN_KEY_CHECKS = 1");
        }

        static function tearDownAfterClass()
        {
            parent::tearDownAfterClass();
            self::$connection->close();// TODO: Change the autogenerated stub
        }

        public function testGetChatGroups()
        {
            $date = &FakeDate::$date;
            $date['year'] = 2000;
            $id_1 = self::$handler->create_group("ryhma1", "pw1");
            $date['year'] = 1800;
            self::$handler->create_group("ryhma2", "pw2");
            $date['year'] = 2000;
            $groups = self::$handler->get_groups();
            $this->assertEquals(1, count($groups));
            $this->assertEquals("ryhma1", $groups[0]["name"]);
            $this->assertEquals($id_1, $groups[0]["chat_id"]);
        }

        public function testCreateUser()
        {
            $return = self::$handler->create_user("arto", "randomtoken");
            $result = self::$connection->query("SELECT * FROM user");
            $row = $result->fetch_assoc();
            $this->assertEquals("arto", $row["name"]);
            $this->assertEquals("randomtoken", $row["token"]);

            $this->assertEquals($return, $row["user_id"]);
            $this->assertEquals(FakeDate::date("Y-m-d H:i:s"), $row["created"]);
            $this->assertEquals(FakeDate::date("Y-m-d H:i:s"), $row["updated"]);
        }

        public function testGetUser()
        {
            $user = [
                'user_id' => $user_id = 777,
                'name' => $name = 'aku',
                'token' => $token = 'test',
                'created' => $created = '2000-01-01 00:00:00',
                'updated' => $created,
                'chat_id' => null,
                'is_online' => 0,
            ];
            $ans = self::$connection->query(
                "INSERT INTO `user`(user_id,`name`,token,created,updated) " .
                "VALUES ($user_id,'$name','$token','$created','$created')"
            );
            $return = self::$handler->get_user($user_id);
            $this->assertEquals($user, $return);

            $return = self::$handler->get_user('no_such_user');
            $this->assertNull($return);
        }

        public function testCreateGroup()
        {
            self::$handler->create_group("ryhmaa", "pw1");
            self::$handler->create_group("ryhmab", "pw2");
            $result = self::$connection->query("SELECT * FROM chat");
            $pass_one = hash('sha256', "pw1");
            $pass_two = hash('sha256', "pw2");
            $row = $result->fetch_assoc();
            $this->assertEquals("ryhmaa", $row["name"]);
            $this->assertEquals(1, $row["chat_id"]);
            $this->assertEquals($pass_one, $row["password"]);
            $this->assertEquals(FakeDate::date("Y-m-d H:i:s"), $row["created"]);
            $this->assertEquals(FakeDate::date("Y-m-d H:i:s"), $row["updated"]);
            $row = $result->fetch_assoc();
            $this->assertEquals("ryhmab", $row["name"]);
            $this->assertEquals(2, $row["chat_id"]);
            $this->assertEquals($pass_two, $row["password"]);
            $this->assertEquals(FakeDate::date("Y-m-d H:i:s"), $row["created"]);
            $this->assertEquals(FakeDate::date("Y-m-d H:i:s"), $row["updated"]);
        }

        public function testJoinChat()
        {
            $date = &FakeDate::$date;
            self::$handler->create_group("ryhmaa", "pw1");
            $date["year"] = "1800";
            $user_id = self::$handler->create_user("arto", "randomtoken");
            $date["year"] = "2000";
            $bool = self::$handler->join_chat(1, $user_id, "pw1");
            $this->assertEquals(true, $bool);
            $result = self::$connection->query("SELECT * FROM `user` WHERE `name`='arto'");
            $row = $result->fetch_assoc();
            $date["year"] = "1800";
            $this->assertEquals(FakeDate::date("Y-m-d H:i:s"), $row["created"]);
            $date["year"] = "2000";
            $this->assertEquals(1, $row["chat_id"]);
            $this->assertEquals(FakeDate::date("Y-m-d H:i:s"), $row["updated"]);
            $this->assertEquals(false, self::$handler->join_chat(1, $user_id, "pw2"));
        }

        public function testPostMessage()
        {
            self::$handler->create_group("ryhmaa", "pw1");
            $return = self::$handler->create_user("randomtoken", "arto");
            self::$handler->join_chat(1, $return["id"], "pw1");
            $id_1 = self::$handler->post_message(1, 1, "Hello world");
            $id_2 = self::$handler->post_message(1, 1, "dlrow elloH");
            $result = self::$connection->query("SELECT * FROM message");
            $row = $result->fetch_assoc();
            $this->assertEquals(FakeDate::date("Y-m-d H:i:s"), $row["created"]);
            $this->assertEquals(1, $row["chat_id"]);
            $this->assertEquals(1, $row["message_id"]);
            $this->assertEquals(1, $id_1);
            $this->assertEquals(1, $row["user_id"]);
            $this->assertEquals("Hello world", $row["content"]);

            $row = $result->fetch_assoc();
            $this->assertEquals(FakeDate::date("Y-m-d H:i:s"), $row["created"]);
            $this->assertEquals(1, $row["chat_id"]);
            $this->assertEquals(2, $row["message_id"]);
            $this->assertEquals(2, $id_2);
            $this->assertEquals(1, $row["user_id"]);
            $this->assertEquals("dlrow elloH", $row["content"]);
        }

        public function testGetChatUsers()
        {
            self::$handler->create_group("ryhmaa", "pw1");

            $user_id = self::$handler->create_user("arto", "randomtoken");
            self::$handler->join_chat(1, $user_id, "pw1");

            $user_id = self::$handler->create_user("tuomas", "randomtoken");
            self::$handler->join_chat(1, $user_id, "pw1");

            $user_id = self::$handler->create_user("akseli", "randomtoken");
            self::$handler->join_chat(1, $user_id, "pw1");

            $users = self::$handler->get_chat_users(1);
            $this->assertEquals("arto", $users[0]["name"]);
            $this->assertEquals("tuomas", $users[1]["name"]);
            $this->assertEquals("akseli", $users[2]["name"]);

            self::$handler->leave_chat($users[0]['user_id']);
            self::$handler->leave_chat($users[1]['user_id']);
            self::$handler->leave_chat($users[2]['user_id']);
            $users = self::$handler->get_chat_users(1);
            $this->assertEquals(true, empty($users));
        }

        public function testLeaveChat()
        {
            self::$handler->create_group("ryhmaa", "pw1");
            $user_id = self::$handler->create_user("arto", "randomtoken");
            self::$handler->join_chat(1, $user_id, "pw1");
            $users = self::$handler->get_chat_users(1);
            $this->assertEquals("arto", $users[0]["name"]);
            self::$handler->leave_chat(1);
            $users = self::$handler->get_chat_users(1);
            $this->assertEquals(true, empty($users));
        }

        public function testGetMessages()
        {
            self::$handler->create_group("ryhmaa", "pw1");
            $return = self::$handler->create_user("arto", "randomtoken");
            self::$handler->join_chat(1, $return["id"], "pw1");
            self::$handler->post_message(1, 1, "Hello world");
            self::$handler->post_message(1, 1, "dlrow elloH");
            self::$handler->post_message(1, 1, "A");
            self::$handler->post_message(1, 1, "B");
            self::$handler->post_message(1, 1, "C");
            $messages = self::$handler->get_messages(0, 1);
            $this->assertEquals(5, count($messages));
            $this->assertEquals("Hello world", $messages[0]["content"]);
            $this->assertEquals("dlrow elloH", $messages[1]["content"]);
            $this->assertEquals(1, $messages[0]["user_id"]);
            $this->assertEquals(1, $messages[0]["chat_id"]);
            $this->assertEquals(1, $messages[0]["message_id"]);
            $this->assertEquals(2, $messages[1]["message_id"]);
            $messages = self::$handler->get_messages(4, 1);
            $this->assertEquals(1, count($messages));
        }


        public function testSetGetWorkspaceContent() {
            $expected_content = 'Hello World!';

            self::assertInternalType('int',
                $chat_id = self::$handler->create_group("ryhmaa", "pw1")
            );
            self::assertTrue(
                self::$handler->set_workspace_content($chat_id, $expected_content)
            );
            self::assertEquals(['content' => $expected_content, 'last_update' => 0],
                self::$handler->get_workspace_content($chat_id)
            );
        }

        public function testWorkspaceUpdatesInsertRemoveGet(){
            self::assertInternalType('int',
                $chat_id = self::$handler->create_group("ryhmaa", "pw1")
            );
            self::assertInternalType('int',
                $user_id = self::$handler->create_user("aku", "sometoken")
            );


            $updates = [
                [0, 'Hello'],
                [strlen('Hello'), 'World'],
                [strlen('Hello'), '   '],
                [strlen('Hello   '), 3],
                [strlen('Hello'), '-'],
                [strlen('Hello-World'), '!'],
            ];
            $update_ids = [
                self::$handler->workspace_insert($chat_id, $user_id, ...$updates[0]),
                self::$handler->workspace_insert($chat_id, $user_id, ...$updates[1]),
                self::$handler->workspace_insert($chat_id, $user_id, ...$updates[2]),
                self::$handler->workspace_remove($chat_id, $user_id, ...$updates[3]),
                self::$handler->workspace_insert($chat_id, $user_id, ...$updates[4]),
                self::$handler->workspace_insert($chat_id, $user_id, ...$updates[5]),
            ];

            $prev_id = -1;
            foreach($update_ids as $id) {
                self::assertInternalType('int', $id);
                self::assertGreaterThan($prev_id, $id);
                $prev_id = $id;
            }

            $expected_updates = [];
            foreach($updates as $i => $u) {
                $expected_updates[] = [
                    'update_id' => (string)$update_ids[$i],
                    'chat_id' => (string)$chat_id,
                    'user_id' => (string)$user_id,
                    'pos' =>  (string)$u[0],
                    'input' => ($i == 3) ? str_repeat(' ', $u[1]) : $u[1],
                    'mode' => ($i == 3) ? '1' : '0',
                ];
            }

            $actual_updates = self::$handler->get_workspace_updates($chat_id, $update_ids[1]);
            array_shift($expected_updates);
            array_shift($expected_updates);
            self::assertEquals($expected_updates, $actual_updates);
        }
    }
}