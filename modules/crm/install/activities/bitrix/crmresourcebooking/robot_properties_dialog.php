<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
?>

<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-top bizproc-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($map['ResourceField']['Name'])?>:
	</span>
	<?
	echo $dialog->renderFieldControl($map['ResourceField']);
	?>
</div>

<div class="bizproc-automation-popup-settings">
	<?=$dialog->renderFieldControl($map['ResourceName'])?>
</div>

<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-top bizproc-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($map['ResourceStart']['Name'])?>:
	</span>
	<?
	echo $dialog->renderFieldControl($map['ResourceStart']);
	?>
</div>

<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-top bizproc-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($map['ResourceDuration']['Name'])?>:
	</span>
	<?
	echo $dialog->renderFieldControl($map['ResourceDuration']);
	?>
</div>

<div class="bizproc-automation-popup-settings">
	<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-top bizproc-automation-popup-settings-title-autocomplete">
		<?=htmlspecialcharsbx($map['ResourceUsers']['Name'])?>:
	</span>
	<?
	echo $dialog->renderFieldControl($map['ResourceUsers']);
	?>
</div>