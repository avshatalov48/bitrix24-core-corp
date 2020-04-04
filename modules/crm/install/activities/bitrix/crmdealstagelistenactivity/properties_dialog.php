<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("crm"))
	return;
?>
<tr>
	<td align="right" width="40%"><?= GetMessage("BPCDSA_PD_DEAL") ?>:</td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("int", 'deal_id', $arCurrentValues['deal_id'], Array('size'=> 20))?>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><?= GetMessage("BPCDSA_PD_STAGE") ?>:</td>
	<td width="60%">
		<select name="stage[]" multiple="multiple" style="min-height: 200px">
			<?
			$selected = (array)$arCurrentValues["stage"];

			foreach(\Bitrix\Crm\Category\DealCategory::getStageGroupInfos() as $group)
			{
				$name = isset($group['name']) ? $group['name'] : \Bitrix\Crm\Category\DealCategory::getDefaultCategoryName();
				$items = isset($group['items']) && is_array($group['items']) ? $group['items'] : array();
				?>
				<optgroup label="<?=htmlspecialcharsbx($name)?>">
				<?
				foreach ($items as $stageId => $stageName)
				{
					$s = CCrmDeal::GetStageSemantics($stageId);
					if ($s != 'process')
						continue;
					?><option value="<?= htmlspecialcharsbx($stageId) ?>"<?= (in_array($stageId, $selected)) ? " selected" : "" ?>><?= htmlspecialcharsbx($stageName) ?></option><?
				}
				?>
				</optgroup>
				<?
			}
			?>
		</select>
		<div style="margin: 5px 0; color: grey"><?=GetMessage('BPCDSA_PD_STAGE_DESCR')?></div>
	</td>
</tr>
