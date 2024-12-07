<?php

include __DIR__ . '/compatibility.php';

CModule::AddAutoloadClasses(
	'biconnector',
	[
		'CBIConnectorSqlBuilder' => 'classes/sqlbuilder.php',
		'biconnector' => 'install/index.php',
	]
);
