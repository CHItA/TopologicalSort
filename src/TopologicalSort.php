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

namespace CHItA\TopologicalSort;

use Closure;
use InvalidArgumentException;
use LogicException;

/**
 * Topological sort implementation.
 *
 * The function uses Kahn's algorithm to perform a topological sort. The
 * function returns the topologically sorted array of the nodes provided. It is
 * also possible to perform an action on the sorted elements via a callback
 * provided in the action parameter.
 *
 * By providing a Closure in the $filter parameter you can filter out nodes in
 * your set.
 *
 * Further more, it is possible to provide the edges of the graph in two ways:
 *   a) By providing an array of edges, containing the outgoing from each node
 *   b) Providing a callback method which returns the list of the outgoing
 *      edges from any node.
 *
 * You also have the option to define the directed edges of the graph in a
 * reversed order (specifying the incoming coming edges rather than the
 * outgoing ones).
 *
 * @param iterable      $nodes      An array of nodes to sort.
 * @param array|Closure $edges      An array or closure specifying the edges of
 *                                  the graph.
 * @param bool          $flip_edges Whether or not to flip the the directions
 *                                  of the edges in the graph.
 * @param Closure|null  $action     A Closure to be called, once the node is
 *                                  sorted, or null to return the sorted list.
 * @param Closure|null  $filter     A filter function to skip items in the
 *                                  `$nodes` collection.
 *
 * @return array The topologically sorted `$nodes`.
 */
function topologicalSort(
    iterable $nodes,
    $edges,
    bool $flip_edges = false,
    ?Closure $action = null,
    ?Closure $filter = null) : array
{
    if (!is_array($edges) && !($edges instanceof Closure))
    {
        throw new InvalidArgumentException(
            'TopologicalSort(): $edges is neither iterable nor a Closure.'
        );
    }

    if (!($filter instanceof Closure))
    {
        $filter = function($arg) {
            return false;
        };
    }

    if (!($action instanceof Closure))
    {
        $action = function($arg) {};
    }

    $outgoing_storage = [];
    $incoming_storage = [];

    $edges_iterable =& $edges;
    $callback = function ($current, $item) use (&$incoming_storage, &$outgoing_storage) {
        $outgoing_storage[$current] = $item;
        if (!array_key_exists($current, $incoming_storage))
        {
            $incoming_storage[$current] = [];
        }

        foreach ($item as $node)
        {
            $incoming_storage[$node][] = $current;
        }
    };

    if ($edges instanceof Closure)
    {
        $edges_iterable =& $nodes;
        $callback = function ($current, $item) use (&$incoming_storage, &$outgoing_storage, &$edges) {
            $dependencies = $edges($item);
            $outgoing_storage[$current] = $dependencies;
            if (!array_key_exists($current, $incoming_storage))
            {
                $incoming_storage[$current] = [];
            }

            foreach ($dependencies as $node)
            {
                $incoming_storage[$node][] = $current;
            }
        };
    }

    $item_count = 0;
    foreach ($nodes as $node)
    {
        if ($filter($node))
        {
            continue;
        }

        $callback($node, current($edges_iterable));
        next($edges_iterable);
        ++$item_count;
    }

    $incoming_edges =& $incoming_storage;
    $outgoing_edges =& $outgoing_storage;

    if ($flip_edges)
    {
        $incoming_edges =& $outgoing_storage;
        $outgoing_edges =& $incoming_storage;
    }

    $independent_nodes = [];
    foreach ($incoming_edges as $node => $list)
    {
        if (empty($list))
        {
            $independent_nodes[] = $node;
        }
    }

    $sorted = [];
    while (!empty($independent_nodes))
    {
        $current = array_pop($independent_nodes);
        $action($current);
        $sorted[] = $current;
        foreach ($outgoing_edges[$current] as $node)
        {
            $incoming_edges[$node] = array_diff($incoming_edges[$node], [$current]);
            if (empty($incoming_edges[$node]))
            {
                $independent_nodes[] = $node;
            }
        }
    }

    if ($item_count !== count($sorted))
    {
        throw new LogicException(
            'TopologicalSort(): Circular dependency detected.'
        );
    }

    return $sorted;
}
