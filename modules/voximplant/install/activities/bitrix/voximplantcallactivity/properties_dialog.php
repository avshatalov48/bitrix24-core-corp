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
<tr>
	<td align="right" width="40%" valign="top" colspan="2" style="color: red"><?=GetMessage('BPVICA_PD_NO_OUTPUT_NUMBER')?></td>
</tr>
<?else:?>
<tr>
	<td align="right"><span class="adm-required-field"><?= GetMessage("BPVICA_PD_OUTPUT_NUMBER") ?>:</span></td>
	<td>
		<select name="output_number">
			<? foreach($outputNumber as $number => $name):?>
			<option value="<?=$number?>"<?= $currentValues["output_number"] == $number ? " selected" : "" ?>><?= $name ?></option>
			<?endforeach;?>
		</select>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPVICA_PD_NUMBER") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("string", 'number', $currentValues['number'], array('size' => 50))?>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?=GetMessage('BPVICA_PD_CALL_TYPE')?>:</span></td>
	<td width="60%" valign="top">
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
		<select name="use_audio_file" onchange="__BPVICA_change(this.value)">
			<option value="N"<?= $currentValues['use_audio_file'] != 'Y' ? " selected" : "" ?>><?= GetMessage("BPVICA_PD_CALL_TYPE_TEXT") ?></option>
			<option value="Y"<?= $currentValues['use_audio_file'] == 'Y' ? " selected" : "" ?>><?= GetMessage("BPVICA_PD_CALL_TYPE_AUDIO") ?></option>
		</select>
	</td>
</tr>
<tr id="bpvica_text" <?if ($currentValues['use_audio_file'] == 'Y'):?> style="display: none" <?endif?>>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("BPVICA_PD_TEXT") ?>:</span></td>
	<td width="60%" valign="top">
		<?=CBPDocument::ShowParameterField("text", 'text', $currentValues['text'], Array('rows'=>'7'))?>
	</td>
</tr>
<tr id="bpvica_voice_language" <?if ($currentValues['use_audio_file'] == 'Y'):?> style="display: none" <?endif?>>
	<td align="right"><?= GetMessage("BPVICA_PD_VOICE_LANGUAGE") ?>:</td>
	<td>
		<select name="voice_language">
			<? foreach($voiceLanguage as $lang => $name):?>
				<option value="<?=$lang?>"<?= $currentValues["voice_language"] == $lang ? " selected" : "" ?>><?= $name ?></option>
			<?endforeach;?>
		</select>
	</td>
</tr>
<tr id="bpvica_voice_speed" <?if ($currentValues['use_audio_file'] == 'Y'):?> style="display: none" <?endif?>>
	<td align="right"><?= GetMessage("BPVICA_PD_VOICE_SPEED") ?>:</td>
	<td>
		<select name="voice_speed">
			<? foreach($voiceSpeed as $speed => $name):?>
				<option value="<?=$speed?>"<?= $currentValues["voice_speed"] == $speed ? " selected" : "" ?>><?= $name ?></option>
			<?endforeach;?>
		</select>
	</td>
</tr>
<tr id="bpvica_voice_volume" <?if ($currentValues['use_audio_file'] == 'Y'):?> style="display: none" <?endif?>>
	<td align="right"><?= GetMessage("BPVICA_PD_VOICE_VOLUME") ?>:</td>
	<td>
		<select name="voice_volume">
			<? foreach($voiceVolume as $volume => $name):?>
				<option value="<?=$volume?>"<?= $currentValues["voice_volume"] == $volume ? " selected" : "" ?>><?= $name ?></option>
			<?endforeach;?>
		</select>
	</td>
</tr>
<tr id="bpvica_audio_file" <?if ($currentValues['use_audio_file'] != 'Y'):?> style="display: none" <?endif?>>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("BPVICA_PD_AUDIO_FILE") ?>:</span></td>
	<td width="60%" valign="top">
		<?=CBPDocument::ShowParameterField("file", 'audio_file', $currentValues['audio_file'], array('size' => 45))?>
	</td>
</tr>
<tr>
	<td align="right"><?= GetMessage("BPVICA_PD_WAIT_FOR_RESULT") ?>:</td>
	<td>
		<select name="wait_for_result">
			<option value="Y"<?= $currentValues["wait_for_result"] == "Y" ? " selected" : "" ?>><?= GetMessage("BPVICA_PD_YES") ?></option>
			<option value="N"<?= $currentValues["wait_for_result"] != "Y" ? " selected" : "" ?>><?= GetMessage("BPVICA_PD_NO") ?></option>
		</select>
	</td>
</tr>
<?if (IsModuleInstalled('crm')):?>
<tr>
	<td align="right"><?= GetMessage("BPVICA_PD_USE_DOCUMENT_PHONE_NUMBER") ?>:</td>
	<td>
		<select name="use_document_phone_number">
			<option value="N"<?= $currentValues["use_document_phone_number"] != "Y" ? " selected" : "" ?>><?= GetMessage("BPVICA_PD_NO") ?></option>
			<option value="Y"<?= $currentValues["use_document_phone_number"] == "Y" ? " selected" : "" ?>><?= GetMessage("BPVICA_PD_YES") ?></option>
		</select>
	</td>
</tr>
<?endif;?>
<?endif?>