<?php

use Bitrix\Main\Loader;

\Bitrix\Main\Loader::registerAutoLoadClasses(
	'imbot',
	[
		'imbot' => 'install/index.php',
	]
);

$documentRoot = Loader::getDocumentRoot();
if (is_dir($documentRoot . '/bitrix/modules/imbot/dev/'))
{
	// developer mode
	Loader::registerNamespace('Bitrix\Imbot\Dev',  $documentRoot . '/bitrix/modules/imbot/dev');
}
