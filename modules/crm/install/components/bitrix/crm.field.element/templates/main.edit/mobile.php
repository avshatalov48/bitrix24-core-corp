<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Crm\UserField\Types\ElementType;

/**
 * @var ElementCrmUfComponent $component
 * @var array $arResult
 * @var array $arParams
 */
$publicMode = (isset($arParams['PUBLIC_MODE']) && $arParams['PUBLIC_MODE'] === true);
$fieldName = HtmlFilter::encode($arResult['userField']['FIELD_NAME']);
?>
<select
	name="<?= $arResult['fieldName'] ?>"
	id="<?= $fieldName ?>_select"
	class="mobile-grid-data-select"
	<?= ($arResult['userField']['MULTIPLE'] === 'Y' ? ' multiple' : '') ?>
>
	<?php
	foreach($arResult['valueCodes'] as $value)
	{
		$value = HtmlFilter::encode($value);
		list($prefix, $id) = explode('_', $value);
		?>
		<option
			value="<?= $value ?>"
			selected="selected"
			data-category="<?= mb_strtolower(ElementType::getLongEntityType($prefix)) ?>"
		><?= $value ?></option>
		<?php
	}
	?>
</select>

<div>
	<?php
	foreach($arResult['value'] as $entityType => $arEntity)
	{
		if (empty($arEntity['items']))
		{
			continue;
		}

		if($arParams['PREFIX'])
		{
			?>
			<span class="mobile-grid-data-span mobile-grid-crm-element-category-title">
				<?= $arEntity['title'] ?>:
			</span>
			<?php
		}

		foreach($arEntity['items'] as $entityId => $entity)
		{
			?>
			<span class="mobile-grid-data-span">
				<?php
				if($publicMode)
				{
					print HtmlFilter::encode($entity['ENTITY_TITLE']);
				}
				else
				{
					// @todo remove after support dynamic entity in mobile
					$typeId = \CCrmOwnerType::ResolveID($entityType);
					if(!\CCrmOwnerType::isUseDynamicTypeBasedApproach($typeId))
					{
						?>
						<a
							href="<?= $entity['ENTITY_LINK'] ?>"
							data-id="<?= ($entity['ENTITY_TYPE_ID_WITH_ENTITY_ID'] ?? $entityId) ?>"
						>
							<?= HtmlFilter::encode($entity['ENTITY_TITLE']) ?>
						</a>
						<?php
					}
					else
					{
						print HtmlFilter::encode($entity['ENTITY_TITLE']);
					}
				}
				?>
			</span>
			<?php
		}
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
	<?php
	$nodes = [$fieldName];
	$messages = [];
	foreach ($arResult['value'] as $entityTypeName => $entityType)
	{
		$messages['CRM_ENTITY_TYPE_'.$entityTypeName] = $entityType['title'];
	}
	?>

	BX.message(<?= CUtil::phpToJSObject($messages) ?>);

	BX.ready(function ()
	{
		new BX.Mobile.Field.ElementCrm(
			<?=CUtil::PhpToJSObject([
				'name' => 'BX.Mobile.Field.ElementCrm',
				'nodes' => $nodes,
				'restrictedMode' => true,
				'formId' => $arParams['additionalParameters']['formId'],
				'gridId' => $arParams['additionalParameters']['gridId'],
				'useOnChangeEvent' => false,
				'availableTypes' => $arResult['availableTypes']
			])?>
		);
	});
</script>
