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
        return remap_keys($rooms, ['name', 'chat_id' => 'id']);
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

    function list_users($chat_id) {
        $users = $this->db_handler->get_chat_users($chat_id);
        return remap_keys($users, ['name', 'user_id' => 'id']);
    }

    function list_messages($chat_id, $since){
        $messages = $this->db_handler->get_messages($since, $chat_id);
        return remap_keys($messages, ['user_id', 'content' => 'message', 'message_id' => 'id']);
    }

    function post_message($user_id, $chat_id, $message) {
        $message_id = $this->db_handler->post_message($user_id, $chat_id, $message);
        return $message_id;
    }

}