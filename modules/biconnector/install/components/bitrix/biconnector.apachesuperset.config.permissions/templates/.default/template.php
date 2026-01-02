<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\UI\Buttons;

/**
 * @var array $arResult
 * @var array $arParams
 * @var CMain $APPLICATION
 */

Bitrix\Main\UI\Extension::load([
	'ui.accessrights.v2',
	'ui.buttons',
	'ui.icon-set.api.vue',
	'ui.icon-set.actions',
	'ui.icons.disk',
	'biconnector.apache-superset-analytics',
	'biconnector.dashboard-group',
]);


$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . 'no-all-paddings no-background');

Loc::loadMessages(__FILE__);

\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();
?>

<div id="bx-biconnector-role-main"></div>
<div id="bx-biconnector-role-main2"></div>
<?php

$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
	'HIDE' => true,
	'BUTTONS' => [
		[
			'TYPE' => 'save',
			'ONCLICK' => 'accessRightsApp.sendActionRequest().catch(() => {})',
		],
		[
			'TYPE' => 'custom',
			'LAYOUT' => (new Buttons\Button())
				->setColor(Buttons\Color::LINK)
				->setText(Loc::getMessage('BI_ACCESS_RIGHTS_BUTTON_CANCEL'))
				->bindEvent('click', new Buttons\JsCode('accessRightsApp.fireEventReset()'))
				->render()
			,
		],
	],
]);

?>

<script>
	const accessRightsApp = new BX.UI.AccessRights.V2.App({
		renderTo: document.getElementById('bx-biconnector-role-main'),
		userGroups: <?= Json::encode($arResult['USER_GROUPS']) ?>,
		accessRights: <?= Json::encode($arResult['ACCESS_RIGHTS']) ?>,
		component: 'bitrix:biconnector.apachesuperset.config.permissions',
		actionSave: 'savePermissions',
		actionDelete: 'deleteRole',
		analytics: {},
		searchContainerSelector: '#uiToolbarContainer',
		isSaveAccessRightsList: true,
	});

	accessRightsApp.draw();

	BX.ready(function() {
		const searchContainer = document.querySelector('.ui-access-rights-v2-search');
		if (searchContainer)
		{
			BX.removeClass(searchContainer, 'ui-ctl-w100');
			BX.addClass(searchContainer, 'ui-ctl-w50');
		}

		BX.message(<?= Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
		BX.BIConnector.Permissions.init({
			newGroupPermissions: <?= Json::encode($arResult['NEW_GROUP_PERMISSIONS']) ?>,
			dashboardGroups: <?= Json::encode($arResult['DASHBOARD_GROUPS']) ?>,
			dashboards: <?= Json::encode($arResult['DASHBOARDS']) ?>,
			user: <?= Json::encode($arResult['USER_DATA']) ?>,
			appGuid: accessRightsApp.getGuid(),
		});
	});
</script>
