<?php

	/***************************************************************************
	 * PHP-HTML template engine, Thomas Radeke, July 2010
	 * This class can load HTML template files recursively and replace special
	 * tags inside them with the contents of variables.
	 * PHP code inside templates will not be evaulated; Multiple generated
	 * instances of some object must be prepared one by one with their own
	 * template and inserted as a variable content into another template.
	 * In other words: Prepare first, output later.
	 * The basis of this class is a search-and-replace method.	 
	 ***************************************************************************/

	class HTMLfromTemplate {
	
		private $openingTag = "{";
		private $closingTag = "}";
		private $templateFile;
		private $templateFileContent;
		private $elements = array();
		private $noreplace = array();
		
		// Construct the object from a template and a number of strings to replace.
		// List of element mappings is optional; if no mappings are given, no replacement will happen.
		// Will attempt to load sub-templates recursively.
		public function __construct($templateFile, $elements = array(), $noreplace = array()) {
			$this->elements = $elements;
			$this->setTemplate($templateFile);
			$this->noreplace = $noreplace;
		}
		
		// Set template filename and trigger loading.
		public function setTemplate($file) {
			if(is_file($file)) {
				$this->templateFile = $file;
				$this->load();
			} else {
				$this->templateFileContent = $file;
				$this->templateFileContent = $this->loadRecursive($this->templateFileContent);
			}
		}
		
		// Load template file content.
		private function load() {
			$this->templateFileContent = file_get_contents($this->templateFile);
			$this->templateFileContent = $this->loadRecursive($this->templateFileContent);
		}
		
		// Function sets template opening and closing tags. Defaults to curly braces but can be any sequence of characters.
		public function setTemplateTags($openingTag, $closingTag) {
			if(is_string($openingTag) && !empty($openingTag)) {
				$this->openingTag = $openingTag;
			}
			if(is_string($closingTag) && !empty($closingTag)) {
				$this->closingTag = $closingTag;
			}
		}
		
		// Return array of replaceable elements.
		public function getReplaceableElements($input) {
			$matches = array();
			$return = array();
			$expression = "/".$this->openingTag."[[:print:]]+?".$this->closingTag."/";
			preg_match_all($expression, $input, $matches);
			//debug($matches);
			if(count($matches)){
				foreach($matches as $match) {
					if(!in_array($match, $this->noreplace)){
						$string = str_replace($this->openingTag, "", $match);
						$string = str_replace($this->closingTag, "", $string);
						$return[] = $string;
					}
				}
				return $return[0];
			} else {
				return false;
			}
		}
		
		// Load templates recursively, if one of the current elements is a filename.
		private function loadRecursive($input) {
			$elements = $this->getReplaceableElements($input);
			$replace = array();
			$return = $input;
			if($elements){
				foreach($elements as $element) {
					if($element[0] != "#"){
						if(is_file($element)) {
							$temp = new HTMLfromTemplate($element);
							$temp = $temp->getHTML();
							$return = str_replace($this->openingTag . $element . $this->closingTag, $temp, $input);
							$return = $this->loadRecursive($return);
						}
					}
				}
			}
			return $return;
		}

		// Return elements and template combined to full HTML. Elements that have no mapping will not be replaced.
		// Additionally, if there is no replacement for a mapping, the function will try to display the translated plaintext instead.
		public function getHTML() {
			$return = $this->templateFileContent;
			
			if(count($this->elements)) {
				foreach ($this->elements as $key => $value) {
				
					//debug($key);
					//debug($value);
				
					/*
					if(empty($value)){
						$value = " ";
					}
					*/
					
					//debug($value);
					
					// replace all elements that don't begin with a hashtag, even if they're empty
					//if(isset($value[0]) && ($value[0] != "#")) {
						$return = str_replace($this->openingTag . $key . $this->closingTag, $value, $return);
					/*} else {
						$return = str_replace($this->openingTag . $key . $this->closingTag, $value, $return);
					}
					*/
				}
				
				$unreplaced = $this->getReplaceableElements($return);
				foreach($unreplaced as $word){
					if(!in_array($word, $this->noreplace)){
						if($word[0] != "#") {
							$return = str_replace($this->openingTag . $word . $this->closingTag, $word, $return);
						} else {
							$return = str_replace($this->openingTag . $word . $this->closingTag, "", $return);
						}
					}
				}
				
			}
			return $return;
		}
		
		// Output full HTML.
		public function render() {
			print($this->getHTML());
		}
		
		public static function template($template, $elements){
			global $default_template;
			
			$result = "";
			
			// if the specified template file does not exist, look for the default
			if(!file_exists($template)){
				$template = $default_template;
			}
			
			if($tpl = new HTMLfromTemplate($template, $elements)){
				$result = $tpl->getHTML();
			}
			
			return $result;
		}
		
	}
	
?>