<?php

namespace StormChat;


class DB_Handler
{
    private $host = "";
    private $db = "";
    private $passwd = "";
    private $user = "";

    /**
     * DB_Handler constructor.
     * @param array $config Array containing keys 'host', 'user', 'password' and 'db'.
     */
    function __construct(array $config)
    {
        $this->host = $config['host'];
        $this->user = $config['user'];
        $this->passwd = $config['password'];
        $this->db = $config['db'];
    }

    /**
     * Connects to the database, returns mysqli connection object.
     * @return \mysqli
     */
    function connect()
    {
        $connection = mysqli_connect($this->host, $this->user, $this->passwd, $this->db);
        return $connection;
    }

    /**
     * Fetches all joinable groups from the database.
     * @return array
     */
    function get_groups()
    {
        $date = getdate();
        $year = $date['year'];
        $month = $date['mon'];
        $day = $date['mday'];
        $connection = $this->connect();
        $sql = "SELECT * 
        FROM chat WHERE created BETWEEN '$year-" . ($month - 1) . "-$day 00:00:00' AND  '$year-$month-$day 23:59:59'";
        $groups = [];
        $result = $connection->query($sql);
        while ($row = $result->fetch_assoc()) {
            $groups[] = $row;
        }
        $connection->close();
        return $groups;
    }

    /**
     * Joins user to chat group.
     * Returns true if succeful.
     * @return boolean
     */
    function join_chat($chat_id, $user_id, $password)
    {

        $connection = $this->connect();
        $sql = "SELECT password FROM chat WHERE chat_id='$chat_id'";
        $hashed = hash('sha256', $password);
        $result = $connection->query($sql);

        $row = $result->fetch_assoc();
        if ($row["password"] === $hashed) {
            $updated = date("Y-m-d H:i:s");
            $sql = "UPDATE user SET chat_id='$chat_id', updated='$updated', is_online=1 WHERE user_id='$user_id'";
            $connection->query($sql);
            $connection->close();

            return true;
        }
        $connection->close();
        return false;

    }

    /**
     * Removes user from chat group.
     */
    function leave_chat($user_id)
    {
        $connection = $this->connect();
        $updated = date("Y-m-d H:i:s");
        $sql = "UPDATE user SET chat_id=null, updated='$updated', is_online=0 WHERE user_id='$user_id'";
        $connection->query($sql);
        $connection->close();
    }
    /**
     * Creates user and returns user id.
     * @return int
     */
    function create_user($name, $token)
    {
        $connection = $this->connect();
        $created = date("Y-m-d H:i:s");
        $sql = $connection->prepare("INSERT INTO user (name, token, created, updated) VALUES (?, ?, ?, ?)");
        $sql->bind_param("ssss", $name, $token, $created, $created);
        $sql->execute();
        $id = $connection->insert_id;
        $connection->close();
        return $id;
    }

    /**
    * Get user by ID, returns NULL if user not found.
    * @return array|null
    */
    function get_user($id)
    {
        $connection = $this->connect();
        $sql = "SELECT * FROM user WHERE user_id = '$id'";
        $result = $connection->query($sql);
        $row = $result->fetch_assoc();
        $connection->close();
        return $row;
    }

    /**
     * Creates group with name and password, returns group id.
     * @return int
     */
    function create_group($name, $password)
    {
        $connection = $this->connect();
        $sql = $connection->prepare("INSERT INTO chat (name, password, created, updated) VALUES (?, ?, ?, ?)");
        $hashed = hash('sha256', $password);
        $created = date("Y-m-d H:i:s");
        $sql->bind_param("ssss", $name, $hashed, $created, $created);
        $sql->execute();
        $id = $connection->insert_id;

        $sql = $connection->prepare("INSERT INTO workspace (chat_id) VALUES (?)");
        $sql->bind_param("i", $id); # todo: test workspace creation

        $connection->close();
        return $id;
    }

    /**
     * Fetches messages for certain group from database.
     * @return array
     */
    function get_messages($since_message_id, $chat_id)
    {
        $connection = $this->connect();
        $sql = "SELECT * FROM message WHERE message_id > '$since_message_id' AND chat_id='$chat_id'";
        $messages = [];
        $result = $connection->query($sql);
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        $connection->close();
        return $messages;
    }

    /**
     * Inserts message send by user to database. Returns message id.
     * @return int
     */
    function post_message($user_id, $chat_id, $message)
    {
        $connection = $this->connect();
        $created = date("Y-m-d H:i:s");
        $sql = $connection->prepare("INSERT INTO message (chat_id, user_id, content, created) VALUES (?, ?, ?, ?)");
        $sql->bind_param("iiss", $chat_id, $user_id, $message, $created);
        $sql->execute();
        $id = $connection->insert_id;
        $connection->close();
        return $id;
    }

    /**
     * Fetches users for chat. Returns array of users.
     * @return array
     */
    function get_chat_users($chat_id)
    {
        $connection = $this->connect();
        $sql = "SELECT * FROM user WHERE chat_id='$chat_id' AND is_online = 1";
        $users = [];
        $result = $connection->query($sql);
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    }

    function get_workspace_content($chat_id) {
        $connection = $this->connect();
        $sql = "SELECT * FROM workspace WHERE chat_id=$chat_id";
        $result = $connection->query($sql);
        if ($row = $result->fetch_assoc()) {
            return $row['content'];
        }
        else return "";
    }

    function set_workspace_content($chat_id, $content) {
        $connection = $this->connect();
        $sql = "UPDATE workspace SET content='$content' WHERE chat_id=$chat_id";
        $connection->query($sql);
        return true;
    }

    function get_workspace_updates($last_update_id, $chat_id) {
        $connection = $this->connect();
        $sql = "SELECT * FROM workspace_updates WHERE chat_id=$chat_id AND update_id > $last_update_id";
        $updates = [];
        $result = $connection->query($sql);
        while ($row = $result->fetch_assoc()) {
            $updates[] = $row;
        }
        return $updates;
    }

    function workspace_insert($chat_id, $user_id, $pos, $content){
        $mode = 0;

        $connection = $this->connect();
        $sql = $connection->prepare("INSERT INTO message (chat_id, user_id, pos, mode, input) VALUES (?, ?, ?, ?, ?)");
        $sql->bind_param("iiiis", $chat_id, $user_id, $pos, $mode, $content);
        $sql->execute();
        $id = $connection->insert_id;
        $connection->close();
        return $id;
    }

    function workspace_remove($chat_id, $user_id, $pos, $len){
        $mode = 1;
        $content = str_repeat(' ', $len);

        $connection = $this->connect();
        $sql = $connection->prepare("INSERT INTO message (chat_id, user_id, pos, mode, input) VALUES (?, ?, ?, ?, ?)");
        $sql->bind_param("iiiis", $chat_id, $user_id, $pos, $mode, $content);
        $sql->execute();
        $id = $connection->insert_id;
        $connection->close();
        return $id;
    }

}