<?php

$qb = new QuickBibleCli();

if (!$qb->parseCli()){
	$qb->simpleParseCli();
}

try{
	$qb->validateBible();
}catch(Exception $e){
	echo $e->getMessage();
	exit;
}

$qb->searchBible();

//scroll down for class

















class QuickBibleCli{
	protected $base_dir;
	protected $bible;
	protected $options = array('q'=>'');
	protected $bible_path = '';

	public function __construct(){
		//defaults
		$this->base_dir = __DIR__.'/bibles'; //"C:\Program Files (x86)\QuickBible\Bibles";
		$this->bible = 'kjvr';
	}

	public function simpleParseCli(){
		global $argv;
		//qb <bible> <search-query>
		if (isset($argv[1])){
			$this->bible = $argv[1];
		}

		if (isset($argv[2])){
			//conjoin all parameters into one
			for($i = 2; $i < count($argv); ++$i){
				if ($i > 2){
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
		$shortopts .= 'h';  //help

		$longopts  = array(
		    "basedir::",    // Optional value
		    "bible::",        // optional value
		    "color",           // show colors
		    "raw",          //show raw text (including rtf characters)
		    "help",         //show help
		);

		$options = getopt($shortopts, $longopts);
		if (empty($options)){
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
		echo "Usage: php ".$argv[0].' kjvr Jesus wept';
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

	public function searchBible(){
		

		$dbh = new PDO('sqlite:'.$this->bible_path);

		if (!empty($this->options['q'])){
			if (preg_match('/([\da-z][a-z][a-z]) (\d+):?(\d+)?\-?(\d+)?/i', $this->options['q'], $regs)) {
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
			       echo $row['v'].' ';

			       $row['t'] = str_replace('\\emdash', '--', $row['t']);

			       if (isset($this->options['color'])){
			       	  echo $this->printColorArray( $this->colorize($row['t']) );
			       }else if (isset($this->options['raw'])){
			       	  echo $row['t'];
			       }else{
			       	  $t = preg_replace('/({\\\\[\w+\\\\]+ )|(})/i', '', $row['t']);
			       	  echo $t;
			       }
			       
			       echo "\n";
			       $count++;
			    }
		    	return;
			} 

			


			$sql = 'SELECT * FROM bible_verses WHERE t LIKE :search';
			$ary = array('search'=>'%'.$this->options['q'].'%');

			$prep = $dbh->prepare($sql);
		    $prep->execute($ary);
		    $count = 1;
		    while($row = $prep->fetch(PDO::FETCH_ASSOC)){
		       //echo $count.'. ';
		       echo self::getAbbr($row['b']).' '.$row['c'].':'.$row['v'].' ';
		       echo $this->printColorArray( $this->colorize($row['t']) );
		       echo "\n";
		       $count++;
		    }
		}else{
			$sql = 'SELECT * FROM bible_info LIMIT 1';
			$prep = $dbh->prepare($sql);
			$prep->execute(array());
			$info = $prep->fetch(PDO::FETCH_ASSOC);

			print_r($info);
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
	"GEN"=>1,
	"EXO"=>2,
	"LEV"=>3,
	"NUM"=>4,
	"DEU"=>5,
	"JOS"=>6,
	"JDG"=>7,
	"RUT"=>8,
	"1SA"=>9,
	"2SA"=>10,
	"1KI"=>11,
	"2KI"=>12,
	"1CH"=>13,
	"2CH"=>14,
	"EZR"=>15,
	"NEH"=>16,
	"EST"=>17,
	"JOB"=>18,
	"PSA"=>19,
	"PRO"=>20,
	"ECC"=>21,
	"SON"=>22,
	"ISA"=>23,
	"JER"=>24,
	"LAM"=>25,
	"EZE"=>26,
	"DAN"=>27,
	"HOS"=>28,
	"JOE"=>29,
	"AMO"=>30,
	"OBA"=>31,
	"JON"=>32,
	"MIC"=>33,
	"NAH"=>34,
	"HAB"=>35,
	"ZEP"=>36,
	"HAG"=>37,
	"ZEC"=>38,
	"MAL"=>39,
	"MAT"=>40,
	"MAR"=>41,
	"LUK"=>42,
	"JOH"=>43,
	"ACT"=>44,
	"ROM"=>45,
	"1CO"=>46,
	"2CO"=>47,
	"GAL"=>48,
	"EPH"=>49,
	"PHI"=>50,
	"COL"=>51,
	"1TH"=>52,
	"2TH"=>53,
	"1TI"=>54,
	"2TI"=>55,
	"TIT"=>56,
	"PHM"=>57,
	"HEB"=>58,
	"JAM"=>59,
	"1PE"=>60,
	"2PE"=>61,
	"1JO"=>62,
	"2JO"=>63,
	"3JO"=>64,
	"JUD"=>65,
	"REV"=>66,
	);

	public static $bible_abbr = array(
	"   ",
	"GEN",
	"EXO",
	"LEV",
	"NUM",
	"DEU",
	"JOS",
	"JDG",
	"RUT",
	"1SA",
	"2SA",
	"1KI",
	"2KI",
	"1CH",
	"2CH",
	"EZR",
	"NEH",
	"EST",
	"JOB",
	"PSA",
	"PRO",
	"ECC",
	"SON",
	"ISA",
	"JER",
	"LAM",
	"EZE",
	"DAN",
	"HOS",
	"JOE",
	"AMO",
	"OBA",
	"JON",
	"MIC",
	"NAH",
	"HAB",
	"ZEP",
	"HAG",
	"ZEC",
	"MAL",
	"MAT",
	"MAR",
	"LUK",
	"JOH",
	"ACT",
	"ROM",
	"1CO",
	"2CO",
	"GAL",
	"EPH",
	"PHI",
	"COL",
	"1TH",
	"2TH",
	"1TI",
	"2TI",
	"TIT",
	"PHM",
	"HEB",
	"JAM",
	"1PE",
	"2PE",
	"1JO",
	"2JO",
	"3JO",
	"JUD",
	"REV");

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
 