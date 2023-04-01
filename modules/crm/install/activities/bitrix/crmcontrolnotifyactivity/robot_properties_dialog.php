<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$messageText = $map['MessageText'];

$toUsers = $dialog->getCurrentValue($map['ToUsers']);
if ($dialog->getCurrentValue($map['ToHead']['FieldName']) !== 'N')
{
	if (!is_array($toUsers))
	{
		$toUsers = (array) $toUsers;
	}

	$toUsers[] = 'responsible_head';
}
?>
<div class="bizproc-automation-popup-settings">
	<?= $dialog->renderFieldControl($messageText)?>
</div>
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($map['ToUsers']['Name'])?>:
	</span>
	<?=$dialog->renderFieldControl($map['ToUsers'], $toUsers)?>
</div>