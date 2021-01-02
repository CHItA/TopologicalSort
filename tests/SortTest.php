<?php
/*
 * Copyright 2020 Máté Bartus
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

namespace CHItA\TopologicalSort\Test;

use function CHItA\TopologicalSort\topologicalSort;
use PHPUnit\Framework\TestCase;

class SortTest extends TestCase
{
    /**
     * @dataProvider getTestData
     */
    public function testArrays($nodes, $edges, $expected)
    {
        $result = topologicalSort($nodes, $edges);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider getTestData
     */
    public function testReverse($nodes, $edges, $expected)
    {
        $result = topologicalSort($nodes, $edges, true);
        $this->assertEquals(array_reverse($expected), $result);
    }

    /**
     * @dataProvider getTestData
     */
    public function testEdgeCallback($nodes, $edges, $expected)
    {
        $callback = function ($node) use ($edges, $nodes) {
            $index = array_search($node, $nodes);
            return $edges[$index];
        };

        $result = topologicalSort($nodes, $callback);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider getTestData
     */
    public function testAction($nodes, $edges, $expected)
    {
        $result = [];
        $callback = function ($node) use (&$result) {
            $result[] = $node;
        };

        topologicalSort($nodes, $edges, false, $callback);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider getTestData
     */
    public function testFilter($nodes, $edges, $expected)
    {
        $skip = 'c';

        $callback = function ($node) use ($skip) {
            return $node === $skip;
        };

        $index = array_search($skip, $expected);
        unset($expected[$index]);

        foreach ($edges as &$edgesFromNode)
        {
            $index = array_search($skip, $edgesFromNode);
            if ($index !== false)
            {
                unset($edgesFromNode[$index]);
            }
        }

        $result = topologicalSort($nodes, $edges, false, null, $callback);
        $this->assertEquals(array_values($expected), $result);
    }

    public function getTestData()
    {
        return [
            [['a', 'b', 'c', 'd'], [['b', 'c'], ['c', 'd'], ['d'], []], ['a', 'b', 'c', 'd']]
        ];
    }
}
