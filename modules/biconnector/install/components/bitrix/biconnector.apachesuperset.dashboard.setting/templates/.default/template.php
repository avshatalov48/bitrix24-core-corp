<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var string $templateFolder
 */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

Loader::includeModule('biconnector');
Loader::includeModule('ui');

if (!empty($arResult['ERROR_MESSAGES']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.info.error',
		'',
		[
			'TITLE' => $arResult['ERROR_MESSAGES'][0],
		]
	);

	if ($arResult['FEATURE_AVAILABLE'] === false)
	{
		echo '<script>top.BX.UI.InfoHelper.show("limit_crm_BI_analytics")</script>';
	}

	return;
}

Extension::load([
	'loc',
	'ui.buttons',
	'ui.icons',
	'ui.notification',
	'ui.entity-editor',
	'biconnector.entity-editor.field.settings-date-filter',
	'biconnector.apache-superset-analytics',
]);

$dashboardTitle = htmlspecialcharsbx($arResult['DASHBOARD_TITLE']);

\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();
$entityEditorControlFactory = 'BX.UI.EntityEditorControlFactory';
$entityEditorControllerFactory = 'BX.UI.EntityEditorControllerFactory';
$APPLICATION->SetTitle($arResult['TITLE']);
?>

<div id="biconnector-dashboard-settings__wrapper">
	<div class="biconnector-dashboard-settings-editor__wrapper ui-entity-section">
		<?php
		global $APPLICATION;
		$APPLICATION->IncludeComponent(
			"bitrix:ui.form",
			"",
			$arResult['FORM_PARAMETERS']
		);
		?>
	</div>
</div>


<script>
	BX.message(<?=Json::encode(Loc::loadLanguageFile(__FILE__))?>);
	BX.BIConnector.ApacheSuperset.Dashboard.Setting.registerFieldFactory('<?= \CUtil::JSEscape($entityEditorControlFactory) ?>');
	BX.BIConnector.ApacheSuperset.Dashboard.Setting.registerControllerFactory('<?= \CUtil::JSEscape($entityEditorControllerFactory) ?>');
</script>
