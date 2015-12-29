<?php

$qb = new QuickBibleCli();

$qb->getLastOptions();

$qb->parseCli();

if ($qb->getQuery() == ""){
	$qb->simpleParseCli();
}

$qb->putOptions();

try{
	$qb->validateBible();
}catch(Exception $e){
	echo $e->getMessage();
	exit;
}

$qb->determinePath();



























class QuickBibleCli{
	protected $base_dir;
	protected $bible;
	protected $options = array('q'=>'');
	protected $bible_path = '';
	protected $cfg_file = '';

  public function getQuery(){
		if (!isset($this->options['q'])){
			$this->options['q'] = '';
		}
		return $this->options['q'];
	}

	public function __construct(){
		//defaults
		$this->base_dir = __DIR__.'/bibles'; //"C:\Program Files (x86)\QuickBible\Bibles";
		$this->bible = 'kjvr';
	}

	public function getLastOptions(){
		if (!$this->cfg_file){
			$this->cfg_file = __DIR__ .'/qb.cfg.json';
		}
		if (!file_exists($this->cfg_file)){
			return false;
		}

		$this->options = json_decode(file_get_contents($this->cfg_file), true);
		return true;
	}

	public function putOptions($options = null){
		if (!$this->cfg_file){
			$this->cfg_file = __DIR__ .'/qb.cfg.json';
		}

		if (empty($options)){ $options = $this->options; }
		
		file_put_contents($this->cfg_file, json_encode($options));	
		return true;
	}

	public function simpleParseCli(){
		global $argv;
		//qb <bible> <search-query>
		if ( isset($argv[1]) && file_exists($this->base_dir.'/'.$argv[1].'.bible.sqlite3') ){
			$this->bible = $argv[1];
			$this->options['bible'] = $argv[1];
			$start = 2;
		}else{
			$start = 1;
		}

		if (isset($argv[$start])){
			//conjoin all parameters into one
			for($i = $start; $i < count($argv); ++$i){
				if ($argv[$i]{0} == '-'){
					continue;
				}
				if ($this->options['q'] != ''){
					$this->options['q'] .= ' ';
				}
				$this->options['q'] .= $argv[$i];
			}
		}
	}

	public function parseCli(){
		$shortopts  = "q::"; //query string
		$shortopts .= "b::";  // Bible (optional) value
		$shortopts .= "d::"; // Base Dir (optional) value
		$shortopts .= "r"; //random chapter
		$shortopts .= "v"; //verbose/debug
		$shortopts .= 'h';  //help

		$longopts  = array(
		    "basedir::",    // Optional value
		    "bible::",        // optional value
		    "color",           // show colors
		    "raw",          //show raw text (including rtf characters)
				"nnl",          //no newlines
				"nv",           //no verse numbers
		    "help",         //show help
		);

		$options = getopt($shortopts, $longopts);

		if (isset($options['v'])){
			print_r($options);
		}

		if (empty($options)){
			$this->options = array('q'=>'');
			return false;
		}

		$this->options = $options;

		if (isset($options['basedir'])){
			$base_dir = $options['basedir'];
		}else if (isset($options['d'])){
			$base_dir = $options['d'];
		}

		if (isset($options['bible'])){
			$bible = $options['bible'];
		}else if (isset($options['b'])){
			$bible = $options['b'];
		}

		if (isset($bible)){
			$this->bible = $bible;
			$this->options['bible'] = $bible;
		}

		if (isset($base_dir)){
			$this->base_dir = $base_dir;
		}

		if (isset($options['help']) || isset($options['h'])){
			$this->showHelp();
			exit;
		}
		return true;
	}

	public function showHelp(){
		global $argv;
		echo "Usage: php ".$argv[0].' kjvr Jesus wept'."\n";
		echo "Usage: qb kjvr Jesus wept \n";
	}

	public function validateBible(){
		$bible_path = $this->base_dir.'/'.$this->bible.'.bible.sqlite3';

		if (!file_exists($bible_path)){
			throw new Exception($this->bible.' does not exist in this path: '.$this->base_dir);
		}

		$this->bible_path = $bible_path;
		return true;
	}

	public function colorize($text){
		$color_ary = array();

		$segment = 0;
		$color = 'gray';

		$len = strlen($text);
		for ($i = 0; $i < $len; ++$i){
			if ($color == 'white' && $text{$i} == ' '){
				$color = 'gray';
				$segment++;
			}
			if ($i + 1 < $len){
				$lookahead = $text{$i+1};
			}else{
				$lookahead = null;
			}

		    if ($i + 2 < $len){
				$lookahead2 = $text{$i+2};
			}else{
				$lookahead2 = null;
			}

			if ($i + 3 < $len){
				$lookahead3 = $text{$i+3};
			}else{
				$lookahead3 = null;
			}

		    if ($i + 4 < $len){
				$lookahead4 = $text{$i+4};
			}else{
				$lookahead4 = null;
			}

		    if ($i + 5 < $len){
				$lookahead5 = $text{$i+5};
			}else{
				$lookahead5 = null;
			}

			switch($text{$i}){
				case '{':
				if ($lookahead == '\\' && $lookahead2 == 'c' && $lookahead3 == 'f' &&
				    $lookahead4 == '6' && $lookahead5 == ' '){
					$color = 'red';
					if (!empty($color_ary[$segment]))
					{
						$segment++;
					}
					$i += 5;
					continue;
					
				}else if ($lookahead == '\\' && $lookahead2 == 'c' && $lookahead3 == 'f' &&
				          $lookahead4 == '1' && $lookahead5 == '5'){
					//{\cf15\i
					$i += 8; //5;
					$color = 'white';
					if (!empty($color_ary[$segment]))
					{
						$segment++;
					}					
					continue;
				}
				break;
				case '}':
				
				$color = 'gray';
				$segment++;
				$i++;

					continue;
				break;
			}
			if (isset($color_ary[$segment][$color])){
				$color_ary[$segment][$color] .= $text{$i};
			}
			else{
				$color_ary[$segment][$color] = $text{$i};
			}
		}

		
		return $color_ary;
	}

	public function printColorArray($color_ary){
	
		$s = '';
		$clr = new Colors();
		foreach($color_ary as $i => $ct){
			foreach($ct as $color => $text){
				if (!empty($text)){
					if ($color == 'gray'){ $color = null; }
					$s .= $clr->getColoredString($text, $color, 'black');
				}
			}
		}
		return $s;
	}


	public function outputVerse($t){
	       $t = str_replace('\\emdash', '--', $t);

	       if (isset($this->options['color'])){
	       	  echo $this->printColorArray( $this->colorize($t) );
	       }else if (isset($this->options['raw'])){
	       	  echo $t;
	       }else{
	       	  $t = preg_replace('/({\\\\[\w+\\\\]+ )|(})/i', '', $t);
	       	  echo $t;
	       }
	}


  public function getRandomChapter($dbh){
			$b = rand(1,66);
			$this->options['q'] = $this->getAbbr($b).' ';
			$prep = $dbh->prepare('SELECT MAX(c) FROM bible_verses WHERE b = :book');
			$prep->execute(array('book'=>$b));
			$max_c = $prep->fetchColumn();
			$c = rand(1, $max_c);
			$this->options['q'] .= $c;
	}

  public function referenceLookup($dbh, $regs){
      	$bk = $regs[1];
				$ch = $regs[2];
				$vs_min = isset($regs[3]) ? $regs[3] : null;
				$vs_max = isset($regs[4]) ? $regs[4] : null;

				if (empty($vs_min)){
					$vs_min = 1;
					$vs_max = 999;
				}

				if (empty($vs_max)){
					$vs_max = $vs_min;
				}

				$b = self::getBookNumber($bk);
				$sql = 'SELECT * FROM bible_verses WHERE b = :book AND c = :chapter AND v >= :vs_min AND v <= :vs_max ORDER BY v ASC';
				$ary = array('book'=>$b, 'chapter'=>$ch, 'vs_min'=>$vs_min, 'vs_max'=>$vs_max);


				$prep = $dbh->prepare($sql);
				$prep->execute($ary);

				$count = 0;
			    while($row = $prep->fetch(PDO::FETCH_ASSOC)){
			       //echo $count.'. ';
			       //echo self::getAbbr($row['b']).' '.$row['c'].':'.$row['v'].' ';
			       if (!isset($this->options['nv'])){
						  	echo $row['v'].' ';
						 }

			       $this->outputVerse($row['t']);
		
						 if (!isset($this->options['nnl'])){
			       		echo "\n";
						 }
			       $count++;
			    }
					if (isset($this->options['nnl'])){
						echo "\n";
					}
			    //echo $count.' bible verses found.';
		    	return;
	}

	public function search($dbh){

			$sql = 'SELECT * FROM bible_verses WHERE t LIKE :search';
			$ary = array('search'=>'%'.$this->options['q'].'%');

			$prep = $dbh->prepare($sql);
		    $prep->execute($ary);
		    $count = 1;
		    while($row = $prep->fetch(PDO::FETCH_ASSOC)){
		       //echo $count.'. ';
		       echo self::getAbbr($row['b']).' '.$row['c'].':'.$row['v'].' ';

		       $this->outputVerse($row['t']);

		       echo "\n";
		       $count++;
		    }
		    echo ($count-1)." bible verses found.\n";
	}

  public function showInfo($dbh){
			$sql = 'SELECT * FROM bible_info LIMIT 1';
			$prep = $dbh->prepare($sql);
			$prep->execute(array());
			$info = $prep->fetch(PDO::FETCH_ASSOC);

			print_r($info);
			echo "Books of the Bible: ";
			echo implode(',', array_keys(self::$bible_abbr_to_number));
			echo "\n";	
	}

	public function determinePath(){
		

		$dbh = new PDO('sqlite:'.$this->bible_path);


		if (isset($this->options['r'])){
			$this->getRandomChapter($dbh);
			echo $this->options['q']."\n";
		}

		if (!empty($this->options['q'])){
			if (preg_match('/([\da-z][a-z][a-z])[a-z]* (\d+):?(\d+)?\-?(\d+)?/i', $this->options['q'], $regs)) {
				$this->referenceLookup($dbh, $regs);	
			}else{
				$this->search($dbh);
			}

	  }else{
	    $this->showInfo($dbh);
    }

	}

	public static function getAbbr($b){
		$bk = self::$bible_abbr[$b];
		if (!is_numeric($bk{0})){
			$bk{1} = strtolower($bk{1});
		}
		$bk{2} = strtolower($bk{2});
		return $bk;
	}

	public static function getBookNumber($bk){
		$bk = strtoupper($bk);
		if (isset(self::$bible_abbr_to_number[$bk])){
			return self::$bible_abbr_to_number[$bk]; 
		}
		else return false;
	}

	public static $bible_abbr_to_number = array(
		"GEN"=>1,	"EXO"=>2,	"LEV"=>3,	"NUM"=>4,	"DEU"=>5,	"JOS"=>6,	"JDG"=>7,	"RUT"=>8,	"1SA"=>9,	"2SA"=>10,	"1KI"=>11,	"2KI"=>12,	"1CH"=>13,	"2CH"=>14,	"EZR"=>15,	"NEH"=>16,	"EST"=>17,	"JOB"=>18,	"PSA"=>19,	"PRO"=>20,	"ECC"=>21,	"SON"=>22,	"ISA"=>23,	"JER"=>24,	"LAM"=>25,	"EZE"=>26,	"DAN"=>27,	"HOS"=>28,	"JOE"=>29,	"AMO"=>30,	"OBA"=>31,	"JON"=>32,	"MIC"=>33,	"NAH"=>34,	"HAB"=>35,	"ZEP"=>36,	"HAG"=>37,	"ZEC"=>38,	"MAL"=>39,
		"MAT"=>40,	"MAR"=>41,	"LUK"=>42,	"JOH"=>43,	"ACT"=>44,	"ROM"=>45,	"1CO"=>46,	"2CO"=>47,	"GAL"=>48,	"EPH"=>49,	"PHI"=>50,	"COL"=>51,	"1TH"=>52,	"2TH"=>53,	"1TI"=>54,	"2TI"=>55,	"TIT"=>56,	"PHM"=>57,	"HEB"=>58,	"JAM"=>59,	"1PE"=>60,	"2PE"=>61,	"1JO"=>62,	"2JO"=>63,	"3JO"=>64,	"JUD"=>65,	"REV"=>66,
	);

	public static $bible_abbr = array(
	"   ",	"GEN",	"EXO",	"LEV",	"NUM",	"DEU",	"JOS",	"JDG",	"RUT",	"1SA",	"2SA",	"1KI",	"2KI",	"1CH",	"2CH",	"EZR",	"NEH",	"EST",	"JOB",	"PSA",	"PRO",	"ECC",	"SON",	"ISA",	"JER",	"LAM",	"EZE",	"DAN",	"HOS",	"JOE",	"AMO",	"OBA",	"JON",	"MIC",	"NAH",	"HAB",	"ZEP",	"HAG",	"ZEC",	"MAL",	
					"MAT",	"MAR",	"LUK",	"JOH",	"ACT",	"ROM",	"1CO",	"2CO",	"GAL",	"EPH",	"PHI",	"COL",	"1TH",	"2TH",	"1TI",	"2TI",	"TIT",	"PHM",	"HEB",	"JAM",	"1PE",	"2PE",	"1JO",	"2JO",	"3JO",	"JUD",	"REV"
	);

}





class Colors {
	private $foreground_colors = array();
	private $background_colors = array();
 
	public function __construct() {
	// Set up shell colors
	$this->foreground_colors['black'] = '0;30';
	$this->foreground_colors['dark_gray'] = '1;30';
	$this->foreground_colors['blue'] = '0;34';
	$this->foreground_colors['light_blue'] = '1;34';
	$this->foreground_colors['green'] = '0;32';
	$this->foreground_colors['light_green'] = '1;32';
	$this->foreground_colors['cyan'] = '0;36';
	$this->foreground_colors['light_cyan'] = '1;36';
	$this->foreground_colors['red'] = '0;31';
	$this->foreground_colors['light_red'] = '1;31';
	$this->foreground_colors['purple'] = '0;35';
	$this->foreground_colors['light_purple'] = '1;35';
	$this->foreground_colors['brown'] = '0;33';
	$this->foreground_colors['yellow'] = '1;33';
	$this->foreground_colors['light_gray'] = '0;37';
	$this->foreground_colors['white'] = '1;37';
 
	$this->background_colors['black'] = '40';
	$this->background_colors['red'] = '41';
	$this->background_colors['green'] = '42';
	$this->background_colors['yellow'] = '43';
	$this->background_colors['blue'] = '44';
	$this->background_colors['magenta'] = '45';
	$this->background_colors['cyan'] = '46';
	$this->background_colors['light_gray'] = '47';
	}
 
	// Returns colored string
	public function getColoredString($string, $foreground_color = null, $background_color = null) {
	$colored_string = "";
 
	// Check if given foreground color found
	if (isset($this->foreground_colors[$foreground_color])) {
	$colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
	}
	// Check if given background color found
	if (isset($this->background_colors[$background_color])) {
	$colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
	}
 
	// Add string and end coloring
	$colored_string .=  $string . "\033[0m";
 
	return $colored_string;
	}
 
	// Returns all foreground color names
	public function getForegroundColors() {
	return array_keys($this->foreground_colors);
	}
 
	// Returns all background color names
	public function getBackgroundColors() {
	return array_keys($this->background_colors);
	}
}
 
