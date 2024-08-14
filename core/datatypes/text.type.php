<?php
/**
 * Text datatype class, manages textareas and WW editors content. 
 * Many properties are inherited from Char datatype.
 * Works together with core Editor class.
 */
class TextModelElement extends CharModelElement
{
	protected $rich_text = false;
	
	protected $height;
	
	protected $show_in_admin = false;
	
	protected $auto_cleanup = true;
	
	protected $images_path = 'userfiles/images/';
	
	protected $files_path = 'userfiles/files/';
	
	protected $display_method;
	
	protected $virtual = false;
	
	public function setValue($value)
	{
		$value = is_null($value) ? '' : $value;
		$this -> value = str_replace("\t", '', $value);
		
		if($this -> rich_text)
		{
			$search = array("'", "\\");
			$replace = array("&#039;", "&#92;");
			$this -> value = preg_replace("/<script([^>]*)>/i", "&lt;script$1&gt;", $this -> value);
			$this -> value = str_ireplace("</script>", "&lt;/script&gt;", $this -> value);
			$this -> value = str_ireplace("<meta ", "&lt;meta ", $this -> value);
			
			if($this -> auto_cleanup)
				$this -> value = $this -> cleanupText($this -> value);
		}
		else 
		{
			$search = array("&", "'", "<", ">", '"', "\\");
			$replace = array("&amp;", "&#039;", "&lt;", "&gt;", "&quot;", "&#92;");
		}
		
		$this -> value = trim(str_ireplace($search, $replace, $this -> value));
		
		return $this;
	}
		
	public function displayHtml()
	{
		$id = "textarea_".$this -> name;

		if(is_string($this -> height))
			$this -> height = str_replace('px', '', $this -> height);

		$height = $this -> height ? $this -> height : 100;
		$css = $this -> addHtmlParams() ? $this -> addHtmlParams() : " class=\"form-textarea\"";

		if($this -> placeholder !== '')
			$css .= ' placeholder="'.$this -> placeholder.'"';

		$height = $this -> rich_text ? '' : 'style="height: '.$height.'px" ';

		$html = "<textarea ".$height."id=\"".$id."\" name=\"".$this -> name."\"".$css.">";
		$html .= $this -> value."</textarea>\n".$this -> addHelpText();
		
		if($this -> rich_text)
		{
			$height = $this -> height ? $this -> height : 300;
			$html .= Editor :: run($id, $height);
		}
		
		return $html;
	}
	
	private function cleanupText($text)
	{
		$tags = "(p|li|div|span|strong|em|h1|h2|h3|h4|h5|h6)";
		
		$text = preg_replace("/(\s*&nbsp;\s*){2,}/", " ", $text);
		$text = preg_replace("/\s*(&nbsp;)?\s*<\/".$tags.">/", "</$2>", $text);
		$text = preg_replace("/<".$tags.">\s*(&nbsp;)?\s*/", "<$1>", $text);

		return $text;
	}
}
