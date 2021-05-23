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
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title"><?=htmlspecialcharsbx($actions['Name'])?>: </span>
	<?=$dialog->renderFieldControl($actions, $selected)?>
</div>
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($map['Observers']['Name'])?>:
	</span>
	<?=$dialog->renderFieldControl($map['Observers'], $observers)?>
</div>