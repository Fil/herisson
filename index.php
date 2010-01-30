<?php

define('HERISSON', 1);
require 'inc/herisson.php';

include 'inc/source.php';  # definir la source de trafic

# s'il y a un POST on enregistre le nom en cookie et on redirecte
# On n'utilise pas $_SESSION car on ne veut aucune donnee sur le serveur
if (isset($_POST['nom'])) {
	setcookie('nom', ucwords($_POST['nom']));
	setcookie('nom_date', time());
	header('Location: ./'.urlencode(ucwords(htmlspecialchars($_POST['nom']))));
	exit;
}

# per.sonn.es
$nom = ucwords(htmlspecialchars(array_pop(explode('/',urldecode($_SERVER['REQUEST_URI'])))));
# supprimer ce qui est apres le ? (pour l'API ?format=json)
$nom = array_shift(explode('?', $nom));

# ignorer les espaces superflus
$nom = trim($nom);

# poser parfois un cookie pour semer le trouble
if (rand(0,1))
	setcookie('nomrecent', $nom);

# bidouille pour accepter les hits de test de google webmaster tools
if (preg_match(',^noexist_,i', $nom)) {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	die('Not found');
}


# config
switch($_SERVER['HTTP_HOST']) {
	case 'localhost':
	case 'per.sonn.es':
		$lang = 'fr';
		$site = 'per.sonn.es';
		break;
	default:
	case 'fakefriends.me':
		$lang = 'en';
		$site = 'fakefriends.me';
		break;
}

loadfrom('config');

# add your own analytics file if you want
$analytics = @file_get_contents('inc/analytics.'.$lang.'.js');


# format
$format = 'html';
if (preg_match(',^(.+)\.(json|rdf|xml|yaml|html)$,', $nom, $f)
AND strlen($nom = $f[1])) {
	$format = $f[2];
	if ($format == 'xml') $format = 'rdf';
}

# user or home?
switch ($nom) {
	case '':
		$format = 'html';
		$user = null;
		break;

	default:
		$user = get_personne($nom);
		break;
}

# display
switch($format) {

	case 'json':
		header('Content-Type: text/plain; charset=utf-8');
		echo json_encode($user);
		break;

	case 'yaml':
		header('Content-Type: text/plain; charset=utf-8');
		include_once 'lib/sfyaml/sfYaml.php';
		echo sfYaml::dump($user);
		break;

	case 'rdf':
		header('Content-Type: text/xml; charset=utf-8');
		include 'templates/herisson.rdf';
		break;

	case 'html':
		header('Content-Type: text/html; charset=utf-8');
		include 'templates/herisson.html';
		break;
}

?>
