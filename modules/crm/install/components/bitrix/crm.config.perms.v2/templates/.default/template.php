<?php

use Bitrix\Crm\Security\Role\UIAdapters\AccessRights;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die();
}
/** @var array $arResult */

global $APPLICATION;

\Bitrix\Main\Loader::includeModule('ui');
\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'ui.icons',
	'ui.notification',
	'ui.accessrights',
	'crm.perms.access-rights-wrapper',
]);

$rolesData = AccessRights\Queries\QueryRoles::getInstance()->execute();
?>

<div style="background: #eef2f4 !important; padding: 10px;">
	<div id="bx-crm-perms-config-permissions"></div>
</div>

	<script>
		const userGroups = <?= \Bitrix\Main\Web\Json::encode($rolesData->userGroups) ?>;
		const accessRights = <?= \Bitrix\Main\Web\Json::encode($rolesData->accessRights); ?>;

		const accessRightsWrapper = new BX.Crm.Perms.AccessRightsWrapper();
		accessRightsWrapper.draw(
			userGroups,
			accessRights,
			document.getElementById('bx-crm-perms-config-permissions')
		);
	</script>

<?php
$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
	'HIDE' => true,
	'BUTTONS' => [
		[
			'TYPE' => 'save',
			'ONCLICK' => 'accessRightsWrapper.sendActionRequest()',
		],
		[
			'TYPE' => 'cancel',
			'ONCLICK' => 'accessRightsWrapper.fireEventReset()'
		],
	],
]);
?>