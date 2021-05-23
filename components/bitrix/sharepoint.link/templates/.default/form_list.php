<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die(); 

__IncludeLang($_SERVER['DOCUMENT_ROOT'].$this->GetFolder().'/lang/'.LANGUAGE_ID.'/template.php');

if (!isset($arResult['SERVICE']['SYNC_PERIOD']))
	$arResult['SERVICE']['SYNC_PERIOD'] = 86400;

$arValue = array();
if ($arResult['SERVICE']['FIELDS'])
{
	foreach ($arResult['SERVICE']['FIELDS'] as $field)
	{
		$arValue[$field['SP_FIELD']] = $field['FIELD_ID'];
	}
}
else
{
	$arValue = array(
		'Title' => 'NAME',
		'Author' => 'CREATED_BY',
		'Created' => 'DATE_CREATE',
		'Modified' => 'TIMESTAMP_X',
		'Editor' => 'MODIFIED_BY',
	);
}
?>

<?
$arIntervals = array(600, 1800, 3600, 10800, 28800, 86400, 0);
?>
<input type="hidden" name="step" value="4" />
<style type="text/css">
#sp_fields tbody tr, #sp_fields label {
	cursor: pointer;
}

</style>
<div class="bx-sp-list">
	<table class="bx-width100">
		<tbody>
			<tr>
				<td class="bx-popup-label bx-width30" valign="top"><?=GetMessage('SL_FORM_LIST_INTERVAL')?>: </td>
				<td><small>
<?
foreach ($arIntervals as $int):
?>
					<input type="radio" name="sp_interval" value="<?=$int?>" id="sp_interval_<?=$int?>"<?=$int == $arResult['SERVICE']['SYNC_PERIOD'] ? ' checked="checked"' : '';?> />&nbsp;<label for="sp_interval_<?=$int?>"><?=GetMessage('SL_FORM_LIST_INTERVAL_'.$int)?></label><br />
<?
endforeach;
?>
				</small></td>
			</tr>
			<tr>
				<td class="bx-popup-label bx-width30" valign="top"><?=GetMessage('SL_FORM_LIST_PRIORITY')?>: </td>
				<td><small>
					<input type="radio" name="sp_priority" value="B" id="sp_priority_B"<?=$arResult['SERVICE']['PRIORITY'] == 'B' ? ' checked="checked"' : '';?> />&nbsp;<label for="sp_priority_B"><?=GetMessage('SL_FORM_LIST_PRIORITY_B')?></label><br />
					<input type="radio" name="sp_priority" value="S" id="sp_priority_S"<?=$arResult['SERVICE']['PRIORITY'] == 'S' ? ' checked="checked"' : '';?> />&nbsp;<label for="sp_priority_S"><?=GetMessage('SL_FORM_LIST_PRIORITY_S')?></label><br />
				</small></td>
			</tr>
			<tr class="section">
				<td colspan="2"><?=GetMessage('SL_FORM_LIST_FIELDS')?></td>
			</tr>
			<tr>
				<td colspan="2"><table class="bx-width100" id="sp_fields"><tbody>
<?
$arFields = array();
foreach ($arResult['LIST']['FIELDS'] as $field)
{
	$arFields[$field['Name']] = array($field['DisplayName'], $field['Name'].':'.$field['Type']);
}

$arTypes = CIntranetSharepoint::GetTypes($arParams['IBLOCK_ID']);
foreach ($arValue as $key => $value):
?>
					<tr onclick="SLeditField(this)" name="<?=htmlspecialcharsbx($arFields[$key][1].'|'.$value)?>">
						<td><?=htmlspecialcharsex($arFields[$key][0])?> (<?=htmlspecialcharsex($key)?>)</td>
						<td><?=htmlspecialcharsex($arTypes[$value])?></td>
					</tr>
<?
endforeach;
?>
					<tr id="sp_select_control" colspan="2" style="display: none;">
						<td valign="top" class="bx-width50"><select name="sp_field" style="width: 100%;">
<?
foreach ($arResult['LIST']['FIELDS'] as $field):
?>
								<option value="<?=$field['Name'].':'.$field['Type']?>"><?=htmlspecialcharsex($field['DisplayName']).' ('.htmlspecialcharsex($field['Name']).')'?></option>
<?
endforeach;
?>
						</select></td>
						<td valign="top" class="bx-width50">
							<span id="bx_field_select"><?
echo CIntranetSharepoint::GetTypesHTML($arParams['IBLOCK_ID'], 'bx_field');
						?></span>
							<span id="bx_field_select_create" style="display: none;"><?
echo CIntranetSharepoint::GetTypesCreateHTML('bx_field_create');
							?></span>
							<input type="checkbox" id="bx_field_create_new" onclick="SLswitchSelectors(this.checked)" />&nbsp;<label for="bx_field_create_new"><?=GetMessage('SL_FORM_LIST_FIELD_CREATE')?></label>
						</td>
					</tr>
				</tbody><tfoot>
					<tr>
						<td colspan="2"><button onclick="SLadd(); return false;"><?=GetMessage('SL_FORM_LIST_ADD')?></button></td>
					</tr>
				</tfoot></table></td>
			</tr>
		</tbody>
	</table>

</div>
<script type="text/javascript">
BX.WindowManager.Get().SetTitle('<?=CUtil::JSEscape(GetMessage('SL_FORM_LIST_STEP_TITLE'))?>');
var editRow = null, editTable = null, selSP = null, selField = null, createField = null, checkField = null;

BX.ready(function() {
	
	editRow = BX('sp_select_control'); editTable = BX('sp_fields').tBodies[0];
	var a = BX.findChildren(editRow, {tag: 'SELECT'}, true);
	selSP = a[0]; selField = a[1]; createField = a[2];
	
	checkField = BX.findChild(editRow, {tag: 'INPUT'}, true);
	
	selSP.bx_index = 0; selField.bx_index = createField.bx_index = 1;
	selSP.onchange = selField.onchange = createField.onchange = _SLselonchange;
});

function SLswitchSelectors(value)
{
	selField.parentNode.style.display = value ? 'none' : 'inline';
	createField.parentNode.style.display = value ? 'inline' : 'none';
	
	_SLselonchange.apply(value ? createField : selField);
}

function SLeditField(row)
{
	if (null != editRow._bxrow)
		editRow._bxrow.style.display = '';
	
	editTable.removeChild(editRow);
	editTable.insertBefore(editRow, row);
	editRow._bxrow = row;
	
	editRow.style.display = '';
	
	row.style.display = 'none';

	var val = row.getAttribute('name').split('|');
	
	_SLsetValue(selSP, val[0]); 
	_SLselonchange.apply(selSP);
	if (_SLsetValue(selField, val[1]))
	{
		_SLselonchange.apply(selField);
		checkField.checked = false;
	}
	else
	{
		_SLsetValue(createField, val[1]);
		_SLselonchange.apply(createField);
		checkField.checked = true;
	}
	
	SLswitchSelectors(checkField.checked);
}

function _SLselonchange()
{
	_SLsaveValue(this.value, this.options[this.selectedIndex].text, this.bx_index);
}

function _SLsetValue(sel, val)
{
	for (var i = 0, len = sel.options.length; i < len; i++)
	{
		if (val == sel.options[i].value)
		{
			sel.selectedIndex = i;
			return true;
		}
	}
	
	return false;
}

function _SLsaveValue(value, text, index)
{
	if (editRow._bxrow)
	{
		var val = editRow._bxrow.getAttribute('name').split('|');
		val[index] = value
		editRow._bxrow.setAttribute('name', val[0] + '|' + val[1]);
		editRow._bxrow.cells[index ? 1 : 0].innerHTML = text;
	}
}

function SLadd()
{
	var row = (editTable || BX('sp_fields').tBodies[0]).insertRow(-1);
	row.setAttribute('name', '0|0');
	row.onclick = function() {SLeditField(this)};
	
	row.insertCell(-1); row.insertCell(-1);
	
	SLeditField(row);
}

</script>

<?
$arInternalDataTypes = array('Lookup', 'Computed', 'WorkSpaceLink');
$arInternalFields = array(
	'_HasCopyDestinations', 
	'_CopySource', 
	'_IsCurrentVersion'
);
?>