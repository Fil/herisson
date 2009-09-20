<?php

if (!defined('HERISSON')) exit;

function counter($code, $c) {
	if (!$c = abs(intval($c)))
		return 0;
	return $c-1 - floor(sqrt(abs(crc32($code)%100000) % $c*$c));
}

// with code x take a line from base $base
// base : a yaml file containing data in desc freq order
// code : any string (name of a person)
$mem = array();
function loadfrom($base) {
	global $mem;
	global $lang;
	if (!isset($mem[$base])) {
		# $a = time(); $b = microtime();

		# sfYAML is very slow
		# use a caching mechanism
		# yes this is very bad  :-(
		if (@filemtime($cache = "cache/$base.$lang.serialize")
		 <= @filemtime($file = "bases/$base.$lang.yaml")
		OR !is_array($mem[$base] = @unserialize(file_get_contents($cache)))) {
			$yaml  = file_get_contents($file);
			include_once 'lib/sfyaml/sfYaml.php';
			$mem[$base] = sfYaml::load($yaml);
			if (!$fp = @fopen($cache, 'w'))
				die ('I need a writeable cache/');
			fwrite($fp, serialize($mem[$base]));
			fclose($fp);
		}
	}
}
function takefrom($base, $code, $opt=array()) {
	global $mem;
	loadfrom($base);
	$c = counter($code, count($mem[$base]));
	list($key) = array_slice(array_keys($mem[$base]), $c, 1);
	if (isset($opt['key']))
		return array($key => $mem[$base][$key]);
	else
		return $mem[$base][$key];
}

// renvoie les 20 membres les plus recents
// un nouveau est cree toutes les 3 minutes
function membres_recents($n = 20) {
	$friends = array();
	for ($i=0; $i<$n; $i++) {
		$code = date('Y-m-d H:i:s', 180 * floor(time()/180 - $i));
		$heure = date($GLOBALS['formatheure'], 180 * floor(time()/180 - $i)
			+ 50*(crc32($code)%3));

		$sexe = date('i', 180 * floor(time()/180 - $i));

		$pre = takefrom($sexe%2 ? 'male' : 'female', $code);
		$fam = takefrom('family', 'nom/'.$code);
		$friends[$code] = "$heure&nbsp;: <a href='".urlencode("$pre $fam")."'>$pre $fam</a>";
	}

	// inserer le cas echeant celui qu'on vient de creer (il est dans le cookie)
	if (isset($_COOKIE['nom'])
	AND isset($_COOKIE['nom_date'])
	AND ($d = date('Y-m-d H:i:s', intval($_COOKIE['nom_date'])))
	AND $d >= min(array_keys($friends))
	AND $d <= date('Y-m-d H:i:s')) {
		$friends[$d] = date('H\hi', intval($_COOKIE['nom_date']))
			.'&nbsp;: <a href="'
			.urlencode(htmlspecialchars($_COOKIE['nom']))
			.'">'.htmlspecialchars($_COOKIE['nom']).'</a>';

		krsort($friends);
	}

	return $friends;
}


function inscrits() {
	return floor(time()/180)
		- 6922500 /* launch date */
		+ 498000 /* bragging */
		+ isset($_COOKIE['nom']) /* YOU! */;
}

// les membres ont tous plus de 18 ans (on veut pas d'emmerdes :-] )
// et moins de 80 ; la date de naissance ne change jamais
// + il y a plus de jeunes que de vieux.
function datedenaissance($code, $aff='') {
	global $mem;

	// un membre sur 9 n'a pas rempli le champ date de naissance
	if (counter('affage/'.$code, 9) == 1)
		return '';

	// 0 = epoch = 1er janvier 1970
	// 2009 = 1234567890
	$date = intval(1234567890 - 24*3600 * (18*365.24 + counter($code, 62*365.24)));
	$date = date('Y-m-d', $date);

	switch($aff) {
		case '':
			return $date;
		case 'affdate':
			list($y,$m,$d) = explode('-', $date);
			switch($GLOBALS['lang']) {
				case 'fr':
				$jour = ($d == 1) ? '1er' : intval($d);
				break;
				case 'en':
				if ($d == 1) $jour = '1st';
				elseif ($d == 2) $jour = '2nd';
				elseif ($d == 3) $jour = '3rd';
				else $jour = intval($d).'th';
				break;
			}
			loadfrom('months');
			return $jour
				.' '.$mem['months'][intval($m)]
				.' '.$y;
	}
}

function age($code) {
	if (!$d = datedenaissance($code))
		return '';
	preg_match(',(\d+)-(\d+-\d+),', $d, $a);
	return date('Y')-$a[1] - ($a[2] > date('m-d'));
}


// chercher betement n personnes dont l'anniv est aujourd'hui
function anniversaires($n, $excepte='') {
	global $annees;
	$friends = array();
	$j = 0;
	$cpt = 0;
	while (count($friends) < $n
	AND $cpt++ < 7*365) {
		$nom = takefrom(($n%2) ? 'male' : 'female', $j++)
			.' '.takefrom('family', $j++);
		$code = code($nom);
		if ($nom !== $excepte
		AND strstr(datedenaissance($code), date('-m-d')))
			$friends[] =  "<a rel='friend' href='".urlencode($nom)."'>$nom</a> (".age($code)."&nbsp;$annees)";
	}

	return $friends;
}

function translitteration($txt) {
	return str_replace(
	array('é','è','ë','ê','á','à','ä','â','å','ã','ï','î','ì','í','ô','ö','ò','ó','õ','ø','ú','ù','û','ü','ç','ñ','æ','œ'),
	array('e','e','e','e','a','a','a','a','a','a','i','i','i','i','o','o','o','o','o','o','u','u','u','u','c','n','ae','oe'),
	$txt
	);
}

// René = rene
function code($nom) {
	static $ram = array();
	if (!isset($ram[$nom]))
		$ram[$nom] = translitteration(strtolower($nom));
	return $ram[$nom];
}

function get_personne($nom) {
	global $mem;
	global $lang;

	$code = code($nom);
	$user = array('name' => $nom);

	// cherche le sexe du prenom
	// par defaut, si on ne connait pas, c'est une fille
	list($prenom) = explode(' ', $nom);
	loadfrom('male');
	$user['sex'] = in_array($prenom, $mem['male'])
		? 'M'
		: 'F';

	// date de naissance
	if ($d = datedenaissance($code))
		$user['birthday'] = $d;

	// boulot
	if ($job = takefrom('work', 'metier/'.$code))
		$user['job'] = $job;

	// friends, toujours defini
	$user['friends'] = array();

	// si on a un cookie de nom, qu'on est sur la page d'un autre,
	// et qu'on a de la chance, on est son ami
	if (isset($_COOKIE['nom'])
	AND $_COOKIE['nom'] !== $nom
	AND counter('autofriend/'.$code, 3)==1)
		$user['friends'][] = htmlspecialchars($_COOKIE['nom']);

	// on est passe la il y a pas longtemps, on se souvient
	if (isset($_COOKIE['nomrecent'])
	AND $_COOKIE['nomrecent'] !== $nom
	AND counter('autofriend/'.$code, 2)==1)
		$user['friends'][] = htmlspecialchars($_COOKIE['nomrecent']);

	// on ajoute entre 0 et 150 friends
	$f = counter('friends/'.$code, 150);
	for ($i = 0; $i < $f; $i++) {
		$pre = takefrom(($f % (1+$i))%2 ? 'male' : 'female', $i.'/'.$code);
		$fam = takefrom('family', $i.'nom/'.$code);
		$user['friends'][] = "$pre $fam";
	}

	// on verifie qu'il n'y a pas de doublon
	$user['friends'] = array_values(array_unique($user['friends']));


	// status
	$n = counter('pensees/'.$code, 3);
	$statuts = array();
	for ($i=0; $i< $n; $i++)
		$statuts[] = takefrom('thoughts', $i.'pensee/'.$code);
	if ($statuts) {
		$statuts = array_values(array_unique($statuts));
		$user['status'] = array_shift($statuts);
		if ($statuts)
			$user['past_statuses'] = $statuts;
	}

	// info
	if ($url = @$mem['config']['sites'][counter('feed/'.$code, 3 + count($mem['config']['sites']))]) {
		define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');
		require('lib/magpierss/rss_fetch.inc');
		if ($rss = @fetch_rss($url)
		AND $rss = $rss->items
		AND $item = @$rss[1 + counter('recette/'.$code, count($rss))]
		) {
			$user['info'] = array(
				'title' => strip_tags($item['title']),
				'link' => $item['link']
			);
			if ($author = @$item['dc']['creator'])
				$user['info']['author'] = $author;
			if ($desc = @$item['summary']
				? $item['summary']
				: $item['atom_content']
			)
				$user['info']['summary'] = $desc;
		}
	}
	
	// geographie
	if (
	$lang == 'fr' AND
	$geo = takefrom('cities', 'region/'.$code, array('key' => true))
	AND $region = array_pop(array_keys($geo))
	AND $ville = $geo[$region][counter('ville/'.$code, count($geo[$region]))])
		$user['city'] = "$ville ($region)";

	return $user;
}
