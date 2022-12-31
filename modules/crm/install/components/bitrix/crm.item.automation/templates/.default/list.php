<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $APPLICATION;

$cmpName = 'bitrix:crm.item.list';

$navigationIndex = CUserOptions::GetOption('crm.navigation', 'index');
$mainPage = explode(':', ($navigationIndex[strtolower(\CCrmOwnerType::ResolveName($arParams['entityTypeId']))] ?? ''))[0];

if (strtolower($mainPage) === 'kanban')
{
	$cmpName = 'bitrix:crm.item.kanban';
}

if ((int)$arParams['entityTypeId'] === \CCrmOwnerType::Quote)
{
	$cmpName = 'bitrix:crm.quote';
}

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => $cmpName,
		'POPUP_COMPONENT_PARAMS' => [
			'entityTypeId' => $arParams['entityTypeId'],
			'categoryId' => $arParams['categoryId'],
		],
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'Y',
	],
	$this->getComponent()
);
Bitrix\Main\UI\Extension::load('sidepanel');
?>
<script>
	BX.ready(function()
	{
		var a = document.querySelector('.crm-robot-btn');
		if (a && BX.SidePanel)
		{
			var url = a.href;

			<?php if (!empty($_GET['id'])): ?>
			url += '?id=<?=(int)$_GET['id']?>';
			<?php endif ?>

			BX.SidePanel.Instance.open(url, { customLeftBoundary: 0 });
		}
	});
</script>