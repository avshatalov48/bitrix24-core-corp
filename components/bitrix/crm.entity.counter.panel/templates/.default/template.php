<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @var array $arResult
 */

use Bitrix\Main\Page\Asset;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Extension::load(['ui.fonts.opensans', 'ui.counterpanel']);
Asset::getInstance()->addJs('/bitrix/js/crm/message.js');

$entityTypeId = (int)$arResult['ENTITY_TYPE_ID'];

if (isset($arResult['EXTRAS']['DEAL_CATEGORY_ID']))
{
	$categoryId = (int)$arResult['EXTRAS']['DEAL_CATEGORY_ID'];
}
elseif (isset($arResult['EXTRAS']['CATEGORY_ID']))
{
	$categoryId = (int)$arResult['EXTRAS']['CATEGORY_ID'];
}
else
{
	$categoryId = 0;
}

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
uasort($data, fn($x , $y) => $x['TYPE_ID'] <=> $y['TYPE_ID']);
?>

<div id="<?= $containerId ?>" class="crm-counter"></div>
<script>
	BX.ready(function() {
		BX.message(<?=CUtil::phpToJsObject(Loc::loadLanguageFile(__FILE__))?>);
		BX.message(<?= CUtil::PhpToJSObject($arResult['ENTITY_PLURALS']) ?>);

		// init counter panel
		(new BX.Crm.EntityCounterPanel({
			id: "<?= $containerId ?>",
			entityTypeId: <?= $entityTypeId ?>,
			userId: <?= (int)$arResult['USER_ID'] ?>,
			userName: "<?= CUtil::JSEscape($arResult['USER_NAME']) ?>",
			serviceUrl: "<?= '/bitrix/components/bitrix/crm.entity.counter.panel/ajax.php?'.bitrix_sessid_get() ?>",
			data: <?= CUtil::PhpToJSObject($data) ?>,
			codes: <?= CUtil::PhpToJSObject($arResult['CODES']) ?>,
			extras: <?= CUtil::PhpToJSObject($arResult['EXTRAS']) ?>,
			filterLastPresetId: "<?= $filterLastPresetId ?>",
			filterLastPresetData: <?= CUtil::PhpToJSObject($filterLastPreset) ?>
		})).init();
	});
</script>
