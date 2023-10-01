<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("crm"))
	return;
?>
<tr>
	<td align="right" width="40%"><?= GetMessage("BPCLSLA_PD_LEAD") ?>:</td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("int", 'lead_id', $arCurrentValues['lead_id'], Array('size'=> 20))?>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><?= GetMessage("BPCLSLA_PD_STATUS_MSGVER_1") ?>:</td>
	<td width="60%">
		<select name="status[]" multiple="multiple">
			<?
			$selected = (array)$arCurrentValues["status"];
			foreach (\CCrmStatus::GetStatusList('STATUS') as $statusId => $statusName)
			{
				$s = CCrmLead::GetStatusSemantics($statusId);
				if ($s != 'process')
					continue;
				?><option value="<?= htmlspecialcharsbx($statusId) ?>"<?= (in_array($statusId, $selected)) ? " selected" : "" ?>><?= htmlspecialcharsbx($statusName) ?></option><?
			}
			?>
		</select>
		<div style="margin: 5px 0; color: grey"><?=GetMessage('BPCLSLA_PD_STATUS_DESCR_MSGVER_1')?></div>
	</td>
</tr>
