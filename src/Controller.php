<?php
namespace StormChat;

class Controller {

    function get_groups() {
        $date = getdate();
        $year = $date['year'];
        $month = $date['mon'];
        $day = $date['mday'];
        $connection = DB_Handler.get_connection();
       // handler->selec(table, [col1, col2], "col1 > 0 AND col2 = 0")

        $sql = "SELECT chat_id 
        FROM chat WHERE created BETWEEN '$year-$month-$day 23:59:59' AND '$year-".($month - 1)."-$day 00:00:00'";

        //$result = $connection->query($sql);
        $result = DB_Handler.select($sql);
        $chat_ids = [];
        while ($row = $result->fetch_assoc()) {
            array_push($chat_ids, $row["chat_id"]);
        }
        $connection = close();
        return $chat_ids;
    }

    function create_user($name, $password) {
        //palauta id ja token (tehty)

        $connection = DB_Handler.get_connection();
        $created = date("Y-m-d h:i:s");
        $sql = $connection->prepare("INSERT INTO user (name, token, created) VALUES (?, ?, ?)");
        $sql->bind_param("ssis", $name, $token, $created);
        $sql->execute();
        $connection->close();
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