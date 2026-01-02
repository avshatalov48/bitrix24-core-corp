<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @var array $arResult
 * @var array $arParams
 * @global \CMain $APPLICATION
 */

use Bitrix\Crm\Component\EntityList\ActivityFieldRestrictionManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;

Extension::load(['ui.fonts.opensans', 'ui.counterpanel']);
Asset::getInstance()->addJs('/bitrix/js/crm/message.js');

$entityTypeId = (int)$arResult['ENTITY_TYPE_ID'];
$entityTypeName = $arResult['ENTITY_TYPE_NAME'] ?? $arParams['ENTITY_TYPE_NAME'];
$categoryId = (int)$arResult['CATEGORY_ID'];

$prefix = mb_strtolower($arResult['GUID']);
$containerId = htmlspecialcharsbx("{$prefix}_container");
$filterLastPresetId = htmlspecialcharsbx(
	sprintf(
		'crm-counter-filter-last-preset-%d-%d',
		$entityTypeId,
		$categoryId
	)
);
$filterLastPreset = CUserOptions::getOption('crm', $filterLastPresetId);

$data = $arResult['DATA'] ?? [];
$returnAsHtml = $arParams['RETURN_AS_HTML_MODE'] ?? false;

if (!$returnAsHtml)
{
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'crm-pagetitle-view');
}

$phrases = Loc::loadLanguageFile(__FILE__);
$phrases['NEW_CRM_COUNTER_TYPE_CURRENT'] = $phrases['NEW_CRM_COUNTER_TYPE_CURRENT2'];
unset($phrases['NEW_CRM_COUNTER_TYPE_CURRENT2']);

$lockedCallback = '';
$activityFieldRestrictionManager = new ActivityFieldRestrictionManager();
if ($activityFieldRestrictionManager->hasRestrictions())
{
	$lockedCallback = $activityFieldRestrictionManager->getJsCallback();
}

?>

<div id="<?= $containerId ?>" class="ui-actions-bar__panel"></div>
<script>
	(function () {
		BX.message(<?= \Bitrix\Main\Web\Json::encode($phrases) ?>);
		BX.message(<?= \Bitrix\Main\Web\Json::encode($arResult['ENTITY_PLURALS']) ?>);

		const panel = new BX.Crm.EntityCounterPanel({
			id: "<?= $containerId ?>",
			entityTypeId: <?= $entityTypeId ?>,
			entityTypeName: "<?= CUtil::JSEscape($entityTypeName ?? '') ?>",
			userId: <?= (int)$arResult['USER_ID'] ?>,
			userName: "<?= CUtil::JSEscape($arResult['USER_NAME']) ?>",
			data: <?= \Bitrix\Main\Web\Json::encode($data) ?>,
			codes: <?= \Bitrix\Main\Web\Json::encode($arResult['CODES']) ?>,
			extras: <?= \Bitrix\Main\Web\Json::encode($arResult['EXTRAS']) ?>,
			withExcludeUsers: <?= $arResult['WITH_EXCLUDE_USERS'] ? 'true' : 'false' ?>,
			filterLastPresetId: "<?= $filterLastPresetId ?>",
			filterLastPresetData: <?= \Bitrix\Main\Web\Json::encode($filterLastPreset) ?>,
			lockedCallback: <?= \Bitrix\Main\Web\Json::encode($lockedCallback) ?>,
			filterResponsibleFiledName: "<?= CUtil::JSEscape($arResult['FILTER_RESPONSIBLE_FILED_NAME']) ?>"
		});

		// render right away
		panel.init();

		// bind filter and events when the filter is fully ready
		BX.ready(() => {
			panel.bindToFilter();
			panel.bindEvents();
		});
	})();
</script>
