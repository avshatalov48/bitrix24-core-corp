<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
\Bitrix\Main\Page\Asset::getInstance()->addCss(getLocalPath('activities/bitrix/crmgenerateqr/crmgenerateqr.css'));

/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */
?>

<div class="bizproc-automation-popup-settings bizproc-automation-popup-settings-text" style="max-width: 660px">
	<p><?= Loc::getMessage('CRMBPGQR_HELP_1') ?></p>
	<div class="bizproc-automation-popup-settings__qr">
		<div class="bizproc-automation-popup-settings__qr_img-block">
			<img class="bizproc-automation-popup-settings__qr_img" src="/pub/crm/qr/?test&img=y" alt="QR" />
		</div>
		<div class="bizproc-automation-popup-settings__qr_description">
			<div class="bizproc-automation-popup-settings__qr_text --weighty"><?= Loc::getMessage('CRMBPGQR_HELP_2') ?></div>
			<div class="bizproc-automation-popup-settings__qr_text --weighty"><?= Loc::getMessage('CRMBPGQR_HELP_3') ?></div>
			<div class="bizproc-automation-popup-settings__qr_text"><?= Loc::getMessage('CRMBPGQR_HELP_4') ?></div>
		</div>
	</div>
</div>

<style>
	.crm-bpgqr-additional-up,
	.crm-bpgqr-additional {
		border-bottom: 1px dashed #8f949c;
		color: #80868e;
		font: bold 12px "HelveticaNeue", Arial, Helvetica, sans-serif;
		-webkit-transition: border-bottom .3s ease-in-out;
		-moz-transition: border-bottom .3s ease-in-out;
		-ms-transition: border-bottom .3s ease-in-out;
		-o-transition: border-bottom .3s ease-in-out;
		transition: border-bottom .3s ease-in-out;
		cursor: pointer;
		-webkit-font-smoothing: antialiased;
	}
	.crm-bpgqr-additional-up:hover,
	.crm-bpgqr-additional:hover {
		border-bottom: 1px dashed #eef2f4;
		color: #adafb1;
	}
	.crm-bpgqr-additional {
		position: relative;
	}

	.crm-bpgqr-additional:after {
		content: "";
		position: absolute;
		bottom: 4px;
		right: -10px;
		border-style: solid;
		border-width: 4px 3.5px 0 3.5px;
		border-color: #535c69 transparent transparent transparent;
	}

	.crm-bpgqr-additional-up {
		position: relative;
	}

	.crm-bpgqr-additional-up:after {
		content: "";
		position: absolute;
		bottom: 4px;
		right: -10px;
		border-style: solid;
		border-width: 0 3.5px 4px 3.5px;
		border-color: transparent transparent #535c69 transparent;
	}
	.crm-bpgqr-additional-content {
		height: 0;
		min-height: 0;
		padding-top: 17px;
		transition: all .3ms ease;
		overflow: hidden;
	}
	.crm-bpgqr-additional-content-up {
		height: auto;
		min-height: 200px;
	}
</style>

<span class="crm-bpgqr-additional"
	  onclick="BX.toggleClass(this.nextElementSibling, 'crm-bpgqr-additional-content-up'); BX.toggleClass(this, 'crm-bpgqr-additional-up'); return false;">
	<?=GetMessage('CRMBPGQR_ADDITIONAL')?>
</span>
<div class="crm-bpgqr-additional-content">
	<?php
	foreach ($dialog->getMap() as $id => $field):?>
		<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-top bizproc-automation-popup-settings-title-autocomplete">
			<?= htmlspecialcharsbx($field['Name']) ?>:
		</span>
			<?= $dialog->renderFieldControl($field); ?>
		</div>
	<?php
	endforeach;
	?>
</div>
