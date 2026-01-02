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

	$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

	if ($request->get('IFRAME') === 'Y' && $request->get('IFRAME_TYPE') === 'SIDE_SLIDER')
	{
		$APPLICATION->IncludeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'USE_UI_TOOLBAR' => 'Y',
				'POPUP_COMPONENT_NAME' => 'bitrix:bizproc.user.processes.start',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => [],
				'POPUP_COMPONENT_USE_BITRIX24_THEME' => 'Y',
			]
		);
	}
	else
	{
		$APPLICATION->IncludeComponent(
			'bitrix:bizproc.user.processes.start',
			'.default',
			[],
		);
	}
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
