<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load('ui.alerts');

\Bitrix\Main\Page\Asset::getInstance()->addJs(
	getLocalPath('activities/bitrix/crmchangedynamiccategoryactivity/script.js')
);
/**
 * @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog
 * @var \Bitrix\Main\Error[] $errors
 */
$errors = $dialog->getRuntimeData()['errors'] ?? [];
?>
<?php if ($errors): ?>
	<?php foreach ($errors as $error): ?>
		<tr>
			<td colspan="2">
				<div class="ui-alert ui-alert-danger">
					<span class="ui-alert-message"><?= htmlspecialcharsbx($error->getMessage()) ?></span>
				</div>
			</td>
		</tr>
	<?php endforeach; ?>
<?php else: ?>
	<?php foreach ($dialog->getMap() as $field):?>
		<div class="bizproc-automation-popup-settings">
			<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
				<?=htmlspecialcharsbx($field['Name'])?>:
			</span>
			<?=$dialog->renderFieldControl($field, $dialog->getCurrentValue($field))?>
		</div>
	<?php endforeach;?>

	<script>
		BX.ready(function()
		{
			var script = new BX.Crm.Activity.CrmChangeDynamicCategoryActivity({
				formName: '<?=CUtil::JSEscape($dialog->getFormName())?>'
			});
			script.init();
		})
	</script>
<?php endif; ?>