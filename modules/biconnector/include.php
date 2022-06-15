<?php

CModule::AddAutoloadClasses(
	'biconnector',
	[
		'CBIConnectorSqlBuilder' => 'classes/sqlbuilder.php',
		'biconnector' => 'install/index.php',
	]
);
