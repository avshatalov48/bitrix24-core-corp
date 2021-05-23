<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */
$map = $dialog->getMap();
?>
<input type="hidden" name="event_type" value="INFO">
<div class="bizproc-automation-popup-settings">
	<?= $dialog->renderFieldControl($map['EventText'])?>
</div>
<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($map['EventUser']['Name'])?>:
	</span>
	<?=$dialog->renderFieldControl($map['EventUser'])?>
</div>