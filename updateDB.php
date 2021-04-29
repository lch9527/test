<?php
	// check this file's MD5 to make sure it wasn't called before
	$prevMD5 = @file_get_contents(dirname(__FILE__) . '/setup.md5');
	$thisMD5 = md5(@file_get_contents(dirname(__FILE__) . '/updateDB.php'));

	// check if setup already run
	if($thisMD5 != $prevMD5) {
		// $silent is set if this file is included from setup.php
		if(!isset($silent)) $silent = true;

		// set up tables
		setupTable(
			'persons', " 
			CREATE TABLE IF NOT EXISTS `persons` ( 
				`personal_id` VARCHAR(40) NOT NULL,
				PRIMARY KEY (`personal_id`),
				`homeworld` VARCHAR(40) NULL,
				`blaster_ownership` VARCHAR(40) NULL,
				`landcruiser_ownership` VARCHAR(40) NULL
			) CHARSET utf8",
			$silent, [
				"ALTER TABLE persons ADD `field1` VARCHAR(40)",
				"ALTER TABLE `persons` CHANGE `field1` `personal_id` VARCHAR(40) NULL ",
				"ALTER TABLE `persons` CHANGE `personal_id` `personal_id` VARCHAR(40) NOT NULL ",
				"ALTER TABLE persons ADD `field2` VARCHAR(40)",
				"ALTER TABLE `persons` CHANGE `field2` `homeworld` VARCHAR(40) NULL ",
				"ALTER TABLE persons ADD `field3` VARCHAR(40)",
				"ALTER TABLE `persons` CHANGE `field3` `blaster_ownership` VARCHAR(40) NULL ",
				"ALTER TABLE persons ADD `field4` VARCHAR(40)",
				"ALTER TABLE `persons` CHANGE `field4` `landcruiser_ownership` VARCHAR(40) NULL ",
				"ALTER TABLE `persons` ADD PRIMARY KEY (`personal_id`)",
			]
		);

		setupTable(
			'blasters', " 
			CREATE TABLE IF NOT EXISTS `blasters` ( 
				`manufacturer` VARCHAR(40) NULL,
				`date` DATE NULL,
				`blaster_id` VARCHAR(40) NOT NULL,
				PRIMARY KEY (`blaster_id`)
			) CHARSET utf8",
			$silent, [
				"ALTER TABLE blasters ADD `field1` VARCHAR(40)",
				"ALTER TABLE `blasters` CHANGE `field1` `manufacturer` VARCHAR(40) NULL ",
				"ALTER TABLE blasters ADD `field2` VARCHAR(40)",
				"ALTER TABLE `blasters` CHANGE `field2` `date` VARCHAR(40) NULL ",
				"ALTER TABLE `blasters` CHANGE `date` `date` DATE NULL ",
				"ALTER TABLE blasters ADD `field3` VARCHAR(40)",
				"ALTER TABLE `blasters` CHANGE `field3` `blaster_id` VARCHAR(40) NULL ",
				"ALTER TABLE `blasters` CHANGE `blaster_id` `blaster_id` VARCHAR(40) NOT NULL ",
				"ALTER TABLE `blasters` ADD PRIMARY KEY (`blaster_id`)",
			]
		);

		setupTable(
			'landcruisers', " 
			CREATE TABLE IF NOT EXISTS `landcruisers` ( 
				`manufacturer` VARCHAR(40) NULL,
				`date` DATE NULL,
				`landcruisers_id` VARCHAR(40) NOT NULL,
				PRIMARY KEY (`landcruisers_id`)
			) CHARSET utf8",
			$silent, [
				"ALTER TABLE landcruisers ADD `field1` VARCHAR(40)",
				"ALTER TABLE `landcruisers` CHANGE `field1` `manufacturer` VARCHAR(40) NULL ",
				"ALTER TABLE landcruisers ADD `field2` VARCHAR(40)",
				"ALTER TABLE `landcruisers` CHANGE `field2` `date` VARCHAR(40) NULL ",
				"ALTER TABLE `landcruisers` CHANGE `date` `date` DATE NULL ",
				"ALTER TABLE landcruisers ADD `field3` VARCHAR(40)",
				"ALTER TABLE `landcruisers` CHANGE `field3` `landcruisers_id` VARCHAR(40) NULL ",
				"ALTER TABLE `landcruisers` CHANGE `landcruisers_id` `landcruisers_id` VARCHAR(40) NOT NULL ",
				"ALTER TABLE `landcruisers` ADD PRIMARY KEY (`landcruisers_id`)",
			]
		);

		setupTable(
			'crimes', " 
			CREATE TABLE IF NOT EXISTS `crimes` ( 
				`type` VARCHAR(40) NULL,
				`date` DATE NULL,
				`file_num` VARCHAR(40) NOT NULL,
				PRIMARY KEY (`file_num`)
			) CHARSET utf8",
			$silent, [
				"ALTER TABLE crimes ADD `field1` VARCHAR(40)",
				"ALTER TABLE `crimes` CHANGE `field1` `type` VARCHAR(40) NULL ",
				"ALTER TABLE crimes ADD `field2` VARCHAR(40)",
				"ALTER TABLE `crimes` CHANGE `field2` `date` VARCHAR(40) NULL ",
				"ALTER TABLE `crimes` CHANGE `date` `date` DATE NULL ",
				"ALTER TABLE crimes ADD `field3` VARCHAR(40)",
				"ALTER TABLE `crimes` CHANGE `field3` `file_num` VARCHAR(40) NULL ",
				"ALTER TABLE `crimes` CHANGE `file_num` `file_num` VARCHAR(40) NOT NULL ",
				"ALTER TABLE `crimes` ADD PRIMARY KEY (`file_num`)",
			]
		);



		// save MD5
		@file_put_contents(dirname(__FILE__) . '/setup.md5', $thisMD5);
	}


	function setupIndexes($tableName, $arrFields) {
		if(!is_array($arrFields) || !count($arrFields)) return false;

		foreach($arrFields as $fieldName) {
			if(!$res = @db_query("SHOW COLUMNS FROM `$tableName` like '$fieldName'")) continue;
			if(!$row = @db_fetch_assoc($res)) continue;
			if($row['Key']) continue;

			@db_query("ALTER TABLE `$tableName` ADD INDEX `$fieldName` (`$fieldName`)");
		}
	}


	function setupTable($tableName, $createSQL = '', $silent = true, $arrAlter = '') {
		global $Translation;
		$oldTableName = '';
		ob_start();

		echo '<div style="padding: 5px; border-bottom:solid 1px silver; font-family: verdana, arial; font-size: 10px;">';

		// is there a table rename query?
		if(is_array($arrAlter)) {
			$matches = [];
			if(preg_match("/ALTER TABLE `(.*)` RENAME `$tableName`/i", $arrAlter[0], $matches)) {
				$oldTableName = $matches[1];
			}
		}

		if($res = @db_query("SELECT COUNT(1) FROM `$tableName`")) { // table already exists
			if($row = @db_fetch_array($res)) {
				echo str_replace(['<TableName>', '<NumRecords>'], [$tableName, $row[0]], $Translation['table exists']);
				if(is_array($arrAlter)) {
					echo '<br>';
					foreach($arrAlter as $alter) {
						if($alter != '') {
							echo "$alter ... ";
							if(!@db_query($alter)) {
								echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
								echo '<div class="text-danger">' . $Translation['mysql said'] . ' ' . db_error(db_link()) . '</div>';
							} else {
								echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
							}
						}
					}
				} else {
					echo $Translation['table uptodate'];
				}
			} else {
				echo str_replace('<TableName>', $tableName, $Translation['couldnt count']);
			}
		} else { // given tableName doesn't exist

			if($oldTableName != '') { // if we have a table rename query
				if($ro = @db_query("SELECT COUNT(1) FROM `$oldTableName`")) { // if old table exists, rename it.
					$renameQuery = array_shift($arrAlter); // get and remove rename query

					echo "$renameQuery ... ";
					if(!@db_query($renameQuery)) {
						echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
						echo '<div class="text-danger">' . $Translation['mysql said'] . ' ' . db_error(db_link()) . '</div>';
					} else {
						echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
					}

					if(is_array($arrAlter)) setupTable($tableName, $createSQL, false, $arrAlter); // execute Alter queries on renamed table ...
				} else { // if old tableName doesn't exist (nor the new one since we're here), then just create the table.
					setupTable($tableName, $createSQL, false); // no Alter queries passed ...
				}
			} else { // tableName doesn't exist and no rename, so just create the table
				echo str_replace("<TableName>", $tableName, $Translation["creating table"]);
				if(!@db_query($createSQL)) {
					echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
					echo '<div class="text-danger">' . $Translation['mysql said'] . db_error(db_link()) . '</div>';
				} else {
					echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
				}
			}
		}

		echo '</div>';

		$out = ob_get_clean();
		if(!$silent) echo $out;
	}
