<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$responsible = $map['Responsible'];
$items = $map['Items'];

$document = $dialog->getDocumentType();
$defaultItems = $document[1] == 'CCrmDocumentLead' ? array(\CCrmOwnerType::DealName,\CCrmOwnerType::ContactName) : null;

$selectedItems = (array)$dialog->getCurrentValue($items['FieldName'], $defaultItems);

$dealCategoryId = $map['DealCategoryId'] ?? null;
$selectedDealCategory = $dealCategoryId ? $dialog->getCurrentValue($dealCategoryId['FieldName'], 0) : 0;

$disableActivityCompletion = isset($map['DisableActivityCompletion']) ? $map['DisableActivityCompletion'] : null;
$disableActivityCompletionValue = $disableActivityCompletion ? $dialog->getCurrentValue($disableActivityCompletion['FieldName']) : 'N';
?>
<div class="crm-automation-popup-settings">
	<span class="crm-automation-popup-settings-title"><?=htmlspecialcharsbx($responsible['Name'])?>: </span>
	<?=$dialog->renderFieldControl($responsible, $dialog->getCurrentValue($responsible['FieldName']))?>
</div>
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title"><?=htmlspecialcharsbx($items['Name'])?>: </span>
	<div style="display:inline-block; vertical-align: top">
		<?foreach ($items['Options'] as $value => $optionLabel):?>
		<div class="bizproc-automation-popup-checkbox-item">
			<label class="bizproc-automation-popup-chk-label">
				<input type="checkbox"
					name="<?=htmlspecialcharsbx($items['FieldName'])?>[]"
					data-role="crm-cvtd-item-<?=htmlspecialcharsbx(mb_strtolower($value))?>"
					value="<?=htmlspecialcharsbx($value)?>"
					class="bizproc-automation-popup-chk"<?=(in_array($value, $selectedItems)) ? 'checked' : ''?>
				>
				<?=htmlspecialcharsbx($optionLabel)?>
			</label>
		</div>
		<?endforeach;?>
	</div>
</div>
<?php if ($dealCategoryId):?>
<div class="bizproc-automation-popup-settings" data-role="crm-cvtd-deal-category">
	<span class="bizproc-automation-popup-settings-title"><?=htmlspecialcharsbx($dealCategoryId['Name'])?>: </span>
	<?= $dialog->renderFieldControl($dealCategoryId) ?>
</div>
<?php endif?>
<?if ($disableActivityCompletion):?>
<div class="bizproc-automation-popup-settings">
	<div class="bizproc-automation-popup-checkbox">
		<div class="bizproc-automation-popup-checkbox-item">
			<label class="bizproc-automation-popup-chk-label">
				<input type="hidden" name="<?=htmlspecialcharsbx($disableActivityCompletion['FieldName'])?>" value="N">
				<input type="checkbox" name="<?=htmlspecialcharsbx($disableActivityCompletion['FieldName'])?>" value="Y" class="bizproc-automation-popup-chk" <?=$disableActivityCompletionValue === 'Y' ? 'checked' : ''?>>
				<?=htmlspecialcharsbx($disableActivityCompletion['Name'])?>
			</label>
		</div>
	</div>
</div>
<?endif;?>
<script>
	BX.ready(function()
	{
		var dealItem = document.querySelector('[data-role="crm-cvtd-item-deal"]');
		var dealCategory = document.querySelector('[data-role="crm-cvtd-deal-category"]');
		if (!dealCategory)
			return;

		dealCategory.style.display = dealItem && dealItem.checked ? '' : 'none';
		BX.bind(dealItem, 'change', function()
		{
			dealCategory.style.display = this.checked ? '' : 'none';
		});
	});
</script>
