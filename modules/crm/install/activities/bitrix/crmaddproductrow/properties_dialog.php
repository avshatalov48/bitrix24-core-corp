<?php

use Bitrix\Main;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Main\UI\Extension::load('ui.entity-selector');
\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmaddproductrow/script.js'));
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

foreach ($dialog->getMap() as $fieldId => $field): ?>
	<tr>
		<td align="right" width="40%"><?=htmlspecialcharsbx($field['Name'])?>:</td>
		<td width="60%">
			<?= $dialog->renderFieldControl($field, null, !empty($field['AllowSelection']), 0) ?>
		</td>
	</tr>
<?php endforeach;?>
<script>
	BX.ready(function()
	{
		var script = new BX.Crm.Activity.CrmAddProductRowActivity({
			formName: '<?= CUtil::JSEscape($dialog->getFormName()) ?>',
			productProperty: <?= Main\Web\Json::encode($dialog->getMap()['ProductId']) ?>
		});
		script.init();
	});
</script>
