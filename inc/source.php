<?php

# definir la source de trafic et l'indiquer dans un cookie

define('SOURCES', 'google|facebook|123people|yahoo|rezo');

# $dom = domaine referent
if (isset($_SERVER['HTTP_REFERER'])) {
	list(,,$s_ref) = explode('/', $_SERVER['HTTP_REFERER']);
	if (preg_match(','.SOURCES.',i', $s_ref, $r)) {
		$coo = 'r_'.$r[0];
		setcookie($coo, $_COOKIE[$coo] = 1);
	}
}
