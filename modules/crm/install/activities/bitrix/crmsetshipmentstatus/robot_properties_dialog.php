<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

foreach ($dialog->getMap() as $field):
?>
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($field['Name'])?>:
	</span>
	<?=$dialog->renderFieldControl($field)?>
</div>
<?php endforeach;?>
<div class="bizproc-automation-popup-settings bizproc-automation-popup-settings-text" style="max-width: 660px">
	<?=GetMessage('CRM_SSS_RPD_DESCR')?>
</div>