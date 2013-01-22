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
	'Top Model'					=> 'top_model',
	'Scrapbook'					=> 'scrapbook',
	'State Ambassador'			=> 'ambassador',
	'Cover Girl'				=> 'cover_girl',
	'Academic Achievement'		=> 'academic_achievement',
	'Volunteer Service'			=> 'volunteer_service',
);

$placement_map = array(
	'Winner'			=> 'w',
	'1st Runner Up'		=> '1ru',
	'2nd Runner Up'		=> '2ru',
	'3rd Runner Up'		=> '3ru',
	'4th Runner Up'		=> '4ru',
);

$state_regexes = array(
	'princess'		=> '/Princess(.+)Junior Pre-Teen/ms',
	'jr_pre_teen'	=> '/Junior Pre-Teen(.+)Pre-Teen/ms',
	'pre_teen'		=> '/Junior Pre-Teen.+Pre-Teen(.+)Junior Teen/ms',
	'jr_teen'		=> '/Junior Teen(.+)Teen/ms',
	'teen'			=> '/Junior Teen.+Teen(.+)<\/table>/ms',
);

$start_url = 'http://www.namiss.com/eventresults/';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $start_url);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
@$page = curl_exec($ch);
curl_close($ch);

$link_list = array('national'=>array(), 'state'=>array());
preg_match_all('/<li><a href=[\'"]([^"\']+)[\'"]/', $page, $matches);
foreach ($matches[1] as $link) {
	if (preg_match('/national/', $link)) {
		array_push($link_list['national'], $start_url.$link);
	} elseif (preg_match('/eventresults/', $link)) {
		array_push($link_list['state'], 'http://www.namiss.com'.$link);
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

?>
