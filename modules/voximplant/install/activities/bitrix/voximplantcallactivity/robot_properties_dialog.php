<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
/**
 * @var array $currentValues
 * @var array $outputNumber
 * @var array $voiceLanguage
 * @var array $voiceSpeed
 * @var array $voiceVolume
 */

if (empty($outputNumber)):?>
	<div class="crm-automation-popup-settings crm-automation-popup-settings-text" style="max-width: 660px">
		<?=GetMessage('BPVICA_RPD_NO_OUTPUT_NUMBER')?>
	</div>
<?else:?>
	<div class="crm-automation-popup-settings">
		<span class="crm-automation-popup-settings-title"><?= GetMessage("BPVICA_RPD_OUTPUT_NUMBER") ?>: </span>
		<select class="crm-automation-popup-settings-dropdown" name="output_number">
			<? foreach($outputNumber as $number => $name):?>
				<option value="<?=$number?>"<?= $currentValues["output_number"] == $number ? " selected" : "" ?>><?= $name ?></option>
			<?endforeach;?>
		</select>
	</div>
	<div class="crm-automation-popup-settings">
		<span class="crm-automation-popup-settings-title"><?= GetMessage("BPVICA_RPD_CALL_TYPE") ?>: </span>
		<select class="crm-automation-popup-settings-dropdown" name="use_audio_file" onchange="__BPVICA_change(this.value)">
			<option value="N"<?= $currentValues['use_audio_file'] != 'Y' ? " selected" : "" ?>><?= GetMessage("BPVICA_RPD_CALL_TYPE_TEXT") ?></option>
			<option value="Y"<?= $currentValues['use_audio_file'] == 'Y' ? " selected" : "" ?>><?= GetMessage("BPVICA_RPD_CALL_TYPE_AUDIO") ?></option>
		</select>
	</div>
	<div class="crm-automation-popup-settings" id="bpvica_text" <?if ($currentValues['use_audio_file'] == 'Y'):?> style="display: none" <?endif?>>
		<textarea name="text"
				  class="crm-automation-popup-textarea"
				  placeholder="<?=GetMessage("BPVICA_RPD_TEXT")?>"
				  data-role="inline-selector-target"
		><?=htmlspecialcharsbx($currentValues['text'])?></textarea>
	</div>
	<div class="crm-automation-popup-settings" id="bpvica_voice_language" <?if ($currentValues['use_audio_file'] == 'Y'):?> style="display: none" <?endif?>>
		<span class="crm-automation-popup-settings-title"><?= GetMessage("BPVICA_RPD_VOICE_LANGUAGE") ?>: </span>
		<select class="crm-automation-popup-settings-dropdown" name="voice_language">
			<? foreach($voiceLanguage as $lang => $name):?>
				<option value="<?=$lang?>"<?= $currentValues["voice_language"] == $lang ? " selected" : "" ?>><?= $name ?></option>
			<?endforeach;?>
		</select>
	</div>
	<div class="crm-automation-popup-settings" id="bpvica_voice_speed" <?if ($currentValues['use_audio_file'] == 'Y'):?> style="display: none" <?endif?>>
		<span class="crm-automation-popup-settings-title"><?= GetMessage("BPVICA_RPD_VOICE_SPEED") ?>: </span>
		<select class="crm-automation-popup-settings-dropdown" name="voice_speed">
			<? foreach($voiceSpeed as $speed => $name):?>
				<option value="<?=$speed?>"<?= $currentValues["voice_speed"] == $speed ? " selected" : "" ?>><?= $name ?></option>
			<?endforeach;?>
		</select>
	</div>
	<div class="crm-automation-popup-settings" id="bpvica_voice_volume" <?if ($currentValues['use_audio_file'] == 'Y'):?> style="display: none" <?endif?>>
		<span class="crm-automation-popup-settings-title"><?= GetMessage("BPVICA_RPD_VOICE_VOLUME") ?>: </span>
		<select class="crm-automation-popup-settings-dropdown" name="voice_volume">
			<? foreach($voiceVolume as $volume => $name):?>
				<option value="<?=$volume?>"<?= $currentValues["voice_volume"] == $volume ? " selected" : "" ?>><?= $name ?></option>
			<?endforeach;?>
		</select>
	</div>
	<div class="crm-automation-popup-settings" id="bpvica_audio_file" <?if ($currentValues['use_audio_file'] != 'Y'):?> style="display: none" <?endif?>>
		<span class="crm-automation-popup-settings-title crm-automation-popup-settings-title-autocomplete"><?= GetMessage("BPVICA_RPD_AUDIO_FILE") ?>: </span>
		<input name="audio_file" type="text" class="crm-automation-popup-input"
			   value="<?=htmlspecialcharsbx($currentValues['audio_file'])?>"
			   placeholder="https://"
			   data-role="inline-selector-target"
		>
	</div>
	<input type="hidden" name="wait_for_result" value="N">
	<input type="hidden" name="use_document_phone_number" value="Y">
	<script>
		function __BPVICA_change(v)
		{
			document.getElementById("bpvica_text").style.display = v == 'Y'? 'none' : '';
			document.getElementById("bpvica_voice_language").style.display = v == 'Y'? 'none' : '';
			document.getElementById("bpvica_voice_speed").style.display = v == 'Y'? 'none' : '';
			document.getElementById("bpvica_voice_volume").style.display = v == 'Y'? 'none' : '';
			document.getElementById("bpvica_audio_file").style.display = v != 'Y'? 'none' : '';
		}
	</script>
<?endif?>