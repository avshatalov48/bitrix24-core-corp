<?php

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

$APPLICATION->SetTitle(Loc::getMessage('CRM_COMMON_PERMISSIONS_SETTINGS_ITEM'));


\Bitrix\Main\Loader::includeModule('ui');
\Bitrix\Main\UI\Extension::load([
	'ui.accessrights.v2',
]);

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . 'no-all-paddings no-background');

$searchContainerId = 'crm-config-perms-v2-search-container';
$this->SetViewTarget('inside_pagetitle', 100);
echo "<div id=\"${searchContainerId}\"></div>";
$this->EndViewTarget();

/** @var \Bitrix\Crm\Security\Role\UIAdapters\AccessRights\AccessRightsDTO $rolesData */
$rolesData = $arResult['accessRightsData'];
$controllerData = $arResult['controllerData'];
/** @var int|null $maxVisibleUserGroups */
$maxVisibleUserGroups = $arResult['maxVisibleUserGroups'];

?>

<div id='bx-crm-perms-config-permissions'></div>

<script>
	const userGroups = <?= Json::encode($rolesData->userGroups) ?>;
	const accessRights = <?= Json::encode($rolesData->accessRights) ?>;
	const additionalSaveParams = <?= Json::encode($controllerData) ?>;

	const AccessRights = new BX.UI.AccessRights.V2.App({
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
	});

	AccessRights.draw();
</script>

<?php
\Bitrix\Crm\Service\Container::getInstance()->getLocalization()->loadMessages();

$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
	'HIDE' => true,
	'BUTTONS' => [
		[
			'TYPE' => 'save',
			'ONCLICK' => 'AccessRights.sendActionRequest()',
		],
		[
			'TYPE' => 'custom',
			'LAYOUT' => (new \Bitrix\UI\Buttons\Button())
				->setColor(\Bitrix\UI\Buttons\Color::LINK)
				->setText(\Bitrix\Main\Localization\Loc::getMessage('CRM_COMMON_CANCEL'))
				->bindEvent('click', new \Bitrix\UI\Buttons\JsCode('AccessRights.fireEventReset()'))
				->render()
			,
		],
	],
]);
