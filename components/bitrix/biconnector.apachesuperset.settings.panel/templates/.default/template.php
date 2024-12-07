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


Extension::load([
	'ui.buttons',
	'ui.icons',
	'ui.countdown',
	'ui.notification',
	'ui.entity-editor',
	'biconnector.apache-superset-cleaner',
	'biconnector.apache-superset-analytics',
	'biconnector.dashboard-parameters-selector',
]);


$entityEditorControlFactory = 'BX.UI.EntityEditorControlFactory';
$entityEditorControllerFactory = 'BX.UI.EntityEditorControllerFactory';
?>

<div id="biconnector-superset-settings-panel__wrapper">
	<div class="biconnector-superset-settings-panel-editor__wrapper ui-entity-section">
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
	BX.BIConnector.ApacheSuperset.SettingsPanel.registerFieldFactory('<?= \CUtil::JSEscape($entityEditorControlFactory) ?>');
	BX.BIConnector.ApacheSuperset.SettingsPanel.registerControllerFactory('<?= \CUtil::JSEscape($entityEditorControllerFactory) ?>');
</script>
