<?php

require($_SERVER["DOCUMENT_ROOT"] . '/bitrix/header.php');

if (
	\Bitrix\Main\Loader::includeModule('bizproc')
	&& class_exists(\Bitrix\Bizproc\Integration\Intranet\ToolsManager::class)
	&& !\Bitrix\Bizproc\Integration\Intranet\ToolsManager::getInstance()->isBizprocAvailable()
)
{
	echo \Bitrix\Bizproc\Integration\Intranet\ToolsManager::getInstance()->getBizprocUnavailableContent();
}
else
{
	global $APPLICATION;
	$APPLICATION->IncludeComponent(
		'bitrix:bizproc.user.processes',
		'.default',
		[],
	);
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
