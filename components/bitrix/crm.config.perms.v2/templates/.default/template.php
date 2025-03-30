<?php

use Bitrix\Crm\Tour\PermissionsOnboardingPopup;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */
/** @var \CMain $APPLICATION */
/** @var \CBitrixComponentTemplate $this */

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? "{$bodyClass} " : '') . 'no-all-paddings no-background');


\Bitrix\Main\Loader::includeModule('ui');
\Bitrix\Main\UI\Extension::load([
	'ui.accessrights.v2',
]);

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . 'no-all-paddings no-background');

$searchContainerId = 'crm-config-perms-v2-search-container';

?>
<div class="crm-config-perms-v2-header"">
	<?php if (!$arResult['shouldDisplayLeftMenu']):?>
		<div class="crm-config-perms-v2-header-title"><?=Loc::getMessage('CRM_COMMON_PERMISSIONS_SETTINGS_ITEM')?></div>
	<?php endif;?>
	<div id="<?=$searchContainerId?>"></div>
</div>
<?php

/** @var \Bitrix\Crm\Security\Role\UIAdapters\AccessRights\AccessRightsDTO $rolesData */
$rolesData = $arResult['accessRightsData'];
$controllerData = $arResult['controllerData'];
/** @var int|null $maxVisibleUserGroups */
$maxVisibleUserGroups = $arResult['maxVisibleUserGroups'];
/** @var array|null $analytics */
$analytics = $arResult['analytics'];

if ($arResult['isSharedCrmPermissionsSlider'])
{
	echo PermissionsOnboardingPopup::getInstance()->build();
}

if ($arResult['shouldDisplayLeftMenu'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrappermenu',
		'',
		[
			'TITLE' => Loc::getMessage('CRM_COMMON_PERMISSIONS_SETTINGS_ITEM'),
			'ITEMS' => $arResult['leftMenu'],
			'AUTO_HIDE_SUBMENU' => true,
		],
	);
}

$messages = Loc::loadLanguageFile(__FILE__);
?>

<div id='bx-crm-perms-config-permissions'></div>

<script>
	BX.message(<?=Json::encode($messages)?>);
	const userGroups = <?= Json::encode($rolesData->userGroups) ?>;
	const accessRights = <?= Json::encode($rolesData->accessRights) ?>;
	const additionalSaveParams = <?= Json::encode($controllerData) ?>;
	const AccessRightsOption = {
		component: 'bitrix:crm.config.perms.v2',
		actionSave: 'save',
		bodyType: 'json',
		renderTo: document.getElementById('bx-crm-perms-config-permissions'),
		userGroups,
		accessRights,
		additionalSaveParams,
		isSaveOnlyChangedRights: true,
		maxVisibleUserGroups: <?= is_int($maxVisibleUserGroups) ? $maxVisibleUserGroups : 'null' ?>,
		searchContainerSelector: '#<?= $searchContainerId ?>',
		analytics: <?= Json::encode($analytics) ?>,
	}
	const AccessRights = new BX.UI.AccessRights.V2.App(AccessRightsOption)
	const ConfigPerms = new BX.Crm.ConfigPermsComponent({
		menuId: '<?=$arResult['menuId']?>',
		AccessRightsOption,
		AccessRights,
		hasLeftMenu: <?=$arResult['shouldDisplayLeftMenu'] ? 'true' : 'false' ?>,
	});

	ConfigPerms.init();
</script>

<?php
\Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages();

$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
	'HIDE' => true,
	'BUTTONS' => [
		[
			'TYPE' => 'save',
			'ONCLICK' => 'ConfigPerms.AccessRights.sendActionRequest()',
		],
		[
			'TYPE' => 'custom',
			'LAYOUT' => (new \Bitrix\UI\Buttons\Button())
				->setColor(\Bitrix\UI\Buttons\Color::LINK)
				->setText(\Bitrix\Main\Localization\Loc::getMessage('CRM_COMMON_CANCEL'))
				->bindEvent('click', new \Bitrix\UI\Buttons\JsCode('ConfigPerms.AccessRights.fireEventReset()'))
				->render()
			,
		],
	],
]);