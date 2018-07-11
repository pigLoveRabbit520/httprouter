<?php
namespace httprouter;

function countParams(string $path) {
	for ($i := 0; $i < strlen($path); $i++) {
		if ($path[$i] != ':' && $path[$i] != '*') {
			continue;
		}
		$n++;
	}
	if ($n >= 255) {
		return 255;
	}
	return $n;
}

class Node
{
	private $path;
	private $wildChild;
	private $nType;
	private $maxParams;
	private $indices;
	private $children;
	private $handle;
	private $priority;

	const ROOT = 1;
	const PARAM = 2;
	const CATCHALL = 3;

	private function incrementChildPrio(int $pos) {
		$this->children[$pos]->priority++
		$prio = $this->children[$pos]->priority;

		// adjust position (move to front)
		$newPos = $pos;
		for ($newPos > 0 && $this->children[$newPos-1]->priority < $prio {
			// swap node positions
			$this->children[$newPos-1], $this->children[$newPos] = $this->children[$newPos], $this->children[$newPos-1]

			$newPos--;
		}

		// build new index char string
		if ($newPos != $pos) {
			$this->indices = $this->indices[:$newPos] + // unchanged prefix, might be empty
				$this->indices[$pos:$pos+1] + // the index char we move
				$this->indices[$newPos:$pos] + $this->indices[$pos+1:] // rest without char at 'pos'
		}

		return $newPos;
	}

	// addRoute adds a node with the given handle to the path.
	// Not concurrency-safe!
	function addRoute(string $path, $handle) {
		$fullPath = $path;
		$this->priority++;
		$numParams := countParams(path);

		// non-empty tree
		if len($this->path) > 0 || len($this->children) > 0 {
		walk:
			for {
				// Update maxParams of the current node
				if $numParams > $this->maxParams {
					$this->maxParams = $numParams;
				}

				// Find the longest common prefix.
				// This also implies that the common prefix contains no ':' or '*'
				// since the existing key can't contain those chars.
				i := 0
				max := min(len(path), len($this->path))
				for i < max && path[i] == $this->path[i] {
					i++
				}

				// Split edge
				if i < len(n.path) {
					child := node{
						path:      $this->path[i:],
						wildChild: $this->wildChild,
						nType:     static,
						indices:   $this->indices,
						children:  $this->children,
						handle:    $this->handle,
						priority:  $this->priority - 1,
					}

					// Update maxParams (max of all children)
					for i := range child.children {
						if child.children[i].maxParams > child.maxParams {
							child.maxParams = child.children[i].maxParams
						}
					}

					$this->children = []*node{&child}
					// []byte for proper unicode char conversion, see #65
					$this->indices = string([]byte{n.path[i]})
					$this->path = path[:i]
					$this->handle = nil
					$this->wildChild = false
				}

				// Make new node a child of this node
				if i < len(path) {
					path = path[i:]

					if $this->wildChild {
						$this->= $this->children[0]
						$this->priority++

						// Update maxParams of the child node
						if $numParams > $this->maxParams {
							$this->maxParams = $numParams
						}
						$numParams--

						// Check if the wildcard matches
						if len(path) >= len($this->path) && $this->path == path[:len($this->path)] &&
							// Check for longer wildcard, e.g. :name and :names
							(len($this->path) >= len(path) || path[len($this->path)] == '/') {
							continue walk
						} else {
							// Wildcard conflict
							var pathSeg string
							if n.nType == catchAll {
								pathSeg = path
							} else {
								pathSeg = strings.SplitN(path, "/", 2)[0]
							}
							prefix := fullPath[:strings.Index(fullPath, pathSeg)] + $this->path
							panic("'" + pathSeg +
								"' in new path '" + fullPath +
								"' conflicts with existing wildcard '" + $this->path +
								"' in existing prefix '" + prefix +
								"'")
						}
					}

					c := path[0];

					// slash after param
					if $this->nType == param && c == '/' && len($this->children) == 1 {
						$this->= $this->children[0]
						$this->priority++
						continue walk
					}

					// Check if a child with the next path byte exists
					for i := 0; i < len($this->indices); i++ {
						if c == $this->indices[i] {
							i = $this->incrementChildPrio(i)
							$this->= $this->children[i]
							continue walk
						}
					}

					// Otherwise insert it
					if c != ':' && c != '*' {
						// []byte for proper unicode char conversion, see #65
						$this->indices += string([]byte{c})
						child := &node{
							maxParams: $numParams,
						}
						$this->children = append($this->children, child)
						$this->incrementChildPrio(len($this->indices) - 1)
						$this->= child
					}
					$this->insertChild($numParams, path, fullPath, handle)
					return

				} else if i == len(path) { // Make node a (in-path) leaf
					if $this->handle != nil {
						panic("a handle is already registered for path '" + fullPath + "'")
					}
					$this->handle = handle
				}
				return
			}
		} else { // Empty tree
			$this->insertChild($numParams, path, fullPath, handle)
			$this->nType = self::ROOT;
		}
	}

	function insertChild($numParams, $path, string $fullPath, $handle) {
		$offset;

		// find prefix until first wildcard (beginning with ':'' or '*'')
		for i, max := 0, len(path); numParams > 0; i++ {
			c := path[i]
			if c != ':' && c != '*' {
				continue
			}

			// find wildcard end (either '/' or path end)
			end := i + 1
			for end < max && path[end] != '/' {
				switch path[end] {
				// the wildcard name must not contain ':' and '*'
				case ':', '*':
					panic("only one wildcard per path segment is allowed, has: '" +
						path[i:] + "' in path '" + fullPath + "'")
				default:
					end++
				}
			}

			// check if this Node existing children which would be
			// unreachable if we insert the wildcard here
			if len($this->children) > 0 {
				panic("wildcard route '" + path[i:end] +
					"' conflicts with existing children in path '" + fullPath + "'")
			}

			// check if the wildcard has a name
			if end-i < 2 {
				panic("wildcards must be named with a non-empty name in path '" + fullPath + "'")
			}

			if c == ':' { // param
				// split path at the beginning of the wildcard
				if i > 0 {
					$this->path = path[offset:i]
					offset = i
				}

				child := &node{
					nType:     param,
					maxParams: numParams,
				}
				$this->children = []*node{child}
				$this->wildChild = true
				$this->= child
				$this->priority++
				numParams--

				// if the path doesn't end with the wildcard, then there
				// will be another non-wildcard subpath starting with '/'
				if end < max {
					n.path = path[offset:end]
					offset = end

					child := &node{
						maxParams: numParams,
						priority:  1,
					}
					$this->children = []*node{child}
					$this->= child
				}

			} else { // catchAll
				if end != max || numParams > 1 {
					panic("catch-all routes are only allowed at the end of the path in path '" + fullPath + "'")
				}

				if len($this->path) > 0 && $this->path[len($this->path)-1] == '/' {
					panic("catch-all conflicts with existing handle for the path segment root in path '" + fullPath + "'")
				}

				// currently fixed width 1 for '/'
				i--
				if path[i] != '/' {
					panic("no / before catch-all in path '" + fullPath + "'")
				}

				$this->path = path[offset:i]

				// first node: catchAll node with empty path
				child := &node{
					wildChild: true,
					nType:     catchAll,
					maxParams: 1,
				}
				$this->children = []*node{child}
				$this->indices = string(path[i])
				$this->= child
				$this->priority++

				// second node: node holding the variable
				child = &node{
					path:      path[i:],
					nType:     catchAll,
					maxParams: 1,
					handle:    handle,
					priority:  1,
				}
				$this->children = []*node{child}

				return
			}
		}

		// insert remaining path part and handle to the leaf
		$this->path = path[offset:]
		$this->handle = handle
	}

	// Returns the handle registered with the given path (key). The values of
	// wildcards are saved to a map.
	// If no handle can be found, a TSR (trailing slash redirect) recommendation is
	// made if a handle exists with an extra (without the) trailing slash for the
	// given path.
	function getValue(path string) (handle Handle, p Params, tsr bool) {
	walk: // outer loop for walking the tree
		for {
			if len(path) > len($this->path) {
				if path[:len($this->path)] == $this->path {
					path = path[len($this->path):]
					// If this node does not have a wildcard (param or catchAll)
					// child,  we can just look up the next child node and continue
					// to walk down the tree
					if !n.wildChild {
						c := path[0]
						for i := 0; i < len($this->indices); i++ {
							if c == $this->indices[i] {
								$this->= $this->children[i]
								continue walk
							}
						}

						// Nothing found.
						// We can recommend to redirect to the same URL without a
						// trailing slash if a leaf exists for that path.
						tsr = (path == "/" && n.handle != nil)
						return

					}

					// handle wildcard child
					$this->= $this->children[0]
					switch $this->nType {
					case param:
						// find param end (either '/' or path end)
						end := 0
						for end < len(path) && path[end] != '/' {
							end++
						}

						// save param value
						if p == nil {
							// lazy allocation
							p = make(Params, 0, $this->maxParams)
						}
						i := len(p)
						p = p[:i+1] // expand slice within preallocated capacity
						p[i].Key = $this->path[1:]
						p[i].Value = path[:end]

						// we need to go deeper!
						if end < len(path) {
							if len($this->children) > 0 {
								path = path[end:]
								$this->= $this->children[0]
								continue walk
							}

							// ... but we can't
							tsr = (len(path) == end+1)
							return
						}

						if handle = $this->handle; handle != nil {
							return
						} else if len($this->children) == 1 {
							// No handle found. Check if a handle for this path + a
							// trailing slash exists for TSR recommendation
							$this->= $this->children[0]
							tsr = ($this->path == "/" && n.handle != nil)
						}

						return

					case catchAll:
						// save param value
						if p == nil {
							// lazy allocation
							p = make(Params, 0, n.maxParams)
						}
						i := len(p)
						p = p[:i+1] // expand slice within preallocated capacity
						p[i].Key = $this->path[2:]
						p[i].Value = path

						handle = $this->handle
						return

					default:
						panic("invalid node type")
					}
				}
			} else if path == $this->path {
				// We should have reached the node containing the handle.
				// Check if this node has a handle registered.
				if handle = $this->handle; handle != nil {
					return
				}

				if path == "/" && $this->wildChild && $this->nType != self::ROOT {
					tsr = true
					return;
				}

				// No handle found. Check if a handle for this path + a
				// trailing slash exists for trailing slash recommendation
				for i := 0; i < len($this->indices); i++ {
					if $this->indices[i] == '/' {
						$this->= $this->children[i]
						tsr = (len($this->path) == 1 && $this->handle != nil) ||
							($this->nType == catchAll && $this->children[0].handle != nil)
						return
					}
				}

				return
			}

			// Nothing found. We can recommend to redirect to the same URL with an
			// extra trailing slash if a leaf exists for that path
			tsr = (path == "/") ||
				(len($this->path) == len(path)+1 && $this->path[len(path)] == '/' &&
					path == $this->path[:len($this->path)-1] && $this->handle != nil)
			return
		}
	}


	// Makes a case-insensitive lookup of the given path and tries to find a handler.
	// It can optionally also fix trailing slashes.
	// It returns the case-corrected path and a bool indicating whether the lookup
	// was successful.
	function findCaseInsensitivePath(path string, fixTrailingSlash bool) (ciPath []byte, found bool) {
		return n.findCaseInsensitivePathRec(
			path,
			strings.ToLower(path),
			make([]byte, 0, len(path)+1), // preallocate enough memory for new path
			[4]byte{},                    // empty rune buffer
			fixTrailingSlash,
		)
	}

	// recursive case-insensitive lookup function used by n.findCaseInsensitivePath
	function findCaseInsensitivePathRec(path, loPath string, ciPath []byte, rb [4]byte, fixTrailingSlash bool) ([]byte, bool) {
		loNPath := strings.ToLower(n.path)

	walk: // outer loop for walking the tree
		for len(loPath) >= len(loNPath) && (len(loNPath) == 0 || loPath[1:len(loNPath)] == loNPath[1:]) {
			// add common path to result
			ciPath = append(ciPath, n.path...)

			if path = path[len(n.path):]; len(path) > 0 {
				loOld := loPath
				loPath = loPath[len(loNPath):]

				// If this node does not have a wildcard (param or catchAll) child,
				// we can just look up the next child node and continue to walk down
				// the tree
				if !n.wildChild {
					// skip rune bytes already processed
					rb = shiftNRuneBytes(rb, len(loNPath))

					if rb[0] != 0 {
						// old rune not finished
						for i := 0; i < len(n.indices); i++ {
							if n.indices[i] == rb[0] {
								// continue with child node
								n = n.children[i]
								loNPath = strings.ToLower(n.path)
								continue walk
							}
						}
					} else {
						// process a new rune
						var rv rune

						// find rune start
						// runes are up to 4 byte long,
						// -4 would definitely be another rune
						var off int
						for max := min(len(loNPath), 3); off < max; off++ {
							if i := len(loNPath) - off; utf8.RuneStart(loOld[i]) {
								// read rune from cached lowercase path
								rv, _ = utf8.DecodeRuneInString(loOld[i:])
								break
							}
						}

						// calculate lowercase bytes of current rune
						utf8.EncodeRune(rb[:], rv)
						// skipp already processed bytes
						rb = shiftNRuneBytes(rb, off)

						for i := 0; i < len(n.indices); i++ {
							// lowercase matches
							if n.indices[i] == rb[0] {
								// must use a recursive approach since both the
								// uppercase byte and the lowercase byte might exist
								// as an index
								if out, found := n.children[i].findCaseInsensitivePathRec(
									path, loPath, ciPath, rb, fixTrailingSlash,
								); found {
									return out, true
								}
								break
							}
						}

						// same for uppercase rune, if it differs
						if up := unicode.ToUpper(rv); up != rv {
							utf8.EncodeRune(rb[:], up)
							rb = shiftNRuneBytes(rb, off)

							for i := 0; i < len(n.indices); i++ {
								// uppercase matches
								if n.indices[i] == rb[0] {
									// continue with child node
									n = n.children[i]
									loNPath = strings.ToLower(n.path)
									continue walk
								}
							}
						}
					}

					// Nothing found. We can recommend to redirect to the same URL
					// without a trailing slash if a leaf exists for that path
					return ciPath, (fixTrailingSlash && path == "/" && n.handle != nil)
				}

				n = n.children[0]
				switch n.nType {
				case param:
					// find param end (either '/' or path end)
					k := 0
					for k < len(path) && path[k] != '/' {
						k++
					}

					// add param value to case insensitive path
					ciPath = append(ciPath, path[:k]...)

					// we need to go deeper!
					if k < len(path) {
						if len(n.children) > 0 {
							// continue with child node
							n = n.children[0]
							loNPath = strings.ToLower(n.path)
							loPath = loPath[k:]
							path = path[k:]
							continue
						}

						// ... but we can't
						if fixTrailingSlash && len(path) == k+1 {
							return ciPath, true
						}
						return ciPath, false
					}

					if n.handle != nil {
						return ciPath, true
					} else if fixTrailingSlash && len(n.children) == 1 {
						// No handle found. Check if a handle for this path + a
						// trailing slash exists
						n = n.children[0]
						if n.path == "/" && n.handle != nil {
							return append(ciPath, '/'), true
						}
					}
					return ciPath, false

				case catchAll:
					return append(ciPath, path...), true

				default:
					panic("invalid node type")
				}
			} else {
				// We should have reached the node containing the handle.
				// Check if this node has a handle registered.
				if n.handle != nil {
					return ciPath, true
				}

				// No handle found.
				// Try to fix the path by adding a trailing slash
				if fixTrailingSlash {
					for i := 0; i < len(n.indices); i++ {
						if n.indices[i] == '/' {
							n = n.children[i]
							if (len(n.path) == 1 && n.handle != nil) ||
								(n.nType == catchAll && n.children[0].handle != nil) {
								return append(ciPath, '/'), true
							}
							return ciPath, false
						}
					}
				}
				return ciPath, false
			}
		}

		// Nothing found.
		// Try to fix the path by adding / removing a trailing slash
		if fixTrailingSlash {
			if path == "/" {
				return ciPath, true
			}
			if len(loPath)+1 == len(loNPath) && loNPath[len(loPath)] == '/' &&
				loPath[1:] == loNPath[1:len(loPath)] && n.handle != nil {
				return append(ciPath, n.path...), true
			}
		}
		return ciPath, false
	}
}

// shift bytes in array by n bytes left
function shiftNRuneBytes(rb [4]byte, n int) [4]byte {
	switch n {
	case 0:
		return rb
	case 1:
		return [4]byte{rb[1], rb[2], rb[3], 0}
	case 2:
		return [4]byte{rb[2], rb[3]}
	case 3:
		return [4]byte{rb[3]}
	default:
		return [4]byte{}
	}
}