<?php
namespace httprouter;

function CleanPath(string $p) {
	// Turn empty string into "/"
	if $p == "" {
		return "/";
	}

	$n = strlen($p);
	$buf = [];

	// Invariants:
	//      reading from path; r is index of next byte to process.
	//      writing to buf; w is index of next byte to write.

	// path must start with '/'
	$r = 1;
	$w = 1;

	if $p[0] != '/' {
		$r = 0;
		$buf[0] = '/';
	}

	$trailing = $n > 1 && $p[$n-1] == '/';

	// A bit more clunky without a 'lazybuf' like the path package, but the loop
	// gets completely inlined (bufApp). So in contrast to the path package this
	// loop has no expensive function calls (except 1x make)

	for ($r < $n) {
		switch {
		case $p[$r] == '/':
			// empty path element, trailing slash is added after the end
			$r++;

		case $p[$r] == '.' && $r+1 == $n:
			$trailing = true;
			$r++;

		case $p[$r] == '.' && $p[$r+1] == '/':
			// . element
			$r++;

		case $p[$r] == '.' && $p[$r+1] == '.' && ($r+2 == $n || $p[$r+2] == '/'):
			// .. element: remove to last /
			$r += 2;

			if ($w > 1) {
				// can backtrack
				$w--;

				if (empty($buf)) {
					for ($w > 1 && $p[$w] != '/') {
						$w--;
					}
				} else {
					for ($w > 1 && $buf[$w] != '/') {
						$w--;
					}
				}
			}

		default:
			// real path element.
			// add slash if needed
			if ($w > 1) {
				bufApp($buf, $p, $w, '/');
				$w++;
			}

			// copy element
			for ($r < $n && $p[$r] != '/') {
				bufApp($buf, $p, $w, $p[$r]);
				$w++;
				$r++;
			}
		}
	}

	// re-append trailing slash
	if ($trailing && $w > 1) {
		bufApp($buf, $p, $w, '/');
		$w++;
	}

	if (empty($buf)) {
		return substr($p, 0, $w);
	}
	return implode("", array_slice($buf, 0, $w));
}

// internal helper to lazily create a buffer if necessary
function bufApp(&$buf, string $s, int $w, string $c) {
	if (empty($buf)) {
		if $s[$w] == $c {
			return;
		}
		$arr = str_split($s);
		$buf = array_slice($arr, 0, $w);
	}
	$buf[$w] = $c;
}
