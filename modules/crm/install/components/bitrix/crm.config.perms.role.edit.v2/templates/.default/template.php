<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\UI\Extension;

/** @var array $arResult */

Extension::load([
	'ui.design-tokens',
	'crm.perms.edit'
]);

?>

<div id="crm-config-perms-role-edit-v2"></div>

<script>
	BX.ready(function () {

		const appData = <?=CUtil::PhpToJSObject($arResult['APP_DATA'])?>

		const permissionEditApp = (new BX.Crm.Perms.EditApp({containerId: 'crm-config-perms-role-edit-v2'}));
		permissionEditApp.start(appData);
	});
</script>
