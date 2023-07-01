<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die;
}

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmcompletetaskactivity/script.js'));

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */
?>

<?php foreach ($dialog->getMap() as $field): ?>
	<div class="crm-automation-popup-settings">
		<span class="crm-automation-popup-settings-title"><?= htmlspecialcharsbx($field['Name']) ?>: </span>
		<?= $dialog->renderFieldControl($field, $dialog->getCurrentValue($field)) ?>
	</div>
<?php endforeach; ?>
<script>
	BX.ready(function ()
	{
		const script = new BX.Crm.Automation.Activity.CompleteTaskActivity({
			formName: '<?= CUtil::JSEscape($dialog->getFormName()) ?>',
			stages: <?= \Bitrix\Main\Web\Json::encode($dialog->getRuntimeData()['stages'] ?? []) ?>,
			chosenStages: <?= \Bitrix\Main\Web\Json::encode($dialog->getCurrentValue('target_status')) ?>,
			isRobot: true,
		});

		script.init();
		script.render();
	});
</script>
