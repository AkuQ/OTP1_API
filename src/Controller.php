<?php
namespace StormChat;



class Controller {
    public function __construct(DB_Handler $db) {
        $this->db_handler = $db;
    }

    public function create_user($name){
        $token = base64_encode(openssl_random_pseudo_bytes(64));
        $user_id = $this->db_handler->create_user($name, $token);
        return ['id' => $user_id, 'token' => $token];
    }

    public function list_rooms() {
        $rooms = $this->db_handler->get_groups();
        return select_columns($rooms, ['name', 'chat_id' => 'id']);
    }

    public function create_room($name, $password){
        $room_id = $this->db_handler->create_group($name, $password);
        return $room_id;
    }

    public function join_room($chat_id, $user_id, $password){
        $success = $this->db_handler->join_chat($chat_id, $user_id, $password);
        return $success;
    }

    public function leave_room($user_id){
        $this->db_handler->leave_chat($user_id);
    }

    public function is_user_in_room($user_id, $chat_id){
        $user = $this->db_handler->get_user($user_id);
        return $user && $chat_id == $user['chat_id'];
    }

    public function list_users($chat_id) {
        $users = $this->db_handler->get_chat_users($chat_id);
        return select_columns($users, ['name', 'user_id' => 'id']);
    }

    public function list_messages($chat_id, $since){
        $messages = $this->db_handler->get_messages($since, $chat_id);
        return select_columns($messages, ['user_id', 'content' => 'message', 'message_id' => 'id']);
    }

    public function post_message($user_id, $chat_id, $message) {
        $message_id = $this->db_handler->post_message($user_id, $chat_id, $message);
        return $message_id;
    }

    public function get_workspace_content($chat_id){
        $content = $this->db_handler->get_workspace_content($chat_id);
        return $content;
    }

    public function get_workspace_updates($chat_id, $since=0, $caret_pos=0) {
        $updates = $this->db_handler->get_workspace_updates($chat_id, $since);
        $caret_pos = $this->get_caret_pos($caret_pos, $updates);
        $updates = select_columns($updates, ['user_id', 'mode', 'pos', 'input', 'len', 'update_id' => 'id']);
        return ['updates' => $updates, 'caret_pos' => $caret_pos];
    }

    public function workspace_insert($chat_id, $user_id, $pos, $since, $input) {
        $updates = $this->db_handler->get_workspace_updates($chat_id, $since);
        $pos = $this->get_caret_pos($pos, $updates, $user_id);

        $content = $this->db_handler->get_workspace_content($chat_id)['content'];
        $content = substr($content, 0, $pos) . $input . substr($content, $pos);
        $this->db_handler->set_workspace_content($chat_id, $content);

        $update_id = $this->db_handler->workspace_insert($chat_id, $user_id, $pos, $input);
        return $update_id;
    }

    public function workspace_remove($chat_id, $user_id, $pos, $since, $len) {
        $updates = $this->db_handler->get_workspace_updates($chat_id, $since);
        $pos = $this->get_caret_pos($pos, $updates, $user_id);

        $content = $this->db_handler->get_workspace_content($chat_id)['content'];
        $content = substr($content, 0, $pos - $len) . substr($content, $pos);
        $this->db_handler->set_workspace_content($chat_id, $content);

        $update_id = $this->db_handler->workspace_remove($chat_id, $user_id, $pos, $len);
        return $update_id;
    }


    ///////////
    //HELPERS:

    private static function get_caret_pos($caret_pos, array &$inout_updates, $user_id=0){
        foreach ($inout_updates as &$row) {
            if($user_id == $row['user_id']) {
                continue;
            }
            elseif($row['mode'] == 0) {
                $row['mode'] = 'insert';
                $row['len'] = strlen($row['input']);
                $caret_pos += ($row['pos'] <= $caret_pos) ? $row['len'] : 0;
            }
            else {
                $row['mode'] = 'remove';
                $row['len'] = strlen($row['input']);

                if($row['pos'] <= $caret_pos) {
                    $caret_pos -= $row['len'];
                }
                elseif($row['pos'] - $row['len'] <= $caret_pos) {
                    $caret_pos = $row['pos'] - $row['len'];
                }
                $row['input'] = '';
            }
        } unset($row);
        return $caret_pos;
    }
}