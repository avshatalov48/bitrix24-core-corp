<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>

<table id="tblLIST-<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>-<?=CUtil::JSEscape($arResult['TYPE_ID'])?>" class="crm_fm" cellspacing="0">
<?foreach($arResult['VALUES'] as $arValue):?>
<tr>
	<td class="crm_fm_td_value">
		<input type="text" size="35" name="<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>][<?=$arValue['ID']?>][VALUE]" id="field-<?=$arStatus['ID']?>" value="<?=htmlspecialcharsbx($arValue['VALUE'])?>" class="value-input">
	</td>
	<td class="crm_fm_td_select">
		<?=SelectBoxFromArray(CUtil::JSEscape($arResult['FM_MNEMONIC']).'['.htmlspecialcharsbx($arResult['TYPE_ID']).']['.$arValue['ID'].'][VALUE_TYPE]', $arResult['TYPE_BOX'], $arValue['VALUE_TYPE'])?>
	</td>
	<td class="crm_fm_td_delete"><div class="delete-action" onclick="CrmFMdeleteItem(this, '<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>-<?=CUtil::JSEscape($arResult['TYPE_ID'])?>', /<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>\[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>\]\[(n)([0-9]*)\]/g, 2);" title="<?=GetMessage('CRM_STATUS_LIST_DELETE')?>"></div></td>
</tr>
<?endforeach;?>
<?if (empty($arResult['VALUES'])):?>
<tr>
	<td class="crm_fm_td_value">
		<input type="text" size="35" name="<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>][n1][VALUE]" id="field-<?=$arStatus['ID']?>" value="<?=$arStatus['NAME']?>" class="value-input">
	</td>
	<td class="crm_fm_td_select">
		<?=SelectBoxFromArray(CUtil::JSEscape($arResult['FM_MNEMONIC']).'['.htmlspecialcharsbx($arResult['TYPE_ID']).'][n1][VALUE_TYPE]', $arResult['TYPE_BOX'])?>
	</td>
	<td class="crm_fm_td_delete"><div class="delete-action" onclick="CrmFMdeleteItem(this, '<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>-<?=CUtil::JSEscape($arResult['TYPE_ID'])?>', /<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>\[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>\]\[(n)([0-9]*)\]/g, 2);" title="<?=GetMessage('CRM_STATUS_LIST_DELETE')?>"></div></td>
</tr>
	<?if ($arResult['TYPE_ID'] == 'WEB'):?>
	<tr>
		<td class="crm_fm_td_value">
			<input type="text" size="35" name="<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>][n2][VALUE]" id="field-<?=$arStatus['ID']?>" value="<?=$arStatus['NAME']?>" class="value-input">
		</td>
		<td class="crm_fm_td_select">
			<?=SelectBoxFromArray(CUtil::JSEscape($arResult['FM_MNEMONIC']).'['.htmlspecialcharsbx($arResult['TYPE_ID']).'][n2][VALUE_TYPE]', $arResult['TYPE_BOX'], 'FACEBOOK')?>
		</td>
		<td class="crm_fm_td_delete"><div class="delete-action" onclick="CrmFMdeleteItem(this, '<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>-<?=CUtil::JSEscape($arResult['TYPE_ID'])?>', /<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>\[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>\]\[(n)([0-9]*)\]/g, 2);" title="<?=GetMessage('CRM_STATUS_LIST_DELETE')?>"></div></td>
	</tr>
	<tr>
		<td class="crm_fm_td_value">
			<input type="text" size="35" name="<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>][n3][VALUE]" id="field-<?=$arStatus['ID']?>" value="<?=$arStatus['NAME']?>" class="value-input">
		</td>
		<td class="crm_fm_td_select">
			<?=SelectBoxFromArray(CUtil::JSEscape($arResult['FM_MNEMONIC']).'['.htmlspecialcharsbx($arResult['TYPE_ID']).'][n3][VALUE_TYPE]', $arResult['TYPE_BOX'], 'TWITTER')?>
		</td>
		<td class="crm_fm_td_delete"><div class="delete-action" onclick="CrmFMdeleteItem(this, '<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>-<?=CUtil::JSEscape($arResult['TYPE_ID'])?>', /<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>\[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>\]\[(n)([0-9]*)\]/g, 2);" title="<?=GetMessage('CRM_STATUS_LIST_DELETE')?>"></div></td>
	</tr>
	<?endif;?>
<?endif;?>
</table>
<a href="#add" class="status-field-add" onclick="CrmFMaddNewTableRow('<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>-<?=CUtil::JSEscape($arResult['TYPE_ID'])?>', /<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>\[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>\]\[(n)([0-9]*)\]/g, 2)"><?=GetMessage('CRM_STATUS_LIST_ADD')?></a>
<table width="300" id="tblSAMPLE-<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>-<?=CUtil::JSEscape($arResult['TYPE_ID'])?>" style="display:none">
<tr>
	<td class="crm_fm_td_value">
		<input type="text" size="35" name="<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>][n0][VALUE]" id="field-<?=$arStatus['ID']?>" value="<?=$arStatus['NAME']?>" class="value-input">
	</td>
	<td class="crm_fm_td_select">
		<?=SelectBoxFromArray(CUtil::JSEscape($arResult['FM_MNEMONIC']).'['.htmlspecialcharsbx($arResult['TYPE_ID']).'][n0][VALUE_TYPE]', $arResult['TYPE_BOX'])?>
	</td>
	<td class="crm_fm_td_delete"><div class="delete-action" onclick="CrmFMdeleteItem(this, '<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>-<?=CUtil::JSEscape($arResult['TYPE_ID'])?>', /<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>\[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>\]\[(n)([0-9]*)\]/g, 2);" title="<?=GetMessage('CRM_STATUS_LIST_DELETE')?>"></div></td>
</tr>
</table>
<script type="text/javascript">
	var cnt_new = 0;
	if(typeof(CrmFMaddNewTableRow) === "undefined")
	{
		function CrmFMaddNewTableRow(tableID, regexp, rindex)
		{
			var tbl = document.getElementById('tblLIST-'+tableID);
			var tblS = document.getElementById('tblSAMPLE-'+tableID);
			var cnt = tbl.rows.length;
			var oRow = tbl.insertRow(cnt);
			var col_count = tbl.rows[cnt-1].cells.length;
			cnt_new = cnt_new>0? cnt_new+1: tbl.rows.length;


			for(var i=0;i<col_count;i++)
			{
				var oCell = oRow.insertCell(i);
				oCell.className = tblS.rows[0].cells[i].className;
				var html = tblS.rows[0].cells[i].innerHTML;
				oCell.innerHTML = html.replace(regexp,
					function(html)
					{
						return html.replace('[n'+arguments[rindex]+']', '[n'+cnt_new+']');
					}
				);
			}
		}
	}

	if(typeof(CrmFMdeleteItem) === "undefined")
	{
		function CrmFMdeleteItem(button, tableID, regexp, rindex)
		{
			var tableRow = BX.findParent(button, {'tag':'tr'});
			var tableRowCount = BX.findChildren(tableRow.parentNode, {'tag':'tr'}, true);

			if(tableRow && tableRowCount.length <= 1)
			{
				CrmFMaddNewTableRow(tableID, regexp, rindex);
			}

			var hidden = BX.findChild(tableRow, {'tag':'input','class':'value-input'}, true);
			if(hidden)
			{
				var table = tableRow.parentNode;
				hidden.style.display = 'none';
				hidden.value = '';
				table.parentNode.appendChild(hidden);
				table.removeChild(tableRow);
			}
		}
	}
</script>
