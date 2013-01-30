<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '/Users/kexline/Documents/pageanthub/');

$award_field_map = array(
	'Queen'						=> 'queen',                                             
	'Miss Personality'			=> 'personality',
	'Best Thank You Note'		=> 'thank_you_note',
	'Most Promising Model'		=> 'promising_model',
	'Spirit Award'				=> 'spirit',
	'Most Ticket Sales'			=> 'ticket_sales',
	'Most Recommendations'		=> 'recommendations',
	'Best Resume'				=> 'resume',
	'Art Contest'				=> 'art',
	'Talent'					=> 'talent',
	'Photogenic'				=> 'photogenic',
	'Casual Wear'				=> 'casual_wear',
	'Actress'					=> 'actress',
	'SpokesModel'				=> 'spokesmodel',
	'Spokesmodel'				=> 'spokesmodel',
	'Top Model'					=> 'top_model',
	'Scrapbook'					=> 'scrapbook',
	'State Ambassador'			=> 'ambassador',
	'Cover Girl'				=> 'cover_girl',
	'Academic Achievement'		=> 'academic_achievement',
	'Volunteer Service'			=> 'volunteer_service',
	'National Cover Miss'		=> 'cover_miss',
	'National Cover Models'		=> 'cover_model',
	'National Cover Model'		=> 'cover_model',
	'National Cover Girl'		=> 'cover_girl',
	'National Ambassador'		=> 'ambassador',
	'Golden Achievement In Service'		=> 'golden_achievement',
	'NAM Alumni Association Scholarship'	=> 'alumni_scholarship',
	'Top Model Award'			=> 'top_model',
	'Casual Wear Modeling'		=> 'casual_wear',
	'National Scrapbook Contest'	=> 'scrapbook'
);

$placement_map = array(
	'Winner'			=> 'w',
	'1st Runner Up'		=> '1ru',
	'2nd Runner Up'		=> '2ru',
	'3rd Runner Up'		=> '3ru',
	'4th Runner Up'		=> '4ru',
	'Queen'				=> 'w',
	'1st Runner-up'		=> '1ru',
	'2nd Runner-up'		=> '2ru',
	'3rd Runner-up'		=> '3ru',
	'4th Runner-up'		=> '4ru',
);

$state_regexes = array(
	'princess'		=> '/Princess(.+)Junior Pre-Teen/ms',
	'jr_pre_teen'	=> '/Junior Pre-Teen(.+)Pre-Teen/ms',
	'pre_teen'		=> '/Junior Pre-Teen.+Pre-Teen(.+)Junior Teen/ms',
	'jr_teen'		=> '/Junior Teen(.+)Teen/ms',
	'teen'			=> '/Junior Teen.+Teen(.+)<\/table>/ms',
);

$national_links = array(
	'results-princess.aspx'		=> 'princess',
	'results-jpt.aspx'			=> 'jr_pre_teen',
	'results-pt.aspx'			=> 'pre_teen',
	'results-jt.aspx'			=> 'jr_teen',
	'results-teen.aspx'			=> 'teen',
	'results-miss.aspx'			=> 'miss',
);

$division_map = array(
	'Princess'			=> 'princess',
	'Junior Pre-Teen'	=> 'jr_pre_teen',
	'Jr. Pre-Teen'		=> 'jr_pre_teen',
	'Pre-Teen'			=> 'pre_teen',
	'Junior Teen'		=> 'jr_teen',
	'Jr. Teen'			=> 'jr_teen',
	'Teen'				=> 'teen',
	''					=> 'miss',
);

$start_url = 'http://www.namiss.com/eventresults/';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $start_url);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
@$page = curl_exec($ch);
curl_close($ch);

$link_list = array('national'=>array(), 'state'=>array(), 'state_overview'=>array());
preg_match_all('/<li><a href=[\'"]([^"\']+)[\'"]/', $page, $matches);
foreach ($matches[1] as $link) {
	if (preg_match('/national/', $link)) {
		foreach (array_keys($national_links) as $n) {
			array_push($link_list['national'], $start_url.$link.$n);
		}
	} elseif (preg_match('/eventresults/', $link)) {
		array_push($link_list['state'], 'http://www.namiss.com'.$link);
	} elseif (preg_match('/state/', $link)) {
		array_push($link_list['state_overview'], $start_url.$link);
	}
}
//print_r($link_list);

foreach ($link_list['state'] as $url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, preg_replace('/ /', '+', $url));
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	@$page = curl_exec($ch);
	curl_close($ch);
	$results = array();
	$m = array();
	preg_match('/State (.+)$/',$url, $m);
	$state = $m[1];
	$state = preg_replace('/[\(\)]/', '', $state);
	preg_match('/id=(\d{4})/', $url,$m);
	$year = $m[1];
	foreach ($state_regexes as $division => $sr) {
		//echo "state: $state, year: $year, division: $division\n";
		$results[$division] = array();
		preg_match($sr, $page,$m);
		$section = $m[1];
		preg_match_all('/(<tr>|<tr bgcolor="#ffffff">)\s+?<td.+?>(.+?)<\/td>\s+?<td.+?>(.+?)<\/td>\s+?<td.+?><\/td>\s+?<\/tr>/ms', $section,$m);
		$awards = $m[2];
		$contestants = $m[3];
		for ($i = 0; $i < count($awards); $i++) {
			$c = $contestants[$i];
			$c = trim(strip_tags($c));
			$c = preg_replace('/\'/', '\\\'', $c);
			if (empty($results[$division][$c])) {
				$results[$division][$c] = array();
			}
			$a = $awards[$i];
			$a = trim(strip_tags($a));
			if ($a == 'Queen') {
				$results[$division][$c]['queen'] = 'w';
			} elseif ($a == 'Cover Girl') {
				$results[$division][$c]['advertising'] = 'cover_girl';
			} elseif ($a == 'State Ambassador') {
				$results[$division][$c]['advertising'] = 'state_ambassador';
			} else {
				$set = 0;
				foreach ($placement_map as $p => $abbr) {
					preg_match('/(.+) '.$p.'$/i', $a, $m);
					if ($m[1]) {
						$award = $award_field_map[$m[1]];
						$results[$division][$c][$award] = $abbr;
						$set = 1;
						continue;
					}
				}
				if (!$set && preg_match('/(.+) Runner Up$/i', $a, $m)) {
					$award = $award_field_map[$m[1]];
					$results[$division][$c][$award] = '1ru';
				} elseif ($award = $award_field_map[$a]) {
					$results[$division][$c][$award] = 'w';
				}
			}
		}
	}
	foreach ($results as $division => $contestants) {
		foreach ($contestants as $c => $awards) {
			$fields = "(`contestant`,`year`,`division`,`state`,";
			$values = "('".$c."','".$year."','".$division."','".$state."',";
			foreach ($awards as $k => $v) {
				$fields .= "`".$k."`,";
				$values .= "'".$v."',";
			}
			$fields = trim($fields, ',');
			$values = trim($values, ',');
			$fields .= ")";
			$values .= ")";
			$sql = "INSERT INTO nam_event_results ".$fields." VALUES ".$values;
			echo $sql.";\n";
		}
	}	
}

foreach ($link_list['national'] as $url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, preg_replace('/ /', '+', $url));
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	@$page = curl_exec($ch);
	curl_close($ch);
	$results = array(
		'nationals-aa'		=> array(),
		'nationals-nam'		=> array(),
		'nationals-opt'		=> array()
	);
	$m = array();
	preg_match('/national(\d{4})/', $url, $m);
	$year = $m[1];
	preg_match('/\/(results-.+\.aspx)/', $url, $m);
	$division = $national_links[$m[1]];
	preg_match_all('/(<tr>|<tr height="\d+">)\s+?<td.*?>(<strong>)?(<span.+?>)?(.*?)(<\/strong>)?(<\/span>)?<\/td>\s+?(<td.*?>(.*?)<\/td>\s+?)?(<td.*?>.*?<\/td>\s+?)?<\/tr>/ms', $page, $m);
	$col0 = $m[4];
	$col1 = $m[8];
	$current_award = 'queen';
	$current_competition = 'nationals-nam';
	$current_placement = '';
	for ($i = 0; $i < count($col0); $i++) {
		$a = $col0[$i];
		$a = preg_replace('/\s+/', ' ', $a);
		$a = preg_replace('/\'/', '\\\'', $a);
		$a = trim(strip_tags($a));
		if (preg_match('/National (All-)?American/', $a, $m)) {
			$current_award = 'queen';
			if ($m[1][0]) {
				$current_competition = 'nationals-aa';
			} else {
				$current_competition = 'nationals-nam';
			}
		} elseif (preg_match('/Top 10/', $a, $m)) {
			$current_placement = 'tt';
		} elseif (isset($award_field_map[$a]) &&
			      $a != 'Queen') {
			$current_award = $award_field_map[$a];
			if ($a != 'Miss Personality' &&
				$a != 'Spirit Award' &&
				$a != 'Most Ticket Sales') {
				$current_competition = 'nationals-opt';
			}
			if (preg_match('/National (Cover|Ambassador)/', $a, $m)) {
				$current_placement = $current_award;
				$current_award = 'advertising';
			}
		} elseif ($a &&
				  !preg_match('/[|\/;"<>]/',$a,$m) &&
				  $a != 'Advertising Titles' &&
				  $a != 'NAM Spirit Competition' &&
				  $a != 'Optional Contests') {
			$b = $col1[$i];
			$b = preg_replace('/\s+/', ' ', $b);
			$b = trim(strip_tags($b));
			$b = preg_replace('/\'/', '\\\'', $b);
			$placement = $placement_map[$a];
			if (!$placement) {
				$placement = $current_placement;
				if (!isset($results[$current_competition][$a][$current_award])) {
					$results[$current_competition][$a][$current_award] = $placement;
				}
			} else {
				$results[$current_competition][$b][$current_award] = $placement;
			}
		}
	}
	foreach ($results as $competition => $contestants) {
		foreach ($contestants as $c => $awards) {
			$fields = "(`contestant`,`year`,`division`,`state`,";
			$values = "('".$c."','".$year."','".$division."','".$competition."',";
			foreach ($awards as $k => $v) {
				$fields .= "`".$k."`,";
				$values .= "'".$v."',";
			}
			$fields = trim($fields, ',');
			$values = trim($values, ',');
			$fields .= ")";
			$values .= ")";
			$sql = "INSERT INTO nam_event_results ".$fields." VALUES ".$values;
			echo $sql.";\n";
		}
	}
}

foreach ($link_list['state_overview'] as $url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, preg_replace('/ /', '+', $url));
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	@$page = curl_exec($ch);
	curl_close($ch);
	$results = array(
		'princess'		=> array(),
		'jr_pre_teen'	=> array(),
		'pre_teen'		=> array(),
		'jr_teen'		=> array(),
		'teen'			=> array(),
		'miss'			=> array(),
	);
	$m = array();
	preg_match('/state(\d{4})/', $url,$m);
	$year = $m[1];
	preg_match_all('/(<tr>|<tr height="\d+">)\s+?<td.*?>(<strong>)?(<span.+?>)?(.*?)(<\/strong>)?(<\/span>)?<\/td>\s+?(<td.*?>(.*?)<\/td>\s+?)?(<td.*?>.*?<\/td>\s+?)?<\/tr>/ms', $page, $m);
	$contestants = $m[4];
	$titles = $m[8];
	$state = 'Alabama';
	for ($i = 0; $i < count($contestants); $i++) {
		$c = $contestants[$i];
		$t = $titles[$i];
		$c = preg_replace('/\s+/', ' ', $c);
		$c = trim(strip_tags($c));
		$c = preg_replace('/\'/', '\\\'', $c);
		$t = preg_replace('/\s+/', ' ', $t);
		$t = trim(strip_tags($t));
		$t = preg_replace('/\'/', '\\\'', $t);
		if (!$c || preg_match('/[|\/;"<>]/',$c,$m)) {
			continue;
		} elseif (preg_match('/- (North|South|East|West)/', $c) || !$t || $t == '&nbsp;') {
			$state = preg_replace('/[\s\-]+/', '_', $c);
			continue;
		}
		preg_match('/Miss (.+?)(Princess|Junior Pre-Teen|Jr\. Pre-Teen|Pre-Teen|Junior Teen|Jr\. Teen|Teen|)( - Division \d)?$/', $t, $m);
		if (!$state && $m[1]) {
			$state = trim($m[1]);
		}
		$division = $division_map[trim($m[2])];
		if ($results[$division][$state]) {
			$results[$division][$state."2"] = $c;
		} else {
			$results[$division][$state] = $c;
		}
	}
	foreach ($results as $division => $states) {
		foreach ($states as $s => $c) {
			$fields = "(`contestant`,`year`,`division`,`state`,`queen`)";
			$values = "('".$c."','".$year."','".$division."','".$s."','w')";
			$sql = "INSERT INTO nam_event_results ".$fields." VALUES ".$values;
			$sql .= " ON DUPLICATE KEY UPDATE `queen` = 'w'";
			echo $sql.";\n";
		}
	}
}
?>
