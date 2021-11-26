<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmchangerelationsactivity/script.js'));
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */
?>
<?php foreach ($dialog->getMap() as $field): ?>
	<?php if (array_key_exists('Name', $field)): ?>
		<tr>
			<td align="right" width="40%"><?=htmlspecialcharsbx($field['Name'])?>:</td>
			<td width="60%">
				<?=
				$dialog->getFieldTypeObject($field)->renderControl(
					[
						'Form' => $dialog->getFormName(),
						'Field' => $field['FieldName']
					],
					$dialog->getCurrentValue($field['FieldName']),
					true,
					0
				)
				?>
			</td>
		</tr>
	<?php endif; ?>
<?php endforeach; ?>

<script>
	BX.ready(function()
	{
		var script = new BX.Crm.Activity.CrmChangeRelationsActivity({
			formName: '<?=CUtil::JSEscape($dialog->getFormName())?>',
		});
		script.init();
	})
</script>
