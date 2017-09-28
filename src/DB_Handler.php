<?php

namespace StormChat;


class DB_Handler {

    private $host = "";
    private $db = "";
    private $passwd = "";
    private $user = "";

    function __construct($filename) {
        $file = fopen($filename, "r");
        $this->host = trim(fgets($file));
        $this->db = trim(fgets($file));
        $this->user = trim(fgets($file));
        $this->passwd = trim(fgets($file));
        fclose($file);
    }

    function connect() {
        $connection = mysqli_connect($this->host, $this->user, $this->passwd, $this->db);
        return $connection;
    }

    function get_groups() {
        $date = getdate();
        $year = $date['year'];
        $month = $date['mon'];
        $day = $date['mday'];
        $connection = $this->connect();
        $sql = "SELECT * 
        FROM chat WHERE created BETWEEN '$year-".($month - 1)."-$day 00:00:00' AND  '$year-$month-$day 23:59:59'";
        $groups = [];
        $result = $connection->query($sql);
        while ($row = $result->fetch_assoc()) {
            $groups[] = $row;
        }
        $connection->close();
        return $groups;

    }



    function join_chat($chat_id, $user_id, $password) {

        $connection = $this->connect();
        $sql = "SELECT password FROM chat WHERE chat_id='$chat_id'";
        $hashed = hash('sha256', $password);
        $result = $connection->query($sql);

        $row = $result->fetch_assoc();
        if ($row["password"] === $hashed) {
            $updated = date("Y-m-d H:i:s");
            $sql = "UPDATE user SET chat_id='$chat_id', updated='$updated' WHERE user_id='$user_id'";
            $connection->query($sql);
            $connection->close();

            return true;
        }
        $connection->close();
        return false;

    }

    function leave_chat($user_id) {
        $connection = $this->connect();
        $updated = date("Y-m-d H:i:s");
        $sql = "UPDATE user SET chat_id=null, updated='$updated' WHERE user_id='$user_id'";
        $connection->query($sql);
        $connection->close();
    }

    function create_user($token, $name) {
        $connection = $this->connect();
        $created = date("Y-m-d H:i:s");
        $sql = $connection->prepare("INSERT INTO user (name, token, created, updated) VALUES (?, ?, ?, ?)");
        $sql->bind_param("ssss", $name, $token, $created, $created);
        $sql->execute();
        $id = $connection->insert_id;
        $connection->close();
        return $id;
    }

    function create_group($name, $password) {
        $connection = $this->connect();
        $sql = $connection->prepare("INSERT INTO chat (name, password, created, updated) VALUES (?, ?, ?, ?)");
        $hashed = hash('sha256', $password);
        $created = date("Y-m-d H:i:s");
        $sql->bind_param("ssss", $name, $hashed, $created, $created);
        $sql->execute();
        $id = $connection->insert_id;
        $connection->close();
        return $id;
    }

    function get_messages($since_message_id, $chat_id) {
        $connection = $this->connect();
        $sql = "SELECT * 
        FROM message WHERE message_id > '$since_message_id' AND chat_id='$chat_id'";
        $messages = [];
        $result = $connection->query($sql);
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        $connection->close();
        return $messages;
    }

    function post_message($user_id, $chat_id, $message) {
        $connection = $this->connect();
        $created = date("Y-m-d H:i:s");
        $sql = $connection->prepare("INSERT INTO message (chat_id, user_id, content, created) VALUES (?, ?, ?, ?)");
        $sql->bind_param("iiss", $chat_id, $user_id, $message, $created);
        $sql->execute();
        $connection->close();

    }

    function get_chat_users($chat_id) {
        $created = new \DateTime(date("Y-m-d H:i:s"));
        $created->modify("-10 second");
        $created = $created->format("Y-m-d H:i:s");
        $connection = $this->connect();
        $sql = "SELECT * 
        FROM user WHERE chat_id='$chat_id' AND updated > '$created'";
        $users = [];
        $result = $connection->query($sql);
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    }


}