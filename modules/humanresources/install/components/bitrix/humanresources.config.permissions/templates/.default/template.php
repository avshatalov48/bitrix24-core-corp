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
		'ui.analytics',
		'loader',
	]
);
$componentId = 'bx-access-group';
$initPopupEvent = 'humanresources:onComponentLoad';
$openPopupEvent = 'humanresources:onComponentOpen';
$cantUse = isset($arResult['CANT_USE']);

\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();

?>

<div id="bx-humanresources-role-main"></div>

<?php
$APPLICATION->SetPageProperty('BodyClass', 'ui-page-slider-wrapper-hr --premissions');
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
			'ONCLICK' => $cantUse? "(function (button) { BX.UI.InfoHelper.show('limit_office_company_structure'); setTimeout(()=>{button.classList.remove('ui-btn-wait')}, 0)})(this)":
				"window.AccessRights.sendActionRequest(); BX.UI.Analytics.sendData({tool: 'structure',category: 'structure',event: 'save_changes',})",
		],
		[
			'TYPE' => 'cancel',
			'ONCLICK' => "window.AccessRights.fireEventReset(); BX.UI.Analytics.sendData({tool: 'structure',category: 'structure',event: 'cancel_changes',})",
		],
	],
]);

if($cantUse)
{
	$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", array());
	?>
	<script>
		BX.ready(function (){
			BX.UI.InfoHelper.show('limit_office_company_structure');
		});
	</script>
<?php
}
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