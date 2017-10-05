<?php

namespace StormChat\tests;

use PHPUnit_Framework_TestCase;
use function StormChat\remap_keys;

require_once __DIR__ . '/../autoload.php';

class remap_keysFunctionTest extends PHPUnit_Framework_TestCase
{
    public function test_pick_indices(){
        $arr = [
            ['a', 'b', 'c', 'd'],
            ['Q', 'W', 'E', 'R'],
            ['a', 'z', 'e', 'r']
        ];

        $actual = remap_keys($arr, [2, 0]);
        $expected = [
            ['c', 'a'],
            ['E', 'Q'],
            ['e', 'a']
        ];
        self::assertEquals($expected, $actual);
    }

    public function test_pick_keys(){
        $arr = [
            ['a' => 'a', 'b' => 'b', 'c' => 'c', 'd' => 'd'],
            ['a' => 'Q', 'b' => 'W', 'c' => 'E', 'd' => 'R'],
            ['a' => 'a', 'b' => 'z', 'c' => 'e', 'd' => 'r']
        ];

        $actual = remap_keys($arr, ['c', 'a']);
        $expected = [
            ['c' => 'c', 'a' => 'a',],
            ['c' => 'E', 'a' => 'Q',],
            ['c' => 'e', 'a' => 'a',]
        ];
        self::assertEquals($expected, $actual);
    }

    public function test_remap_keys(){
        $arr = [
            ['a' => 'a', 'b' => 'b', 'c' => 'c', 'd' => 'd'],
            ['a' => 'Q', 'b' => 'W', 'c' => 'E', 'd' => 'R'],
            ['a' => 'a', 'b' => 'z', 'c' => 'e', 'd' => 'r']
        ];

        $actual = remap_keys($arr, ['c' => 'A', 'a' => 'C']);
        $expected = [
            ['A' => 'c', 'C' => 'a',],
            ['A' => 'E', 'C' => 'Q',],
            ['A' => 'e', 'C' => 'a',]
        ];
        self::assertEquals($expected, $actual);
    }

    public function test_mixed_remaps_and_picks(){
        $arr = [
            [1, 2, 3, 'a' => 'a', 'b' => 'b', 'c' => 'c', 'd' => 'd', 'e' => 'e', 'f' => 'f', 'g' => 'g'],
            [4, 5, 6, 'a' => 'Q', 'b' => 'W', 'c' => 'E', 'd' => 'R', 'e' => 'T', 'f' => 'Y', 'g' => 'U'],
            [7, 8, 9, 'a' => 'a', 'b' => 'z', 'c' => 'e', 'd' => 'r', 'e' => 't', 'f' => 'y', 'g' => '???']
        ];

        $actual = remap_keys($arr, ['g', 2, 'c' => 'A', 'a' => 'C']);
        $expected = [
            ['g' => 'g',    3, 'A' => 'c', 'C' => 'a',],
            ['g' => 'U',    6, 'A' => 'E', 'C' => 'Q',],
            ['g' => '???',  9, 'A' => 'e', 'C' => 'a',]
        ];
        self::assertEquals($expected, $actual);
    }

    public function test_handles_empty_array(){
        $arr = [];

        $actual = remap_keys($arr, ['g', 2, 'c' => 'A', 'a' => 'C']);
        self::assertEquals([], $actual);
    }
}
