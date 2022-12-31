<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

$cmpParams = [
	'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
	'ENTITY_CATEGORY' => 0,
	'ENTITY_ID' => isset($_GET['id']) ? (int)$_GET['id'] : null,
	'SET_TITLE' => 'Y'
];

if ($_REQUEST['IFRAME'] == 'Y' && $_REQUEST['IFRAME_TYPE'] == 'SIDE_SLIDER')
{
	$cmpParams['DISABLE_TOP_MENU'] = 'Y';
	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		array(
			'POPUP_COMPONENT_NAME' => 'bitrix:crm.config.automation',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => $cmpParams,
			'POPUP_COMPONENT_USE_BITRIX24_THEME' => 'Y',
			'DEFAULT_THEME_ID' => 'light:robots',
			'USE_PADDING' => false,
		)
	);
}
else
{
	$navigationIndex = CUserOptions::GetOption('crm.navigation', 'index');
	$mainPage = explode(':', ($navigationIndex['lead'] ?? ''))[0];

	if (!in_array($mainPage, ['list', 'kanban', 'calendar']))
	{
		$mainPage = 'list';
	}
	include "$mainPage.php";

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
	<?php
}
