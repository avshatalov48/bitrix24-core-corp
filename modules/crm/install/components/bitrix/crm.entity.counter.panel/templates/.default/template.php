<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @var array $arResult
 * @var object $APPLICATION
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
$isBitrix24Template = SITE_TEMPLATE_ID === 'bitrix24';

$isChangeViewTarget = !$returnAsHtml && $isBitrix24Template;

if ($isChangeViewTarget)
{
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'crm-pagetitle-view');

	$this->SetViewTarget('below_pagetitle', 1000);
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

<div id="<?= $containerId ?>" class="crm-counter"></div>
<script>
	BX.ready(function() {
		BX.message(<?=CUtil::phpToJsObject($phrases)?>);
		BX.message(<?= CUtil::PhpToJSObject($arResult['ENTITY_PLURALS']) ?>);

		// init counter panel
		(new BX.Crm.EntityCounterPanel({
			id: "<?= $containerId ?>",
			entityTypeId: <?= $entityTypeId ?>,
			entityTypeName: "<?= CUtil::JSEscape($entityTypeName ?? '') ?>",
			userId: <?= (int)$arResult['USER_ID'] ?>,
			userName: "<?= CUtil::JSEscape($arResult['USER_NAME']) ?>",
			data: <?= CUtil::PhpToJSObject($data) ?>,
			codes: <?= CUtil::PhpToJSObject($arResult['CODES']) ?>,
			extras: <?= CUtil::PhpToJSObject($arResult['EXTRAS']) ?>,
			withExcludeUsers: <?= $arResult['WITH_EXCLUDE_USERS'] ? 'true' : 'false' ?>,
			filterLastPresetId: "<?= $filterLastPresetId ?>",
			filterLastPresetData: <?= CUtil::PhpToJSObject($filterLastPreset) ?>,
			lockedCallback: <?= CUtil::PhpToJSObject($lockedCallback) ?>,
			filterResponsibleFiledName: "<?= CUtil::JSEscape($arResult['FILTER_RESPONSIBLE_FILED_NAME']) ?>"
		})).init();
	});
</script>
<?php
if ($isChangeViewTarget)
{
	$this->EndViewTarget();
}
