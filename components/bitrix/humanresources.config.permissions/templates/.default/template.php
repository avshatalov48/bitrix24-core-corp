<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

/**
 * @var array $arResult
 * @var array $arParams
 * @var CMain $APPLICATION
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Loader::includeModule('ui');

Bitrix\Main\UI\Extension::load(
	[
		'ui.accessrights.v2',
		'ui.info-helper',
		'ui.analytics',
	]
);
$componentId = 'bx-access-group';
$initPopupEvent = 'humanresources:onComponentLoad';
$openPopupEvent = 'humanresources:onComponentOpen';
$cantUse = isset($arResult['CANT_USE']);
$analyticContext = is_array($arResult['ANALYTIC_CONTEXT'] ?? null) ? $arResult['ANALYTIC_CONTEXT'] : [];

\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrappermenu',
	'',
	[
		'TITLE' => $arResult['CONFIG_PERMISSION_MENU_TITLE'],
		'ITEMS' => $arResult['LEFT_MENU'],
	],
);

?>

<div class="hr-permission-config-role-main-wrapper">
	<div id="bx-hr-permission-config-action-search-container"></div>
	<div id="bx-hr-permission-config-role-main-container"></div>
</div>


<?php
$APPLICATION->SetPageProperty('BodyClass', 'ui-page-slider-wrapper-hr --premissions');

if($cantUse)
{
	?>
	<script>
		BX.ready(function (){
			BX.UI.InfoHelper.show('limit_office_company_structure');
		});
	</script>
<?php
}

$messages = Loc::loadLanguageFile(__FILE__);
?>

<script>
	BX.message(<?=Json::encode($messages)?>);
	const accessRightsOption = {
		component: 'bitrix:humanresources.config.permissions',
		actionSave: 'savePermissions',
		renderTo: document.getElementById('bx-hr-permission-config-role-main-container'),
		userGroups: <?= Json::encode($arResult['USER_GROUPS'] ?? []) ?>,
		accessRights: <?= Json::encode($arResult['ACCESS_RIGHTS'] ?? []) ?>,
		additionalSaveParams: {
			category: '<?= \CUtil::JSEscape($arResult['CATEGORY']) ?>'
		},
		loadParams: {
			category: '<?= \CUtil::JSEscape($arResult['CATEGORY']) ?>'
		},
		additionalMembersParams: {
			addUserGroupsProviderTab: true,
			addProjectsProviderTab: false,
			addStructureTeamsProviderTab: true,
			addStructureRolesProviderTab: true,
		},
		popupContainer: '<?= $componentId ?>',
		openPopupEvent: '<?= $openPopupEvent ?>',
		analytics: <?= Json::encode($analyticContext) ?>,
		searchContainerSelector: '#bx-hr-permission-config-action-search-container',
	}

	const accessRights = new BX.UI.AccessRights.V2.App(accessRightsOption)

	const ConfigPerms = new BX.Humanresources.ConfigPermsComponent({
		menuId: '<?=$arResult['MENU_ID'] ?? ''?>',
		accessRightsOption,
		accessRights,
	});

	ConfigPerms.init();

	BX.ready(function() {
		setTimeout(function() {
			BX.onCustomEvent('<?= $initPopupEvent ?>', [{openDialogWhenInit: false, multiple: true }]);
		});
	});
</script>

<?php
$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
	'HIDE' => true,
	'BUTTONS' => [
		[
			'TYPE' => 'save',
			'ONCLICK' => $cantUse
				? "(function (button) { BX.UI.InfoHelper.show('limit_office_company_structure'); setTimeout(()=>{button.classList.remove('ui-btn-wait')}, 0)})(this)"
				: "ConfigPerms.accessRights.sendActionRequest();",
		],
		[
			'TYPE' => 'custom',
			'LAYOUT' => (new \Bitrix\UI\Buttons\Button())
				->setColor(\Bitrix\UI\Buttons\Color::LINK)
				->setText(\Bitrix\Main\Localization\Loc::getMessage('HUMANRESOURCES_CONFIG_PERMISSIONS_CANCEL_BUTTON_TEXT'))
				->bindEvent('click', new \Bitrix\UI\Buttons\JsCode('ConfigPerms.accessRights.fireEventReset();'))
				->render()
			,
		],
	],
]);
