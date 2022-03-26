<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

foreach ($dialog->getMap() as $key => $property):?>
	<div id="field_<?=htmlspecialcharsbx($property['FieldName'])?>_container">
		<div class="bizproc-automation-popup-settings">
			<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
				<?=htmlspecialcharsbx($property['Name'])?>:
			</span>
			<?=$dialog->renderFieldControl($property)?>
		</div>
	</div>
<?php endforeach; ?>

<script>
	BX.ready(function() {
		var form = document.forms['<?= CUtil::JSEscape($dialog->getFormName()) ?>'];
		var orderIdContainer = document.getElementById('field_order_id_container');

		orderIdContainer.style.visibility = form.autocreate.value === 'Y' ? 'hidden' : 'visible';
		form.autocreate.onchange = function(event)
		{
			orderIdContainer.style.visibility = event.srcElement.value === 'Y' ? 'hidden' : 'visible';
		};
	});
</script>
