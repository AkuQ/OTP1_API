<?php

namespace StormChat;

function remap_keys(array $array, array $key_map) {
    $ret = [];

    $i = 0;
    $write_index_counter = 0;
    reset($key_map);

    $read_key = null;
    $write_key = null;
    $done = false;
    $next = function (array &$arr) use(&$read_key, &$write_key, &$done) {
        $each = each($arr);
        if ($each) {
            $read_key = $each[0];
            $write_key = $each[1];
            return true;
        }
        else {
            $read_key = null;
            $write_key = null;
            $done = true;
            return false;
        }
    };

    while($next($key_map) && $read_key === $i) {
        $read_key = $write_key;
        if(is_int($read_key))
            $write_key = $write_index_counter++;
        foreach($array as $row_index => $row) {
            $ret[$row_index][$write_key] = $row[$read_key];
        }
        $i++;
    }
    if(!$done) {
        do {
            foreach ($array as $row_index => $row) {
                $ret[$row_index][$write_key] = $row[$read_key];
            }

        } while ($next($key_map));
    }
    return $ret;
}



