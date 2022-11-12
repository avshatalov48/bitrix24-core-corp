<?php

CModule::AddAutoloadClasses(
	'biconnector',
	[
		'CBIConnectorSqlBuilder' => 'classes/sqlbuilder.php',
		'biconnector' => 'install/index.php',
	]
);

$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler(
	'biconnector',
	'OnBIConnectorValidateDashboardUrl',
	function (\Bitrix\Main\Event $event)
	{
		$url = $event->getParameters()[0];
		$uri = new \Bitrix\Main\Web\Uri($url);
		$isUrlOk = ($uri->getScheme() === 'https') && ($uri->getHost() === 'datalens.yandex.ru');
		$result = new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, ($isUrlOk ? 3 : 0));
		return $result;
	}
);
