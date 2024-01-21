<?php

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

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
	$APPLICATION->IncludeComponent(
		"bitrix:bizproc.workflow.instances",
		"",
		array(
			"SET_TITLE" => 'Y',
			"NAME_TEMPLATE" => CSite::GetNameFormat(),
		)
	);
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
