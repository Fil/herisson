<?php

# prepare les valeurs pour le template html

if (!defined('HERISSON')) exit;

foreach($mem['config'] as $k => $v)
	$$k = $v; ## affectation un peu pourrite pour aller vite

# user ou home ?
if (isset($user['name'])) {

	# title
	$title = $user['name']." | $site";

	# h1
	$titre = $user['name'];

	# afficher l'age ou la date ?
	if (isset($user['birthday'])) {
		$ch = ($user['sex'] == 'F')
			? 'neele'
			: 'nele';
		if (counter('datenaissance/'.code($nom), 2)) {
			$age = str_replace('@age@', age(code($nom)), $jai_age_an);
		} else {
			$age = str_replace('@date@', datedenaissance(code($nom), 'affdate'), ${$ch}).'.';
		}
		$age = "<p class='age'>".$age."</p>";

		if (strstr(datedenaissance(code($nom)), date('-m-d')))
			$age = "<p class='age anniversaire'>$monanniv</a>";
	}
	else
		$age = '';


	# afficher le job
	if (isset($user['job'])) {
		$premetier = takefrom('prework', 'premetier/'.code($nom));
		$metier = "<p class='metier'>$premetier ".$user['job']."</p>";
	}
	else
		$metier = '';


	# aller chercher un portrait
	$flickrtag = ($user['sex'] == 'M')
		? 'man,boy,male&tag_mode=any'
		: 'woman,female&tag_mode=any';
	$flickrpage = counter('flickrpage/'.code($nom), 40);
	$flickrpic = counter('flickrpic/'.code($nom), 30);


	# statuts
	if (isset($user['status'])) {
		$statut = "<li>".$user['status']."</li>\n";
		if (isset($user['past_statuses']))
			foreach($user['past_statuses'] as $p)
				$statut .= "<li>$p</li>\n";
		$statut = "<div id='statuts'><h4>$mapensee</h4>"
			."<ul class='statuts'>".$statut."</ul></div>\n";
	} else
		$statut = '';

	# info
	if (isset($user['info'])) {
		$info = "<h3>$moninfo <a href=\"".$user['info']['link'].'">'
			.($user['info']['title'])
			.'</a>'
			. (@$user['info']['author']
				? ', '.$parauteur.' '.$user['info']['author']
				: '')
			.'</h3><div class="introduction">'
			. $user['info']['summary']
			.'</div>';
	} else
		$info = '';

	# geographie
	if (isset($user['city']))
		$geographie = "<p>$jhabite ".$user['city']."</p>";
	else
		$geographie = '';

	# phobie
	if (isset($user['phobia']))
		$phobia = '<p>'.$phobia.' '.$user['phobia'].'</p>';
	else
		$phobia = '';

	list($prenom) = explode(' ', $user['name']);

	if ($user['friends']) {
		$numfriends = count($user['friends']);
		$deoudapo = in_array(substr($prenom,0,1), array('A','E','I','O','U','Y'))
			? 'd&#8217;' : 'de ';
		$myfriends = "<h4>"
			. str_replace(array('@num@', '@name@', '@deoudapo@'), array($numfriends, $prenom, $deoudapo),
			$lesnamisdex)."</h4>"
			."<ul>";
			foreach ($user['friends'] as $friend)
				$myfriends .= '<li><a href="'.urlencode($friend).'">'
					. $friend ."</a></li>\n";
			$myfriends .= "</ul>\n";
	} else
		$numfriends = 0;
}
else {
	foreach($mem['config']['home'] as $k => $v)
		$$k = $v; ## affectation un peu pourrite là aussi :-)

	$info = str_replace('@inscrits@', inscrits(), $info);
	$prenom = '';
	$age = '';
	$metier = '';
	$geographie = '';
	$flickrpic = '';
	$myfriends = '';
	$phobia = '';
}


	// permalink
	$link = 'http://'.$site.'/'.str_replace(' ', '+', $nom);
	$qr = 120;
	$qrcode = '<br /><img id="qrcode" alt="" src="http://chart.apis.google.com/chart?chs='
	.$qr.'x'.$qr.'&amp;cht=qr&amp;chl='
	.urlencode($link).'"
	width="'.$qr.'" height="'.$qr.'" />';
	$permalink = (strlen($prenom)
		? str_replace('@nom@', $prenom, $permalien)
		: $permaliencourt
	)
	.'<br />'
	.'<a href="http://'.$site.'/'.urlencode($nom).'">'
	.$link
	.'</a>'
	.$qrcode;

	// Ils viennent de nous rejoindre !
	$recents = "<h4>$newmembers</h4>"
		."<ul><li>"
		. join('</li><li>', membres_recents($nom ? 8 : 20))
		. "</li></ul>";

	// Anniversaires du jour :
	$anniv = "<h4>$bonanniv</h4>"
		."<ul><li>"
		. join('</li><li>', $nom ? anniversaires(1,$nom) : anniversaires(8))
		. "</li></ul>";



	if ($flickrpic) {
		$flickrscript = <<<FLICK
	function jsonFlickrApi(data) {
		if (data.stat == 'ok') {
			var photo = data.photos.photo[$flickrpic];
			var t = 'http://farm'+photo['farm']+'.static.flickr.com/'+photo['server']+'/'+photo['id']+'_'+photo['secret']+'_'+'m'+'.jpg';
			var h = 'http://www.flickr.com/photos/'+photo.owner+'/'+photo.id;
			$('#avatar')
			.append(
			'<a href="'+h+'" title="'+photo['title']+'"><img src="'+t+'" alt="..." /><br />'+photo['ownername']+'<\/a>'
			);
		}
	}

	$(function() {
	$.getJSON("http://api.flickr.com/services/rest/?method=flickr.photos.search&api_key=544b232c639fcf62bea1bec45924d03b&tags=$flickrtag&license=1,2,4,5,7&privacy_filter=public&page=$flickrpage&format=json&extras=owner_name&callback=?");
	});

FLICK;
		$avatar = '<div id="avatar"></div>';

	}
	else {
		$flickrscript = '';
		$avatar = '';
	}

$pied = str_replace('@nom@', urlencode(isset($user) ? $user['name'] : ''), $pied);
