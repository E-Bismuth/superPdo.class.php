<?php

require_once("superPdo.class.php");

$db = SPDO::getInstance();


		$insert= $db->prepare('
					INSERT INTO test (test)
					VALUES('.rand(10,500).')
				');
		
		$insert->execute();
		
		
		echo ($db->lastInsertId().PHP_EOL);
		

		$select= $db->prepare('
						SELECT *
						FROM test
		');
		
		$select->execute();
		
		
		print_r($select->fetchAll());