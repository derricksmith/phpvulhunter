<?php

namespace PhpAnalyzer;

/**
 * The "RecursiveNodeTraverser" is a clone of the original with one important difference:
 *   when an 'leaveNode' handler returns an array, that array is immediately traversed,
 *   in addition to normal behavior.
 * This allows "require()" and "include()" to be immediately acted-upon, but it does
 *   potentially create issues for 'nikic'-supplied visitors that modify the AST, and
 *   which may not expect to see what they have done.
 */
class RecursiveTraverser implements \PhpParser\NodeTraverserInterface
{
const DONT_TRAVERSE_CHILDREN = 1;
const REMOVE_NODE = false;
    /**
     * @var NodeVisitor[] Visitors
     */
    protected $visitors;

    /**
     * @var bool
     */
    private $cloneNodes;
    
    /*
     * @var bool
     */
    public $addLinks;

    /**
     * Constructs a node traverser.
     *
     * @param bool $cloneNodes Should the traverser clone the nodes when traversing the AST
     */
    public function __construct($cloneNodes = false) {
        $this->visitors = array();
        $this->cloneNodes = $cloneNodes;
        $this->addLinks   = false;              // ... unless set to 'true' by manipulation of the public var
    }

    /**
     * Adds a visitor.
     *
     * @param NodeVisitor $visitor Visitor to add
     */
    public function addVisitor(\PhpParser\NodeVisitor $visitor) {
        $this->visitors[] = $visitor;
    }

    /**
     * Removes an added visitor.
     *
     * @param NodeVisitor $visitor
     */
    public function removeVisitor(\PhpParser\NodeVisitor $visitor) {
        foreach ($this->visitors as $index => $storedVisitor) {
            if ($storedVisitor === $visitor) {
                unset($this->visitors[$index]);
                break;
            }
        }
    }

    /**
     * Traverses an array of nodes using the registered visitors.
     *
     * @param Node[] $nodes Array of nodes
     *
     * @return Node[] Traversed array of nodes
     */
    public function traverse(array $nodes) {
        foreach ($this->visitors as $visitor) {
            if (null !== $return = $visitor->beforeTraverse($nodes)) {
                $nodes = $return;
            }
        }

        $nodes = $this->traverseArray($nodes, null);

        foreach ($this->visitors as $visitor) {
            if (null !== $return = $visitor->afterTraverse($nodes)) {
                $nodes = $return;
            }
        }

        return $nodes;
    }

    protected function traverseNode(\PhpParser\Node $node) {
    
        global $app;
    
        if ($this->cloneNodes) {
            $node = clone $node;
        }

        foreach ($node->getSubNodeNames() as $name) {
            $subNode =& $node->$name;

            if (is_array($subNode)) {
                $subNode = $this->traverseArray($subNode, $node);
            } elseif ($subNode instanceof \PhpParser\Node) {
            
                // Set the 'parent' attribute now.  'enterNode' handlers must have this information.
                //   (These attributes are indexes into a global array so "print_r()" won't recurse its way to hell.)
                if ($this->addLinks) {
                    $subNode->setAttribute('parent', array_push($app->parent_links, $node) - 1);
                }
            
                $traverseChildren = true;
                foreach ($this->visitors as $visitor) {
                    $return = $visitor->enterNode($subNode);
                    if (self::DONT_TRAVERSE_CHILDREN === $return) {
                        $traverseChildren = false;
                    } else if (null !== $return) {
                        $subNode = $return;
                    }
                }

                if ($traverseChildren) {
                    $subNode = $this->traverseNode($subNode);
                }

                foreach ($this->visitors as $visitor) {
                    if (null !== $return = $visitor->leaveNode($subNode)) {
                        $subNode = $return;
                        
                        // If 'leaveNode' returns an array, traverse that array now.
                        if (is_array($return)) $this->traverseArray($return, $node);          // ADDED ...
                    }
                }
            }
        }

        return $node;
    }

    protected function traverseArray(array $nodes, $parent) {
    
        global $app;
    
        $doNodes = array();
        
        /** (added)
         * Create attributes to locate neighboring siblings, and the parent node.
         *   These are created only if their value is not 'null.'
         *
         * Notice that all of these attributes are established BEFORE 'enterNode' is called,
         *   so that these handlers know their positions right away.  (But they can't go
         *   down and expect to find the same, because child-nodes haven't been visited yet.)
         *
         * The 'App' object provides helpful functions to retrieve these.
         */
        if ($this->addLinks) {
            $left = null;
            foreach ($nodes as $i => $node) {
                if ($node instanceof \PhpParser\Node) {
                
                    // Attributes are indexes to a global array so that "print_r()" won't recurse its brains out.

                    if ($parent !== null) $node->setAttribute('parent', array_push($app->parent_links, $parent) - 1);
                
                    if ($left !== null) {
                        $left->setAttribute('next', array_push($app->next_links, $node) - 1);
                        $node->setAttribute('prev', array_push($app->prev_links, $left) - 1);
                    }
                    $left = $node;
                }
                else {
                    // Ignore any non-Nodes, e.g. string-literals such as "register_globals,"
                    //   arrays and so-forth, which might be in the sequence.
                    // Link right past them as though they weren't there.
                }
            }
        }

        foreach ($nodes as $i => &$node) {
            if (is_array($node)) {
                $node = $this->traverseArray($node, $parent);
            } elseif ($node instanceof \PhpParser\Node) {
                $traverseChildren = true;
                foreach ($this->visitors as $visitor) {
                    $return = $visitor->enterNode($node);
                    if (self::DONT_TRAVERSE_CHILDREN === $return) {
                        $traverseChildren = false;
                    } else if (null !== $return) {
                        $node = $return;
                    }
                }

                if ($traverseChildren) {
                    $node = $this->traverseNode($node);
                }

                foreach ($this->visitors as $visitor) {
                    $return = $visitor->leaveNode($node);

                    if (self::REMOVE_NODE === $return) {
                        $doNodes[] = array($i, array());
                        break;
                    } elseif (is_array($return)) {
                        $this->traverseArray($return, $node);          // ADDED ...
                        $doNodes[] = array($i, $return);
                        break;
                    } elseif (null !== $return) {
                        $node = $return;
                    }
                }
            }
        }

        if (!empty($doNodes)) {
            while (list($i, $replace) = array_pop($doNodes)) {
                array_splice($nodes, $i, 1, $replace);
            }
        }

        return $nodes;
    }
}
