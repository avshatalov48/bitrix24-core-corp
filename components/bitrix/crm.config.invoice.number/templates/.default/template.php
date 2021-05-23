<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<table class="bx-edit-table " cellspacing="0" cellpadding="0" border="0">
	<tr class=" bx-top">
		<td class="bx-field-name bx-padding">
			<?=GetMessage("CRM_ACCOUNT_NUMBER_TEMPL")?>
		</td>
		<td class="bx-field-value">
			<select name="account_number_template" onChange="showAccountNumberAdditionalFields(this.selectedIndex)">
				<?foreach($arResult['NUM_TEMPLATES'] as $template => $templateName):?>
					<option value="<?=$template?>"<?=($arResult['ACC_NUM_TMPL'] == $template ? ' selected' : '')?>><?=$templateName?></option>
				<?endforeach;?>
			</select>
		</td>
	</tr>
	<tr id="account_template_1" <?=($arResult['ACC_NUM_TMPL'] == "NUMBER") ? "" : "style=\"display:none\""?>>
		<td class="bx-field-name bx-padding">
			<?=GetMessage("CRM_ACCOUNT_NUMBER_NUMBER")?>
		</td>
		<td  class="bx-field-value">
			<input type="text" name="account_number_number" size="7" maxlength="7" value="<?=($arResult['ACC_NUM_TMPL'] == 'NUMBER' ? $arResult['ACC_NUM_DATA'] : '')?>"/><br/><br/><?=GetMessage("CRM_ACCOUNT_NUMBER_NUMBER_DESC")?>
		</td>
	</tr>
	<tr id="account_template_2" <?=($arResult['ACC_NUM_TMPL'] == "PREFIX") ? "" : "style=\"display:none\""?>>
		<td class="bx-field-name bx-padding">
			<?=GetMessage("CRM_ACCOUNT_NUMBER_PREFIX")?>
		</td>
		<td  class="bx-field-value">
			<input type="text" name="account_number_prefix" size="10" maxlength="7" value="<?=($arResult['ACC_NUM_TMPL'] == 'PREFIX' ? $arResult['ACC_NUM_DATA'] : '')?>" /><br/><br/>
			<?=GetMessage("CRM_ACCOUNT_NUMBER_PREFIX_DESC")?>
		</td>
	</tr>
	<tr id="account_template_3" <?=($arResult['ACC_NUM_TMPL'] == "RANDOM") ? "" : "style=\"display:none\""?>>
		<td class="bx-field-name bx-padding">
			<?=GetMessage("CRM_ACCOUNT_NUMBER_RANDOM")?>
		</td>
		<td  class="bx-field-value">
			<select name="account_number_random_length">
				<?for($i = 5; $i < 11; $i++):?>
					<option value="<?=$i?>"<?=($arResult['ACC_NUM_DATA'] == $i) ? "selected" : "" ?>><?=$i?></option>
				<?endfor;?>
			</select>
			<br/><br/>
			<?=GetMessage("CRM_ACCOUNT_NUMBER_TEMPLATE_EXAMPLE")?>&nbsp;6B7R1, 8CB2A59X8X
		</td>
	</tr>
	<tr id="account_template_4" <?=($arResult['ACC_NUM_TMPL'] == "USER") ? "" : "style=\"display:none\""?>>
		<td class="bx-field-name bx-padding">
			&nbsp;
		</td>
		<td>
			<?=GetMessage("CRM_ACCOUNT_NUMBER_TEMPLATE_EXAMPLE")?>&nbsp;1_12, 16749_2
		</td>
	</tr>
	<tr id="account_template_5" <?=($arResult['ACC_NUM_TMPL'] == "DATE") ? "" : "style=\"display:none\""?>>
		<td class="bx-field-name bx-padding">
			<?=GetMessage("CRM_ACCOUNT_NUMBER_DATE")?>
		</td>
		<td  class="bx-field-value">
			<select name="account_number_date_period">
				<option value="day" <?=($arResult['ACC_NUM_DATA'] == "day") ? "selected" : "" ?>><?=GetMessage("CRM_ACCOUNT_NUMBER_DATE_1")?></option>
				<option value="month" <?=($arResult['ACC_NUM_DATA'] == "month") ? "selected" : "" ?>><?=GetMessage("CRM_ACCOUNT_NUMBER_DATE_2")?></option>
				<option value="year" <?=($arResult['ACC_NUM_DATA'] == "year") ? "selected" : "" ?>><?=GetMessage("CRM_ACCOUNT_NUMBER_DATE_3")?></option>
			</select>
			<br/><br/>
			<?=GetMessage("CRM_ACCOUNT_NUMBER_TEMPLATE_EXAMPLE")?>&nbsp;23042013&nbsp;/&nbsp;5, 042013&nbsp;/&nbsp;4, 2013&nbsp;/&nbsp;17645
		</td>
	</tr>
</table>

<script type="text/javascript">
	function showAccountNumberAdditionalFields(templateID)
	{
		for (var i = 1; i < 6; i++)
			BX("account_template_" + i).style.display = 'none';

		if (templateID != 0)
			BX("account_template_" + templateID).style.display = 'table-row';
	}
</script>