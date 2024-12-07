<?php

/**
 * @var array $arResult
 * @var array $arParams
 * @var CMain $APPLICATION
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Bitrix\Main\UI\Extension::load(
	[
		'ui.buttons',
		'ui.icons',
		'ui.notification',
		'ui.accessrights',
		'ui.selector',
		'ui',
		'ui.info-helper',
		'ui.actionpanel',
		'ui.design-tokens',
		'loader',
	]
);
$componentId = 'bx-access-group';
$initPopupEvent = 'humanresources:onComponentLoad';
$openPopupEvent = 'humanresources:onComponentOpen';
\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();

?>

<div id="bx-humanresources-role-main"></div>

<?php
$APPLICATION->IncludeComponent(
	"bitrix:main.ui.selector",
	".default",
	[
		'API_VERSION' => 2,
		'ID' => $componentId,
		'BIND_ID' => $componentId,
		'ITEMS_SELECTED' => [],
		'CALLBACK' => [
			'select' => "AccessRights.onMemberSelect",
			'unSelect' => "AccessRights.onMemberUnselect",
			'openDialog' => 'function(){}',
			'closeDialog' => 'function(){}',
		],
		'OPTIONS' => [
			'eventInit' => $initPopupEvent,
			'eventOpen' => $openPopupEvent,
			'useContainer' => 'Y',
			'lazyLoad' => 'Y',
			'context' => 'HUMAN_RESOURCES_PERMISSION',
			'contextCode' => '',
			'useSearch' => 'Y',
			'useClientDatabase' => 'Y',
			'allowEmailInvitation' => 'N',
			'enableAll' => 'N',
			'enableUsers' => 'Y',
			'enableDepartments' => 'Y',
			'enableGroups' => 'Y',
			'departmentSelectDisable' => 'N',
			'allowAddUser' => 'Y',
			'allowAddCrmContact' => 'N',
			'allowAddSocNetGroup' => 'N',
			'allowSearchEmailUsers' => 'N',
			'allowSearchCrmEmailUsers' => 'N',
			'allowSearchNetworkUsers' => 'N',
			'useNewCallback' => 'Y',
			'multiple' => 'Y',
			'enableSonetgroups' => 'Y',
			'showVacations' => 'Y',
		]
	],
	false,
	["HIDE_ICONS" => "Y"]
);

$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
	'HIDE'    => true,
	'BUTTONS' => [
		[
			'TYPE' => 'save',
			'ONCLICK' => "window.AccessRights.sendActionRequest()",

		],
		[
			'TYPE' => 'cancel',
			'ONCLICK' => "window.AccessRights.fireEventReset()",
		],
	],
]);
?>

<script>
	BX.ready(function() {
		window.AccessRights = new BX.UI.AccessRights({
			renderTo: document.getElementById('bx-humanresources-role-main'),
			userGroups: <?= CUtil::PhpToJSObject($arResult['USER_GROUPS'] ?? []) ?>,
			accessRights: <?= CUtil::PhpToJSObject($arResult['ACCESS_RIGHTS'] ?? []); ?>,
			component: 'bitrix:humanresources.config.permissions',
			actionSave: 'savePermissions',
			actionDelete: 'deleteRole',
			popupContainer: '<?= $componentId ?>',
			openPopupEvent: '<?= $openPopupEvent ?>'
		});

		window.AccessRights.draw();

		BX.ready(function() {
			setTimeout(function() {
				BX.onCustomEvent('<?= $initPopupEvent ?>', [{openDialogWhenInit: false, multiple: true }]);
			});
		});
	});
</script>