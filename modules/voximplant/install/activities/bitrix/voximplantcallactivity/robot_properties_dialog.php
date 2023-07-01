<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();

/**
 * @var array $currentValues
 * @var array $outputNumber
 * @var array $voiceLanguage
 * @var array $voiceSpeed
 * @var array $voiceVolume
 * @var bool $isEnableText
 */

if (empty($outputNumber)):?>
	<div class="bizproc-automation-popup-settings bizproc-automation-popup-settings-text" style="max-width: 660px">
		<?=GetMessage('BPVICA_RPD_NO_OUTPUT_NUMBER')?>
	</div>
<?else:?>
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title"><?= GetMessage("BPVICA_RPD_OUTPUT_NUMBER") ?>: </span>
		<select class="bizproc-automation-popup-settings-dropdown" name="output_number">
			<? foreach($outputNumber as $number => $name):?>
				<option value="<?=$number?>"<?= $currentValues["output_number"] == $number ? " selected" : "" ?>><?= $name ?></option>
			<?endforeach;?>
		</select>
	</div>
	<div class="bizproc-automation-popup-settings">
		<span class="bizproc-automation-popup-settings-title"><?= GetMessage("BPVICA_RPD_CALL_TYPE") ?>: </span>
		<select class="bizproc-automation-popup-settings-dropdown" name="use_audio_file" onchange="__BPVICA_change(this.value)">
			<?php if ($isEnableText): ?>
				<option value="N"<?= $currentValues['use_audio_file'] != 'Y' ? " selected" : "" ?>><?= GetMessage("BPVICA_RPD_CALL_TYPE_TEXT") ?></option>
			<?php endif; ?>
			<option value="Y"<?= $currentValues['use_audio_file'] == 'Y' ? " selected" : "" ?>><?= GetMessage("BPVICA_RPD_CALL_TYPE_AUDIO") ?></option>
		</select>
	</div>

	<div id="bpvica_text-alert" class="ui-alert ui-alert-warning ui-alert-icon-danger" <?if ($currentValues['use_audio_file'] == 'Y'):?> style="display: none" <?endif?>>
		<div class="ui-alert-message"><?= Bitrix\Voximplant\Tts\Disclaimer::getHtml() ?></div>
	</div>

	<div class="bizproc-automation-popup-settings" id="bpvica_text" <?if ($currentValues['use_audio_file'] == 'Y'):?> style="display: none" <?endif?>>
		<?= $dialog->renderFieldControl($map['Text'])?>
	</div>
	<div class="bizproc-automation-popup-settings" id="bpvica_voice_language" <?if ($currentValues['use_audio_file'] == 'Y'):?> style="display: none" <?endif?>>
		<span class="bizproc-automation-popup-settings-title"><?= GetMessage("BPVICA_RPD_VOICE_LANGUAGE") ?>: </span>
		<select class="bizproc-automation-popup-settings-dropdown" name="voice_language">
			<? foreach($voiceLanguage as $lang => $name):?>
				<option value="<?=$lang?>"<?= $currentValues["voice_language"] == $lang ? " selected" : "" ?>><?= $name ?></option>
			<?endforeach;?>
		</select>
	</div>
	<div class="bizproc-automation-popup-settings" id="bpvica_voice_speed" <?if ($currentValues['use_audio_file'] == 'Y'):?> style="display: none" <?endif?>>
		<span class="bizproc-automation-popup-settings-title"><?= GetMessage("BPVICA_RPD_VOICE_SPEED") ?>: </span>
		<select class="bizproc-automation-popup-settings-dropdown" name="voice_speed">
			<? foreach($voiceSpeed as $speed => $name):?>
				<option value="<?=$speed?>"<?= $currentValues["voice_speed"] == $speed ? " selected" : "" ?>><?= $name ?></option>
			<?endforeach;?>
		</select>
	</div>
	<div class="bizproc-automation-popup-settings" id="bpvica_voice_volume" <?if ($currentValues['use_audio_file'] == 'Y'):?> style="display: none" <?endif?>>
		<span class="bizproc-automation-popup-settings-title"><?= GetMessage("BPVICA_RPD_VOICE_VOLUME") ?>: </span>
		<select class="bizproc-automation-popup-settings-dropdown" name="voice_volume">
			<? foreach($voiceVolume as $volume => $name):?>
				<option value="<?=$volume?>"<?= $currentValues["voice_volume"] == $volume ? " selected" : "" ?>><?= $name ?></option>
			<?endforeach;?>
		</select>
	</div>
	<div class="bizproc-automation-popup-settings" id="bpvica_audio_file" <?if ($currentValues['use_audio_file'] != 'Y'):?> style="display: none" <?endif?>>
		<span class="bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete"><?= GetMessage("BPVICA_RPD_AUDIO_FILE") ?>: </span>
		<?= $dialog->renderFieldControl($map['AudioFile'])?>
	</div>
	<input type="hidden" name="wait_for_result" value="N">
	<input type="hidden" name="use_document_phone_number" value="Y">
	<script>
		function __BPVICA_change(v)
		{
			document.getElementById("bpvica_text").style.display = v == 'Y'? 'none' : '';
			document.getElementById("bpvica_text-alert").style.display = v == 'Y'? 'none' : '';
			document.getElementById("bpvica_voice_language").style.display = v == 'Y'? 'none' : '';
			document.getElementById("bpvica_voice_speed").style.display = v == 'Y'? 'none' : '';
			document.getElementById("bpvica_voice_volume").style.display = v == 'Y'? 'none' : '';
			document.getElementById("bpvica_audio_file").style.display = v != 'Y'? 'none' : '';
		}
	</script>
<?endif?>