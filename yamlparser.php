<?php
	namespace Safflower;

	class YamlParser{

		public static function get($filePath){
			$contents = self::readFile($filePath);
			if($contents === false){
				return false;
			}
			return self::parse($contents);
		}

		private static function readFile($filePath){
			if(!is_file($filePath)){
				trigger_error('Unable to parse '.basename($filePath).' file.', E_USER_WARNING);
				return false;
			}
			$contents = file_get_contents($filePath);
			// remove BOM
			if(substr($contents, 0, 3) === "\xEF\xBB\xBF"){
				$contents = substr($contents, 3);
			}
			return $contents;
		}

		private static function parse(&$contents){
			$result = [];
			$lines = preg_split("/(\r\n|\n)+/", $contents);
			$parent = [];
			$padding = '';
			$first = false;
			foreach($lines as $line){
				if(preg_match('/^(?<padding>\t*)(?<variable>\w+)[ \t]*:[ \t]*(?<contents>.*?)(?<comment>(\#.*?)?)[ \t]*$/', $line, $match)){
					if($first){
						$first = false;
						$padding = $match['padding'];
					}elseif(strlen($match['padding']) < strlen($padding)){
						$len = strlen($padding) - strlen($match['padding']);
						for($i = 0; $i < $len; ++$i){
							array_pop($parent);
						}
						$padding = $match['padding'];
					}
					if(!isset($match['contents'][0])){
						$parent[] = $match['variable'];
						$first = true;
					}else{
						$resultPointer = &$result;
						foreach($parent as $child){
							$resultPointer = &$resultPointer[$child];
						}

						$buffer = $match['contents'];
						$bufferLower = strtolower($buffer);

						if($buffer[0] === '"' && substr($buffer, -1) === '"' || $buffer[0] === '\'' && substr($buffer, -1) === '\''){
							$buffer = substr($buffer, 1, -1);
							$buffer = strtr($buffer, [
								'\\\'' => '\'',
								'\\"' => '"',
								'\\\\' => '\\',
								'\\t' => "\t",
								'\\r' => "\r",
								'\\n' => "\n",
								'\\0' => "\0"
							]);

						}elseif(in_array($bufferLower, ['true', 'on', 'yes', 'y'], true)){
							$buffer = true;

						}elseif(in_array($bufferLower, ['false', 'off', 'no', 'n'], true)){
							$buffer = false;

						}elseif(preg_match('/^[+-]?\d+(?<decimal>\.\d+)?$/', $buffer, $match2)){
							$buffer = isset($match2['decimal']) ? (float)$buffer : (int)$buffer;
						}

						$resultPointer[$match['variable']] = $buffer;
					}
				}else{
					$lineTrim = trim($line, " \t");
					if($lineTrim !== '' && $lineTrim[0] !== '#'){
						return false;
					}
				}
			}
			return $result;
		}
	}