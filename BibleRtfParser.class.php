<?php
/*
        <biblertf> => ( <emdash> | <text> | <rtf-italics-block> | <rtf-red-block> ) [ <biblertf> ]
        <rtf-italics-block> => <rtf-open> '\cf15\i' <space> <text> <rtf-close> <space>
	    <rtf-red-block> => <rtf-open> '\cf6' <space> <text> <rtf-close> <space>

		<emdash> => '\emdash'
        <text> => ANYTHING-BUT-BRACES
        <rtf-open> => '{'
        <rtf-close> => '}'
        <space> = ' '
*/

class BRToken
{
    public $lexeme;
    public $tokenType;
};

class BRTokenTypes {
	const RtfOpen = 1;
	const Italics = 2;
	const Red = 3;
	const RtfClose = 4;
	const EmDash = 5;
	const Space = 6;
    const Text = 7;
    const ItalicText = 8;
    const RedText = 9;

    public static $lit_to_tt = array(
    	'{\\cf'=>'RtfOpen',
    	  '15\\i '=>'Italics',
    	  '6 '=>'Red',
    	  '}'=>'RtfClose',
    	  '\\emdash'=>'EmDash',
    	  ' '=>'Space',
    );

    public static $tt_to_lit = array(
	  'RtfOpen' => '{\\cf';
	  'Italics' => '15\\i ';
	  'Red' => '6 ';
	  'RtfClose' => '}';
	  'EmDash' => '\\emdash';
	  'Space' => ' ';
   	);
}

class BibleRtfParse {
	public $curToken;
	public $peekToken;
	public $tokenStack = array();

	public $ary;

	private $cur_char = 0; 
	private $string = '';

	private function cur_char(){
	 	if (isset($this->string{$this->cur_char})){
	 		return $this->string{$this->cur_char};
	 	}else{
	 		return false;
	 	}
	}

	private function peek_char(){
	 	if (isset($this->string{$this->cur_char+1})){
	 		return $this->string{$this->cur_char+1};
	 	}else{
	 		return false;
	 	}		
	}

	private function next_char(){
		$this->cur_char++;
		return $this->cur_char();
	}

	private function eatText(){
		$s = '';
		$c = $this->cur_char();
		while($c != '{' && $c != '}' && $c != '\\'){
			if ($c === false){
				return $s;
			}

			$s .= $c;
			$c = $this->next_char();
		}
		return $s;
	}

	public function __construct($s){
		$this->string = $s;
		$this->biblertf();
	}



	public function biblertf(){
		$c = $this->cur_char();
		if ($c === false){ return; }
		switch($c){
			case '{': $this->rtf_open();
			break;
			case '\\': $this->emdash();
			break;
			default: 
				$this->text();
		    break;
		}

		if ($this->peek_char() === false){
			return;
		}else{
			$this->biblertf();
		}
	}


	public function text(){
		$text = $this->eatText();
		if (empty($text)){ return false; }

		$token = new BRToken();
		$token->lexeme = $text;
		$token->tokenType = BRTokenTypes::Text;
		$this->tokenStack[] = $token;
	}

	public function rtf_open(){
       $c = $this->cur_char();
       $s = '';
       if ($c == '{'){
       	 if ($this->peek_char() == '\\'){
       	 	$s .= $this->next_char();
       	 	if ($this->peek_char() == 'c'){
       	 		$s .= $this->next_char();
       	 		if ($this->peek_char() == 'f'){
       	 			$s .= $this->next_char();
       	 			if ($this->peek_char() == '6'){
       	 				$this->next_char();
       	 				$this->rtf_red_block();
       	 			}
       	 		}
       	 	}else{
       	 		throw new Exception('Invalid rtf char: ': $this->peek_char());
       	 	}
       	 }else{
       	 	throw new Exception('Invalid char: '.$this->peek_char());
       	 }
       }
	}

	public function emdash(){

	}

	public function rtf_italics_block(){

	}

	public function rtf_red_block(){

	}

}