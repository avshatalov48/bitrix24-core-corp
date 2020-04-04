<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

if(!empty($arParams["FILTER"])):

$this->SetViewTarget("sidebar", 100);
?>

<div class="sidebar-block">
	<b class="r2"></b><b class="r1"></b><b class="r0"></b>
	<div class="sidebar-block-inner">
		<form name="filter_<?=$arParams["GRID_ID"]?>" action="" method="GET">
<?
foreach($arResult["GET_VARS"] as $var=>$value):
	if(is_array($value)):
		foreach($value as $k=>$v):
			if(is_array($v))
				continue;
?>
		<input type="hidden" name="<?=htmlspecialcharsbx($var)?>[<?=htmlspecialcharsbx($k)?>]" value="<?=htmlspecialcharsbx($v)?>">
<?
		endforeach;
	else:
?>
		<input type="hidden" name="<?=htmlspecialcharsbx($var)?>" value="<?=htmlspecialcharsbx($value)?>">
<?
	endif;
endforeach;
?>
		<div class="filter-block-title">
			<?echo GetMessage("interface_grid_search")?>
			<a href="javascript:void(0)" onclick="bxGrid_<?=$arParams["GRID_ID"]?>.menu.ShowMenu(this, bxGrid_<?=$arParams["GRID_ID"]?>.filterMenu);" class="filter-settings" title="<?echo GetMessage("interface_grid_additional")?>"></a>
<?if($USER->IsAuthorized() && !empty($arResult["FILTER"])):?>
			<a href="javascript:void(0)" class="filter-save" title="<?echo GetMessage("main_interface_filter_save_title")?>" onclick="bxGrid_<?=$arParams["GRID_ID"]?>.AddFilterAs()"><?echo GetMessage("main_interface_filter_save")?></a>
<?endif?>
		</div>
		<div class="filter-block">
<?
foreach($arParams["FILTER"] as $field):
	$bShow = $arResult["FILTER_ROWS"][$field["id"]];
	$field_id = "flt_field_".$arParams["GRID_ID"]."_".$field["id"];
?>
			<div class="filter-field" id="flt_row_<?=$arParams["GRID_ID"]?>_<?=$field["id"]?>"<?if(!$bShow) echo ' style="display:none"'?>>
<?if($field["type"] <> "checkbox"):?>
				<label class="filter-field-title" for="<?=$field_id?>"><?=$field["name"]?></label>
<?endif?>
<?
	//default attributes
	if(!is_array($field["params"]))
		$field["params"] = array();
	if($field["type"] == '' || $field["type"] == 'text')
	{
		if($field["params"]["size"] == '')
			$field["params"]["size"] = "30";
	}
	elseif($field["type"] == 'date')
	{
		if($field["params"]["size"] == '')
			$field["params"]["size"] = "10";
	}
	elseif($field["type"] == 'number')
	{
		if($field["params"]["size"] == '')
			$field["params"]["size"] = "8";
	}
	
	$params = '';
	foreach($field["params"] as $p=>$v)
		$params .= ' '.$p.'="'.$v.'"';

	$value = $arResult["FILTER"][$field["id"]];

	switch($field["type"]):
		case 'custom':
			echo $field["value"];
			break;
		case 'checkbox':
?>
				<input type="hidden" name="<?=$field["id"]?>" value="N" />
				<input class="filter-checkbox" type="checkbox" id="<?=$field_id?>" name="<?=$field["id"]?>" value="Y"<?=($value == "Y"? ' checked="checked"':'')?><?=$params?> />&nbsp;<label for="<?=$field_id?>"><?=$field["name"]?></label><br />
<?
			break;
		case 'list':
			$bMulti = isset($field["params"]["multiple"]);
?>
				<select id="<?=$field_id?>" name="<?=$field["id"].($bMulti? '[]':'')?>" class="<?=($bMulti? "filter-listbox":"filter-dropdown")?>"<?=$params?>>
<?
			if(is_array($field["items"])):
				if(!is_array($value))
					$value = array($value);
				$bSel = false;
				if($bMulti):
?>
					<option value=""<?=($value[0] == ''? ' selected="selected"':'')?>><?echo GetMessage("interface_grid_no_no_no")?></option>
<?
				endif;
				foreach($field["items"] as $k=>$v):
?>
					<option value="<?=htmlspecialcharsbx($k)?>"<?if(in_array($k, $value) && (!$bSel || $bMulti)) {$bSel = true; echo ' selected="selected"';}?>><?=htmlspecialcharsbx($v)?></option>
<?
				endforeach;
?>
				</select>
<?
			endif;
			break;
		case 'date':
			$APPLICATION->IncludeComponent(
				"bitrix:main.calendar.interval",
				"",
				array(
					"FORM_NAME" => "filter_".$arParams["GRID_ID"],
					"SELECT_NAME" => $field["id"]."_datesel",
					"SELECT_VALUE" => $arResult["FILTER"][$field["id"]."_datesel"],
					"INPUT_NAME_DAYS" => $field["id"]."_days",
					"INPUT_VALUE_DAYS" => $arResult["FILTER"][$field["id"]."_days"],
					"INPUT_NAME_FROM" => $field["id"]."_from",
					"INPUT_VALUE_FROM" => $arResult["FILTER"][$field["id"]."_from"],
					"INPUT_NAME_TO" => $field["id"]."_to",
					"INPUT_VALUE_TO" => $arResult["FILTER"][$field["id"]."_to"],
					"INPUT_PARAMS" => $params,
				),
				$component,
				array("HIDE_ICONS"=>true)
			);
			?>
			<script type="text/javascript">
				BX.ready(function(){BX.InterfaceGridFilterSidebar.initializeCalendarInterval(document.forms['filter_<?=$arParams["GRID_ID"]?>'].<?=$field["id"]?>_datesel)});
			</script>
			<?
			break;
			break;
		case 'quick':
?>
				<input class="filter-quick-textbox" id="<?=$field_id?>" type="text" name="<?=$field["id"]?>" value="<?=htmlspecialcharsbx($value)?>"<?=$params?> />
<?
			if(is_array($field["items"])):
?>
				<select name="<?=$field["id"]?>_list" class="filter-quick-dropdown">
<?foreach($field["items"] as $key=>$item):?>
					<option value="<?=htmlspecialcharsbx($key)?>"<?=($arResult["FILTER"][$field["id"]."_list"] == $key? ' selected':'')?>><?=htmlspecialcharsbx($item)?></option>
<?endforeach?>
				</select>
<?
			endif;
			break;
		case 'number':
?>
				<input class="filter-interval" type="text" name="<?=$field["id"]?>_from" id="<?=$field_id?>" value="<?=htmlspecialcharsbx($arResult["FILTER"][$field["id"]."_from"])?>"<?=$params?> /><span class="filter-interval-hellip">&hellip;</span><input class="filter-interval" type="text" name="<?=$field["id"]?>_to" value="<?=htmlspecialcharsbx($arResult["FILTER"][$field["id"]."_to"])?>"<?=$params?> />
<?
			break;
		default:
?>
				<input class="filter-textbox" id="<?=$field_id?>" type="text" name="<?=$field["id"]?>" value="<?=htmlspecialcharsbx($value)?>"<?=$params?> />
<?
			break;
	endswitch;
?>
			</div>
<?endforeach?>

			<div class="filter-field-buttons">
				<input type="submit" name="filter" class="filter-submit" value="<?echo GetMessage("interface_grid_find")?>" title="<?echo GetMessage("interface_grid_find_title")?>">&nbsp;&nbsp;<input type="button" name="" class="filter-submit" value="<?echo GetMessage("interface_grid_flt_cancel")?>" title="<?echo GetMessage("interface_grid_flt_cancel_title")?>" onclick="bxGrid_<?=$arParams["GRID_ID"]?>.ClearFilter(this.form)">
				<input type="hidden" name="clear_filter" value="">
			</div>
		</div>
<?if(is_array($arResult["OPTIONS"]["filters"]) && !empty($arResult["OPTIONS"]["filters"])):?>
		<div class="filter-presets">
			<label><a href="javascript:void(0)" onclick="bxGrid_<?=$arParams["GRID_ID"]?>.ShowFilters()"><?echo GetMessage("main_interface_filter_saved")?></a></label>
			<ul>
<?foreach($arResult["OPTIONS"]["filters"] as $filter_id=>$filter):?>
				<li><a href="javascript:void(0)" title="<?echo GetMessage("main_interface_filter_saved_apply")?>" onclick="bxGrid_<?=$arParams["GRID_ID"]?>.ApplyFilter('<?=CUtil::JSEscape($filter_id)?>')"><?=htmlspecialcharsbx($filter["name"])?></a></li>
<?endforeach;?>
			</ul>
		</div>
<?endif;?>
		</form>
	</div>
	<i class="r0"></i><i class="r1"></i><i class="r2"></i>
</div>
<?
$this->EndViewTarget();
?>
<?if(!empty($arResult["FILTER"])):?>
<div class="bx-filter-note">
	<?echo GetMessage("interface_filter_note")?> <a href="javascript:void(0)" title="<?echo GetMessage("interface_grid_flt_cancel_title")?>" onclick="bxGrid_<?=$arParams["GRID_ID"]?>.ClearFilter(document.forms['filter_<?=$arParams["GRID_ID"]?>'])"><?echo GetMessage("interface_filter_note_clear")?></a>
</div>
<?endif;?>
<?
endif;
?>