<?php

	require __DIR__.'/yamlparser.php';
	use Safflower\YamlParser;

	$filePath = __DIR__.'/test.yml';
	$result = YamlParser::get($filePath);

	header('Content-Type: text/plain');
	var_dump($result);