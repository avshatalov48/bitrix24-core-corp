<?php

use Bitrix\Main;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Page\Asset::getInstance()->addJs(getLocalPath('activities/bitrix/crmaddproductrow/script.js'));
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

foreach ($dialog->getMap() as $propertyKey => $property):?>
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
			<?= htmlspecialcharsbx($property['Name']) ?>:
		</span>
		<?= $dialog->renderFieldControl($property, null, !empty($property['AllowSelection'])) ?>
	</div>
<?php endforeach; ?>
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
