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
            self::$handler = new DB_Handler(parse_ini_file(__DIR__ . '/config/testdb.ini'));
            self::$connection = self::$handler->connect();
            $sql_create_tables = file_get_contents(__DIR__ . "/../../db/create_tables.sql");
            $sql = explode(";", $sql_create_tables);
            print self::$connection->error;
            self::$connection->query("SET FOREIGN_KEY_CHECKS = 0");
            self::$connection->query("DROP TABLE IF EXISTS chat, user, message, workspace, workspace_line, line_lock");
            self::$connection->query("SET FOREIGN_KEY_CHECKS = 1");
            foreach ($sql as $item) {
                self::$connection->query($item);
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
            $return = self::$handler->create_user("randomtoken", "arto");
            $result = self::$connection->query("SELECT * FROM user");
            $row = $result->fetch_assoc();
            $this->assertEquals("arto", $row["name"]);
            $this->assertEquals("randomtoken", $row["token"]);

            $this->assertEquals($return, $row["user_id"]);
            $this->assertEquals(FakeDate::date("Y-m-d H:i:s"), $row["created"]);
            $this->assertEquals(FakeDate::date("Y-m-d H:i:s"), $row["updated"]);
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
            $user_id = self::$handler->create_user("randomtoken", "arto");
            $date["year"] = "2000";
            $bool = self::$handler->join_chat(1, $user_id, "pw1");
            $this->assertEquals(true, $bool);
            $result = self::$connection->query("SELECT * FROM user WHERE name='arto'");
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
            self::$handler->post_message(1, 1, "Hello world");
            self::$handler->post_message(1, 1, "dlrow elloH");
            $result = self::$connection->query("SELECT * FROM message");
            $row = $result->fetch_assoc();
            $this->assertEquals(FakeDate::date("Y-m-d H:i:s"), $row["created"]);
            $this->assertEquals(1, $row["chat_id"]);
            $this->assertEquals(1, $row["message_id"]);
            $this->assertEquals(1, $row["user_id"]);
            $this->assertEquals("Hello world", $row["content"]);

            $row = $result->fetch_assoc();
            $this->assertEquals(FakeDate::date("Y-m-d H:i:s"), $row["created"]);
            $this->assertEquals(1, $row["chat_id"]);
            $this->assertEquals(2, $row["message_id"]);
            $this->assertEquals(1, $row["user_id"]);
            $this->assertEquals("dlrow elloH", $row["content"]);
        }

        public function testGetChatUsers()
        {
            self::$handler->create_group("ryhmaa", "pw1");

            $user_id = self::$handler->create_user("randomtoken", "arto");
            self::$handler->join_chat(1, $user_id, "pw1");

            $user_id = self::$handler->create_user("randomtoken", "tuomas");
            self::$handler->join_chat(1, $user_id, "pw1");

            $user_id = self::$handler->create_user("randomtoken", "akseli");
            self::$handler->join_chat(1, $user_id, "pw1");

            $users = self::$handler->get_chat_users(1);
            $this->assertEquals("arto", $users[0]["name"]);
            $this->assertEquals("tuomas", $users[1]["name"]);
            $this->assertEquals("akseli", $users[2]["name"]);
            $date = &FakeDate::$date;
            $date["s"] = 31;
            $users = self::$handler->get_chat_users(1);
            $this->assertEquals(true, empty($users));
        }

        public function testLeaveChat()
        {
            self::$handler->create_group("ryhmaa", "pw1");
            $user_id = self::$handler->create_user("randomtoken", "arto");
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
            $return = self::$handler->create_user("randomtoken", "arto");
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
    }
}