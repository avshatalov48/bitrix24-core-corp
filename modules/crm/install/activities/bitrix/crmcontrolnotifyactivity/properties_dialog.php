<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("CRM_CTRNA_PD_MESSAGE") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("text", 'message_text', $arCurrentValues['message_text'], Array('rows'=> 7))?>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("CRM_CTRNA_PD_TO_HEAD") ?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("bool", 'to_head', $arCurrentValues['to_head'])?>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><?= GetMessage("CRM_CTRNA_PD_TO_USERS") ?>:</td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("user", 'to_users', $arCurrentValues['to_users'], array('rows'=> 2))?>
	</td>
</tr>