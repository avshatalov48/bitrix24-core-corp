<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$actions = $map['ActionOnObservers'];
$selected = $dialog->getCurrentValue($actions['FieldName']);

$observers = $dialog->getCurrentValue($map['Observers']);
?>
<div class="crm-automation-popup-settings">
	<span class="crm-automation-popup-settings-title"><?=htmlspecialcharsbx($actions['Name'])?>: </span>
	<?=$dialog->renderFieldControl($actions, $selected)?>
</div>
<div class="crm-automation-popup-settings">
	<span class="crm-automation-popup-settings-title crm-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($map['Observers']['Name'])?>:
	</span>
	<?=$dialog->renderFieldControl($map['Observers'], $observers)?>
</div>