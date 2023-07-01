<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmcompletetaskactivity/script.js'));

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

foreach ($dialog->getMap() as $fieldId => $field):?>
	<tr>
		<td align="right" width="40%"><?= htmlspecialcharsbx($field['Name']) ?>:</td>
		<td width="60%">
			<?php
			$fieldType = $dialog->getFieldTypeObject($field);
			echo $fieldType->renderControl([
				'Form' => $dialog->getFormName(),
				'Field' => $field['FieldName']
			], $dialog->getCurrentValue($field['FieldName']), true, 0);
			?>
		</td>
	</tr>
<?php endforeach; ?>
<script>
	BX.ready(function ()
	{
		const script = new BX.Crm.Automation.Activity.CompleteTaskActivity({
			formName: '<?= CUtil::JSEscape($dialog->getFormName()) ?>',
			stages: <?= \Bitrix\Main\Web\Json::encode($dialog->getRuntimeData()['stages'] ?? []) ?>,
			chosenStages: <?= \Bitrix\Main\Web\Json::encode($dialog->getCurrentValue('target_status')) ?>,
			isRobot: false,
		});

		script.init();
		script.render();
	});
</script>
