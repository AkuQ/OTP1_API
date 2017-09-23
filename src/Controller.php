<?php
namespace StormChat;

function remap_keys(array $array, array $keys) {
    $ret = [];
    foreach ($keys as $from => $to) {
        if(is_int($from)) {
            $from = $to;
        }
        foreach($array as $i => $row) {
            $ret[$i][$to] = $row[$from];
        }
    }
    return $ret;
}

class Controller {
    function __construct(DB_Handler $db) {
        $this->db_handler = $db;
    }

    function create_user($name){
        $token = openssl_random_pseudo_bytes(128);
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
}