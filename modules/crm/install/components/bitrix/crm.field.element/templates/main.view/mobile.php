<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

/**
 * @var array $arResult
 */

$fieldName = HtmlFilter::encode($arResult['userField']['FIELD_NAME']);
?>
<select
	name="<?= $arResult['fieldName'] ?>"
	id="<?= $fieldName ?>_select"
	class="mobile-grid-data-select"
	<?= ($arResult['userField']['MULTIPLE'] === 'Y' ? ' multiple' : '') ?>
>
	<?php
	foreach($arResult['value'] as $value)
	{
		$value = HtmlFilter::encode($value);
		?>
		<option
			value="<?= $value ?>"
			selected="selected"
			data-category="<?= $arResult['ELEMENT'][$value]['type'] ?>"
		><?= $value ?></option>
		<?php
	}
	?>
</select>
<div>
	<?php
	$categoryTitles = [];
	foreach($arResult['value'] as $value)
	{
		list($prefix, $id) = explode('_', $value);
		if(!in_array($prefix, $categoryTitles, true))
		{
			$categoryTitles[] = $prefix;
			?>
			<span class="mobile-grid-data-span mobile-grid-crm-element-category-title">
				<?= Loc::getMessage(
					'CRM_ENTITY_TYPE_' . ElementType::getLongEntityType($prefix)
				) . ':' ?>
			</span>
			<?php
		}
		?>
		<span class="mobile-grid-data-span">
			<a
				href="<?= HtmlFilter::encode($arResult['ELEMENT'][ElementType::getLongEntityType($prefix)][$id]['ENTITY_LINK']) ?>"
				data-id="<?= HtmlFilter::encode($value) ?>"
			>
				<?= HtmlFilter::encode($arResult['ELEMENT'][ElementType::getLongEntityType($prefix)][$id]['ENTITY_TITLE']) ?>
			</a>
		</span>
		<?php
	}
	?>
</div>

<a
	class="mobile-grid-button mobile-grid-button-select"
	href="javascript:void(0)"
	id="<?= $fieldName ?>"
>
	<?= Loc::getMessage('CRM_ELEMENT_BUTTON_SELECT') ?>
</a>


<?php
$nodes = [$fieldName];
?>

<script>
	BX.ready(function ()
	{
		new BX.Mobile.Field.ElementCrm(
			<?=CUtil::PhpToJSObject([
				'name' => 'BX.Mobile.Field.ElementCrm',
				'nodes' => $nodes,
				'restrictedMode' => true,
				'formId' => $arParams['additionalParameters']['formId'],
				'gridId' => $arParams['additionalParameters']['gridId'],
				'useOnChangeEvent' => true,
				'availableTypes' => $arResult['userField']['SETTINGS']
			])?>
		);
	});
</script>