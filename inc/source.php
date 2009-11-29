<?php

# definir la source de trafic et l'indiquer dans deux cookies
# s_ref = dernier referer (domaine)
# s_rei = referer initial (domaine)
# s_prems = arrivee initiale (bool)
# s_serv = source de trafic (string, ex 'google')

$SOURCES = array('google', 'facebook', '123people', 'yahoo', 'rezo');

# $dom = domaine referent
if (isset($_SERVER['HTTP_REFERER']))
	list(,,$s_ref) = explode('/', $_SERVER['HTTP_REFERER']);
else
	$s_ref = '';

# lire et poser le(s) cookie(s)
if ($s_rei = $s_ref) setcookie('ref', $s_ref);

if (isset($_COOKIE['rei']))
	$s_rei = $_COOKIE['rei'];
else
	setcookie('rei', $s_rei);

#
# decrire ou on en est dans la session
#
# premiere arrivee sur le site ?
$s_prems = ($s_rei === $s_ref);

# source connue
if (strlen($s_rei) AND preg_match(','.join('|',$SOURCES).',i', $s_rei, $r))
	$s_serv = $r[0];
else
	$s_serv = '';

