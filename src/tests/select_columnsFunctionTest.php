<?php

namespace StormChat\tests;

use PHPUnit\Runner\Exception;
use PHPUnit_Framework_Error_Notice;
use PHPUnit_Framework_TestCase;
use function StormChat\select_columns;

require_once __DIR__ . '/../autoload.php';

class select_columnsFunctionTest extends PHPUnit_Framework_TestCase
{
    public function test_pick_indices(){
        $arr = [
            ['a', 'b', 'c', 'd'],
            ['Q', 'W', 'E', 'R'],
            ['a', 'z', 'e', 'r']
        ];

        $actual = select_columns($arr, [2, 0]);
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

        $actual = select_columns($arr, ['c', 'a']);
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

        $actual = select_columns($arr, ['c' => 'A', 'a' => 'C']);
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

        $actual = select_columns($arr, ['g', 2, 'c' => 'A', 'a' => 'C']);
        $expected = [
            ['g' => 'g',    3, 'A' => 'c', 'C' => 'a',],
            ['g' => 'U',    6, 'A' => 'E', 'C' => 'Q',],
            ['g' => '???',  9, 'A' => 'e', 'C' => 'a',]
        ];
        self::assertEquals($expected, $actual);
    }

    public function test_handles_empty_array(){
        $arr = [];

        $actual = select_columns($arr, ['g', 2, 'c' => 'A', 'a' => 'C']);
        self::assertEquals([], $actual);
    }

    public function test_remap_with_missing_keys(){
        $arr = [
            ['a' => 'a', 'b' => 'b', 'c' => 'c', 'd' => 'd'],
            ['a' => 'Q', 'b' => 'W', 'd' => 'R'],
            ['b' => 'z', 'c' => 'e', 'd' => 'r']
        ];

        //With substitution:
        $actual = select_columns($arr, ['c', 'a' => 'C'], true);
        $expected = [
            ['c' => 'c', 'C' => 'a',],
            ['c' => null, 'C' => 'Q',],
            ['c' => 'e', 'C' => null,]
        ];
        self::assertEquals($expected, $actual);

        //Without substitution:
        $actual = select_columns($arr, ['c', 'a' => 'C'], null);
        $expected = [
            ['c' => 'c', 'C' => 'a',],
            ['C' => 'Q',],
            ['c' => 'e',]
        ];
        self::assertEquals($expected, $actual);

        self::expectException(\Exception::class);
        //If not allowed:
        try {
            select_columns($arr, ['c', 'a' => 'C'], false);
        }
        catch (\Exception $e){
            throw new \Exception($e);
        }
    }
}
