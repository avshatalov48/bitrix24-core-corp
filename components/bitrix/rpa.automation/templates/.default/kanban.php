<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $APPLICATION;

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:rpa.kanban',
		'POPUP_COMPONENT_PARAMS' => [
			'typeId' => $arParams["typeId"],
		],
		'USE_PADDING' => false,
	],
	$this->getComponent()
);
Bitrix\Main\UI\Extension::load('sidepanel');
?>
<script>
	BX.ready(function()
	{
		var a = document.querySelector('a.rpa-toolbar-btn[href^="/rpa/automation/"]');
		if (a && BX.SidePanel)
		{
			BX.SidePanel.Instance.open(a.href);
		}
	});
</script>
