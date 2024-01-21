<?php

CJSCore::RegisterExt('documentpreview', [
	'js' => '/bitrix/js/documentgenerator/documentpreview.js',
	'css' => '/bitrix/js/documentgenerator/css/documentpreview.css',
	'lang' => '/bitrix/modules/documentgenerator/lang/'.LANGUAGE_ID.'/install/js/documentpreview.php',
	'rel' => ['core', 'ajax', 'sidepanel', 'loader', 'popup'],
]);

\Bitrix\Main\Loader::registerAutoLoadClasses(
	"documentgenerator",
	[
		"petrovich" => "lib/external/petrovich.php",
	]
);
