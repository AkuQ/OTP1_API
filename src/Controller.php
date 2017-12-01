<?php
namespace StormChat;



class Controller {
    function __construct(DB_Handler $db) {
        $this->db_handler = $db;
    }

    function create_user($name){
        $token = base64_encode(openssl_random_pseudo_bytes(64));
        $user_id = $this->db_handler->create_user($name, $token);
        return ['id' => $user_id, 'token' => $token];
    }

    function list_rooms() {
        $rooms = $this->db_handler->get_groups();
        return select_columns($rooms, ['name', 'chat_id' => 'id']);
    }

    function create_room($name, $password){
        $room_id = $this->db_handler->create_group($name, $password);
        return $room_id;
    }

    function join_room($chat_id, $user_id, $password){
        $success = $this->db_handler->join_chat($chat_id, $user_id, $password);
        return $success;
    }

    function leave_room($user_id){
        $this->db_handler->leave_chat($user_id);
    }

    function is_user_in_room($user_id, $chat_id){
        $user = $this->db_handler->get_user($user_id);
        return $user && $chat_id == $user['chat_id'];
    }

    function list_users($chat_id) {
        $users = $this->db_handler->get_chat_users($chat_id);
        return select_columns($users, ['name', 'user_id' => 'id']);
    }

    function list_messages($chat_id, $since){
        $messages = $this->db_handler->get_messages($since, $chat_id);
        return select_columns($messages, ['user_id', 'content' => 'message', 'message_id' => 'id']);
    }

    function post_message($user_id, $chat_id, $message) {
        $message_id = $this->db_handler->post_message($user_id, $chat_id, $message);
        return $message_id;
    }

    function get_workspace_content($chat_id){
        $content = $this->db_handler->get_workspace_content($chat_id);
        return $content;
    }

    function get_workspace_updates($chat_id, $since, $caret_pos) {
        $updates = $this->db_handler->get_workspace_updates($since, $chat_id);
        array_map(function ($row) use (&$caret_pos) {
            if($row['mode'] == 0) {
                $row['mode'] = 'insert';
                $row['len'] = count($row['input']);
                $caret_pos += ($row['pos'] <= $caret_pos) ? $row['len'] : 0;
            }
            else {
                $row['mode'] = 'remove';
                $row['len'] = count($row['input']);
                $row['input'] = '';

                if($row['pos'] <= $caret_pos) {
                    $caret_pos -= $row['len'];
                }
                elseif($row['pos'] - $row['len'] <= $caret_pos) {
                    $caret_pos = $row['pos'] - $row['len'];
                }
            }
        }, $updates);

        $updates = select_columns($updates, ['user_id', 'mode', 'input', 'len', 'update_id' => 'id']);
        return ['updates' => $updates, 'caret_pos' => $caret_pos];
    }

    function workspace_insert($chat_id, $user_id, $pos, $content) {
        $content = $this->db_handler->get_workspace_content($chat_id);
        $content = substr($content, 0, $pos) . $content . substr($content, $pos);
        $this->db_handler->set_workspace_content($chat_id, $content);

        $update_id = $this->db_handler->workspace_insert($chat_id, $user_id, $pos, $content);
        return $update_id;
    }

    function workspace_remove($chat_id, $user_id, $pos, $len) {
        $content = $this->db_handler->get_workspace_content($chat_id);
        $content = substr($content, 0, $pos - $len) . substr($content, $pos);
        $this->db_handler->set_workspace_content($chat_id, $content);

        $update_id = $this->db_handler->workspace_remove($chat_id, $user_id, $pos, $len);
        return $update_id;
    }
}