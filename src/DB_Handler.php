<?php

namespace StormChat;


class DB_Handler {


    private $passwd = "";
    private $user = "";

    function connect() {
        $file = fopen("pass.txt", "r");
        $this->user = fgets($file);
        $this->passwd = fgets($file);

        $connection = mysqli_connect("localhost", "TEST_OTP_API", $this->passwd, $this->user);
        return $connection;
    }

    function get_groups() {
        $date = getdate();
        $year = $date['year'];
        $month = $date['mon'];
        $day = $date['mday'];
        $connection = $this->connect();
        // handler->selec(table, [col1, col2], "col1 > 0 AND col2 = 0")

        $sql = "SELECT name 
        FROM chat WHERE created BETWEEN '$year-$month-$day 23:59:59' AND '$year-".($month - 1)."-$day 00:00:00'";

        $groups = [];
        $result = $connection->query($sql);
        while ($row = $result->fetch_assoc()) {
            array_push($groups, $row["name"]);
        }

        return $groups;

    }

    function create_user($name, $password) {
        //palauta id ja token (tehty)
        $token = sha1($password);
        $connection = DB_Handler.get_connection();
        $created = date("Y-m-d h:i:s");
        $sql = $connection->prepare("INSERT INTO user (user_id, name, token, created) VALUES (?, ?, ?, ?)");
        $sql->bind_param("ssis", NULL, $name, $token, $created);
        $sql->execute();
        $id= $connection->insert_id;
        $connection->close();

        return [$token, $id];
    }

    function get_messages($user_id, $chat_id) {
        $connection = DB_Handler.get_connection();
        $sql = "SELECT content 
        FROM message WHERE user_id='$user_id' AND chat_id='$chat_id'";

        $messages = [];
        $result = $connection->query($sql);
        while ($row = $result->fetch_assoc()) {
            array_push($messages, $row["content"]);
        }

        $connection->close();
        return $messages;
    }

    function post_message($user_id, $chat_id, $message) {
        $connection = DB_handler.get_connection();
        $created = date("Y-m-d h:i:s");
        $sql = $connection->prepare("INSERT INTO message (chat_id, user_id, content, created) VALUES (?, ?, ?, ?)");
        $sql->bind_param("iiss", $chat_id, $user_id, $message, $created);
        $sql->execute();

        $connection->close();

    }


}