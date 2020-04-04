<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
?>

<?if($arParams["SHOW_FORM_TAG"]):?>
<form name="form_<?=$arParams["GRID_ID"]?>" action="<?=POST_FORM_ACTION_URI?>" method="POST">

<?=bitrix_sessid_post();?>
<?endif?>

<div class="" id="<?=$arParams["GRID_ID"]?>">
	<div class="bx-disk-interface-tile">
<?
$jsActions = array();
if(!empty($arParams["ROWS"])):

foreach($arParams["ROWS"] as $index=>$aRow):

	$jsActions[$index] = array();
	$sDefAction = '';
	$sDefTitle = '';
	$shareInfoAction = array();
	if(is_array($aRow["actions"]))
	{
		$jsActions[$index] = $aRow["actions"];

		//find default action
		foreach($aRow["actions"] as $action)
		{
			if($action["DEFAULT"] == true)
			{
				$sDefAction = $action["ONCLICK"];
				$sDefTitle = $action["TEXT"];

				break;
			}
		}
	}
	if(!empty($aRow['tileActions']))
	{
		$shareInfoAction = !empty($aRow['tileActions']['SHARE_INFO'])? $aRow['tileActions']['SHARE_INFO'] : array();
	}
	$nameObject = substr($aRow['data']['NAME'], 0, 37);
	if(strlen($nameObject) === 37)
	{
		$nameObject .= '...';
	}
?>
		<div class="bx-disk-file-container" onclick="bxGrid_<?=$arParams["GRID_ID"]?>.clickAndSelectRow(<?= $aRow["data"]["ID"] ?>, event);" oncontextmenu="return bxGrid_<?=$arParams["GRID_ID"]?>.oActions[<?=$index?>]"<?if($sDefAction <> ''):?> ondblclick="<?=htmlspecialcharsbx($sDefAction)?>" title="<?=GetMessage("interface_grid_dblclick")?><?=$sDefTitle?>"<?endif?>>
			<div class="draggable bx-disk-file-block" data-object-id="<?= (!empty($aRow["id"])? $aRow["id"] : $aRow["data"]["ID"]) ?>">
				<?
					if(!empty($aRow['data']['IS_IMAGE']))
					{
						echo "<div class=\"bx-file-icon-container-small {$aRow['data']['ICON_CLASS']}\" style=\"background: url('{$aRow['data']['SRC_IMAGE']}')\">{$aRow['data']['LOCK_NODE']}</div>";
					}
					elseif(!empty($aRow['data']['SRC_PREVIEW']))
					{
						echo "<div class=\"bx-file-icon-container-small {$aRow['data']['ICON_CLASS']}\" style=\"background: url('{$aRow['data']['SRC_PREVIEW']}');\">{$aRow['data']['LOCK_NODE']}";
							echo "<div class=\"bx-file-icon-label\"></div>";
						echo "</div>";
					}
					else
					{
						echo "<div class=\"bx-file-icon-container-small {$aRow['data']['ICON_CLASS']} \">{$aRow['data']['LOCK_NODE']}</div>";
					}
				?>
				<?if($arResult["ALLOW_EDIT"]):?>
					<?
					if(!(isset($aRow["editable"]) && $aRow["editable"] === false)):
						$data_id = (!empty($aRow["id"])? $aRow["id"] : $aRow["data"]["ID"]);
					?>
						<div class="bx-disk-checkbox-col bx-disk-checkbox-container">
							<input type="checkbox" name="ID[]" id="ID_<?=$data_id?>" value="<?=$data_id?>" title="<?echo GetMessage("interface_grid_check")?>">
							<div class="input-fantom"><div class="input-fantom-checked"></div></div>
							<label for="ID_<?=$data_id?>" class="bx-disk-checkbox-label"></label>
						</div>
					<?endif?>
				<?endif?>

				<a class="bx-disk-folder-title" href="<?= $aRow['data']['OPEN_URL']?>" <?= $aRow['data']['VIEWER_ATTRS'] ?>"><?= htmlspecialcharsbx($nameObject) ?></a>
				<?if(is_array($aRow["actions"]) && count($aRow["actions"]) > 0):?>
					<a href="javascript:void(0);" onclick="bxGrid_<?=$arParams["GRID_ID"]?>.ShowActionMenu(this, <?=$index?>);" title="<?echo GetMessage("interface_grid_act")?>" class="bx-disk-action"></a>
				<?endif?>
				<?if(!empty($shareInfoAction)):?>
					<a href="javascript:void(0);" onclick="<?= $shareInfoAction['ONCLICK']; ?>;" title="<?= $shareInfoAction['TEXT']; ?>" class="bx-disk-share-info bx-disk-action"></a>
				<?endif?>

			</div>
		</div>
<?endforeach; // $arParams["ROWS"]?>
<?
else: //!empty($arParams["ROWS"])
?>
	<div class="bx-disk-not-result"><?echo GetMessage("interface_grid_no_data_2")?></div>
<?endif?>
		<div class="clb"></div>
	</div>

	<table cellspacing="0" class="bx-disk-interface-grid" id="" dropzone="">
		<tbody>
			<?if($arResult["ALLOW_EDIT"] || is_array($arParams["FOOTER"]) && count($arParams["FOOTER"]) > 0 || !empty($arResult["NAV_STRING"])):?>
				<tr class="bx-disk-grid-footer">
					<td>
						<table cellpadding="0" cellspacing="0" border="0" class="bx-disk-table-footer bx-disk-grid-footer">
							<tr>
						<?if($arResult["ALLOW_EDIT"]):?>
								<td><?echo GetMessage("interface_grid_checked")?> <span id="<?=$arParams["GRID_ID"]?>_selected_span">0</span></td>
						<?endif?>
						<?foreach($arParams["FOOTER"] as $footer):?>
							<? if(!empty($footer['custom_html'])){ ?>
								<?= $footer['custom_html'] ?>
							<? } else {?>
								<td><?=$footer["title"]?>: <span <?=($footer["id"]? "id=\"{$footer["id"]}\"" : '')?>><?=$footer["value"]?></span></td>
							<? } ?>
						<?endforeach?>
								<td style="width: 100%" class="bx-disk-right"><?=(!empty($arResult["NAV_STRING"])? $arResult["NAV_STRING"] : '&nbsp;')?></td>
							</tr>
						</table>
					</td>
				</tr>
			<?endif?>
		</tbody>
	</table>


<?if($arResult["ALLOW_EDIT"]):?>
<div class="bx-disk-footer-interface-toolbar-container">
<input type="hidden" name="action_button_<?=$arParams["GRID_ID"]?>" value="">
<table cellpadding="0" cellspacing="0" border="0" class="">
<!--	<tr class="bx-top"><td class="bx-disk-left"><div class="empty"></div></td><td><div class="empty"></div></td><td class="bx-disk-right"><div class="empty"></div></td></tr>-->
	<tr>
<!--		<td class="bx-disk-left"><div class="empty"></div></td>-->
<!--		<td class="bx-content">-->
<!--			<table cellpadding="0" cellspacing="0" border="0">-->
<!--				<tr>-->
		<td style="display:none" id="bx_grid_<?=$arParams["GRID_ID"]?>_action_buttons">
			<input type="submit" name="save" value="<?echo GetMessage("interface_grid_save")?>" title="<?echo GetMessage("interface_grid_save_title")?>" class="bx-disk-btn bx-disk-btn-medium bx-disk-btn-green">
			<input type="button" name="" value="<?echo GetMessage("interface_grid_cancel")?>" title="<?echo GetMessage("interface_grid_cancel_title")?>" onclick="bxGrid_<?=$arParams["GRID_ID"]?>.ActionCancel();" class="bx-disk-btn bx-disk-btn-medium bx-disk-btn-lightgray">
		</td>

<?
$bNeedSep = false;
if($arParams["ACTION_ALL_ROWS"]):
	$bNeedSep = true;
?>
		<td>
			&nbsp;
		</td>
<?endif?>
<?if($arResult["ALLOW_INLINE_EDIT"]):?>
	<?if($bNeedSep):?>
<!--		<td><div class="bx-disk-separator"></div></td>-->
	<?endif;?>
		<td><a href="javascript:void(0);" onclick="bxGrid_<?=$arParams["GRID_ID"]?>.ActionEdit(this);" title="<?echo GetMessage("interface_grid_edit_selected")?>" class="bx-disk-bd-icon-edit" id="edit_button_<?=$arParams["GRID_ID"]?>"></a></td>
<?
	$bNeedSep = true;
endif;
?>
<?if($arParams["ACTIONS"]["delete"] == true):?>
	<?if($bNeedSep && !$arResult["ALLOW_INLINE_EDIT"]):?>
		<td><div class="bx-disk-separator"></div></td>
	<?endif?>
		<td><a href="javascript:void(0);" title="<?echo GetMessage("interface_grid_delete_title")?>" class="bx-disk-bd-icon-del" id="delete_button_<?=$arParams["GRID_ID"]?>"></a></td>
<?
	$bNeedSep = true;
endif;
?>
<?
$bShowApply = false;
if(isset($arParams["ACTIONS"]["list"]) && is_array($arParams["ACTIONS"]["list"]) && count($arParams["ACTIONS"]["list"]) > 0):
	$bShowApply = true;
?>
	<?
	if($bNeedSep):
		$bNeedSep = false;
	?>
		<td><div class="bx-disk-separator"></div></td>
	<?endif?>
		<td>
			<select name="" onchange="this.form.elements['action_button_<?=$arParams["GRID_ID"]?>'].value = this.value;">
				<option value=""><?=GetMessage("interface_grid_actions_list")?></option>
	<?foreach($arParams["ACTIONS"]["list"] as $key => $val):?>
				<option value="<?=htmlspecialcharsbx($key)?>"><?=htmlspecialcharsbx($val)?></option>
	<?endforeach?>
			</select>
		</td>
<?endif?>
<?
if($arParams["~ACTIONS"]["custom_html"] <> ''):
	$bShowApply = true;
?>
	<?if(!empty($arParams["~ACTIONS"]["before_custom_html"])):?>
		<td><?= $arParams["~ACTIONS"]["before_custom_html"] ?></td>
	<?endif?>
		<td style="padding-left:2px;"><?=$arParams["~ACTIONS"]["custom_html"]?></td>
<?endif?>
<?if($bShowApply):?>
		<td class="vam" style="padding-left:2px;"><input class="bx-disk-btn bx-disk-btn-medium bx-disk-btn-gray mb0" type="submit" name="apply" value="<?echo GetMessage("interface_grid_apply")?>" disabled></td>
<?endif?>
<!--				</tr>-->
<!--			</table>-->
<!--		</td>-->
		<td class="bx-disk-right"><div class="empty"></div></td>
	</tr>
	<tr class="bx-disk-bottom"><td class="bx-disk-left"><div class="empty"></div></td><td><div class="empty"></div></td><td class="bx-disk-right"><div class="empty"></div></td></tr>
</table>
</div>
<?endif?>
<?if($arParams["SHOW_FORM_TAG"]):?>
</form>
<?endif?>



<?
$variables = array(
	"mess"=>array(
		"calend_title"=>GetMessage("interface_grid_date"),
		"for_all_confirm"=>GetMessage("interface_grid_del_confirm"),
		"settingsTitle"=>GetMessage("interface_grid_settings_title"),
		"settingsSave"=>GetMessage("interface_grid_settings_save"),
		"viewsTitle"=>GetMessage("interface_grid_views_title"),
		"viewsApply"=>GetMessage("interface_grid_views_apply"),
		"viewsApplyTitle"=>GetMessage("interface_grid_views_apply_title"),
		"viewsNoName"=>GetMessage("interface_grid_view_noname"),
		"viewsNewView"=>GetMessage("interface_grid_views_new"),
		"viewsDelete"=>GetMessage("interface_grid_del_view"),
		"viewsFilter"=>GetMessage("interface_grid_filter_sel"),
		"filtersTitle"=>GetMessage("interface_grid_filter_saved"),
		"filtersApply"=>GetMessage("interface_grid_apply"),
		"filtersApplyTitle"=>GetMessage("interface_grid_filter_apply_title"),
		"filtersNew"=>GetMessage("interface_grid_filter_new"),
		"filtersDelete"=>GetMessage("interface_grid_filter_del"),
		"filterSettingsTitle"=>GetMessage("interface_grid_filter_title"),
		"filterHide"=>GetMessage("interface_grid_to_head_1"),
		"filterShow"=>GetMessage("interface_grid_from_head_1"),
		"filterApplyTitle"=>GetMessage("interface_grid_filter_apply"),
	),
	"ajax"=>array(
		"AJAX_ID"=>$arParams["AJAX_ID"],
		"AJAX_OPTION_SHADOW"=>(isset($arParams["AJAX_OPTION_SHADOW"]) && $arParams["AJAX_OPTION_SHADOW"] == "Y"),
	),
	"settingWndSize"=>CUtil::GetPopupSize("InterfaceGridSettingWnd"),
	"viewsWndSize"=>CUtil::GetPopupSize("InterfaceGridViewsWnd", array('height' => 350, 'width' => 500)),
	"filtersWndSize"=>CUtil::GetPopupSize("InterfaceGridFiltersWnd", array('height' => 350, 'width' => 500)),
	"filterSettingWndSize"=>CUtil::GetPopupSize("InterfaceGridFilterSettingWnd"),
	"calendar_image"=>$this->GetFolder()."/images/calendar.gif",
	"server_time"=>(time()+date("Z")+CTimeZone::GetOffset()),
	"component_path"=>$component->GetRelativePath(),
	"template_path"=>$this->GetFolder(),
	"sessid"=>bitrix_sessid(),
	"current_url"=>$arResult["CURRENT_URL"],
	"user_authorized"=>$USER->IsAuthorized(),
);
?>

<script type="text/javascript">
var settingsDialog<?=$arParams["GRID_ID"]?>;
var viewsDialog<?=$arParams["GRID_ID"]?>;
var filtersDialog<?=$arParams["GRID_ID"]?>;
var filterSettingsDialog<?=$arParams["GRID_ID"]?>;

jsDD.Reset();

if(!window['bxGrid_<?=$arParams["GRID_ID"]?>'])
	bxGrid_<?=$arParams["GRID_ID"]?> = new BxInterfaceTile('<?=$arParams["GRID_ID"]?>');

bxGrid_<?=$arParams["GRID_ID"]?>.oActions = <?=CUtil::PhpToJsObject($jsActions)?>;
bxGrid_<?=$arParams["GRID_ID"]?>.oColsMeta = <?=CUtil::PhpToJsObject($arResult["COLS_EDIT_META"])?>;
bxGrid_<?=$arParams["GRID_ID"]?>.oEditData = <?=CUtil::PhpToJsObject($arResult["DATA_FOR_EDIT"])?>;
bxGrid_<?=$arParams["GRID_ID"]?>.oColsNames = <?=CUtil::PhpToJsObject(htmlspecialcharsback($arResult["COLS_NAMES"]))?>;
bxGrid_<?=$arParams["GRID_ID"]?>.oOptions = <?=CUtil::PhpToJsObject($arResult["OPTIONS"])?>;
bxGrid_<?=$arParams["GRID_ID"]?>.vars = <?=CUtil::PhpToJsObject($variables)?>;
bxGrid_<?=$arParams["GRID_ID"]?>.menu = new PopupMenu('bxMenu_<?=$arParams["GRID_ID"]?>', 1010);
bxGrid_<?=$arParams["GRID_ID"]?>.settingsMenu = [
	{'TEXT': '<?=CUtil::JSEscape(GetMessage("interface_grid_views_setup"))?>', 'TITLE': '<?=CUtil::JSEscape(GetMessage("interface_grid_views_setup_title"))?>', 'DEFAULT':true, 'ONCLICK':'bxGrid_<?=$arParams["GRID_ID"]?>.EditCurrentView()', 'DISABLED':<?=($USER->IsAuthorized()? 'false':'true')?>, 'ICONCLASS':'grid-settings'},
	{'TEXT': '<?=CUtil::JSEscape(GetMessage("interface_grid_columns"))?>', 'TITLE': '<?=CUtil::JSEscape(GetMessage("interface_grid_columns_title"))?>', 'MENU':[
<?
foreach($arParams["HEADERS"] as $header):
?>
		{'TEXT': '<?=CUtil::JSEscape($header["name"])?>', 'TITLE': '<?=CUtil::JSEscape(GetMessage("interface_grid_columns_showhide"))?>',<?if(array_key_exists($header["id"], $arResult["HEADERS"])):?>'ICONCLASS':'checked',<?endif?> 'ONCLICK':'bxGrid_<?=$arParams["GRID_ID"]?>.CheckColumn(\'<?=CUtil::JSEscape($header["id"])?>\', this)', 'AUTOHIDE':false},
<?
endforeach;
?>
		{'SEPARATOR': true},
		{'TEXT': '<?=CUtil::JSEscape(GetMessage("interface_grid_columns_apply"))?>', 'TITLE': '<?=CUtil::JSEscape(GetMessage("interface_grid_columns_apply_title"))?>', 'ONCLICK':'bxGrid_<?=$arParams["GRID_ID"]?>.ApplySaveColumns()'}
	], 'DISABLED':<?=($USER->IsAuthorized()? 'false':'true')?>},
	{'SEPARATOR': true},
<?
foreach($arResult["OPTIONS"]["views"] as $view_id=>$view):
?>
	{'TEXT': '<?=htmlspecialcharsbx($view["name"]<>''? CUtil::JSEscape($view["name"]) : GetMessage("interface_grid_view_noname"))?>', 'TITLE': '<?=CUtil::JSEscape(GetMessage("interface_grid_view_title"))?>'<?if($view_id == $arResult["OPTIONS"]["current_view"]):?>, 'ICONCLASS':'checked'<?endif?>, 'DISABLED':<?=($USER->IsAuthorized()? 'false':'true')?>, 'ONCLICK':'bxGrid_<?=$arParams["GRID_ID"]?>.SetView(\'<?=$view_id?>\')'},
<?
endforeach;
?>
	{'SEPARATOR': true},
	{'TEXT': '<?=CUtil::JSEscape(GetMessage("interface_grid_views"))?>', 'TITLE': '<?=CUtil::JSEscape(GetMessage("interface_grid_views_mnu_title"))?>', 'ONCLICK':'bxGrid_<?=$arParams["GRID_ID"]?>.ShowViews()', 'DISABLED':<?=($USER->IsAuthorized()? 'false':'true')?>, 'ICONCLASS':'grid-views'}
];

BX.ready(function(){bxGrid_<?=$arParams["GRID_ID"]?>.InitTable()});

<?if(!empty($arParams["FILTER"])):?>
bxGrid_<?=$arParams["GRID_ID"]?>.oFilterRows = <?=CUtil::PhpToJsObject($arResult["FILTER_ROWS"])?>;
bxGrid_<?=$arParams["GRID_ID"]?>.filterMenu = [
	{'TEXT': '<?=CUtil::JSEscape(GetMessage("interface_grid_flt_rows"))?>', 'TITLE': '<?=CUtil::JSEscape(GetMessage("interface_grid_flt_rows_title"))?>', 'MENU':[
<?foreach($arParams["FILTER"] as $field):?>
		{'ID':'flt_<?=$arParams["GRID_ID"]?>_<?=$field["id"]?>', 'TEXT': '<?=CUtil::JSEscape($field["name"])?>', 'ONCLICK':'bxGrid_<?=$arParams["GRID_ID"]?>.SwitchFilterRow(\'<?=CUtil::JSEscape($field["id"])?>\', this)', 'AUTOHIDE':false<?if($arResult["FILTER_ROWS"][$field["id"]]):?>, 'ICONCLASS':'checked'<?endif?>},
<?endforeach?>
		{'SEPARATOR': true},
		{'TEXT': '<?=CUtil::JSEscape(GetMessage("interface_grid_flt_show_all"))?>', 'ONCLICK':'bxGrid_<?=$arParams["GRID_ID"]?>.SwitchFilterRows(true)'},
		{'TEXT': '<?=CUtil::JSEscape(GetMessage("interface_grid_flt_hide_all"))?>', 'ONCLICK':'bxGrid_<?=$arParams["GRID_ID"]?>.SwitchFilterRows(false)'}
	]},
<?if(is_array($arResult["OPTIONS"]["filters"]) && !empty($arResult["OPTIONS"]["filters"])):?>
	{'SEPARATOR': true},
<?foreach($arResult["OPTIONS"]["filters"] as $filter_id=>$filter):?>
	{'ID': 'mnu_<?=$arParams["GRID_ID"]?>_<?=$filter_id?>', 'TEXT': '<?=htmlspecialcharsbx(CUtil::JSEscape($filter["name"]))?>', 'TITLE': '<?=CUtil::JSEscape(GetMessage("interface_grid_filter_apply"))?>', 'ONCLICK':'bxGrid_<?=$arParams["GRID_ID"]?>.ApplyFilter(\'<?=CUtil::JSEscape($filter_id)?>\')'},
<?
	endforeach;
endif;
?>
	{'SEPARATOR': true},
	{'TEXT': '<?=CUtil::JSEscape(GetMessage("interface_grid_filters"))?>', 'TITLE': '<?=CUtil::JSEscape(GetMessage("interface_grid_filters_title"))?>', 'ONCLICK':'bxGrid_<?=$arParams["GRID_ID"]?>.ShowFilters()', 'DISABLED':<?=($USER->IsAuthorized()? 'false':'true')?>, 'ICONCLASS':'grid-filters'},
	{'TEXT': '<?=CUtil::JSEscape(GetMessage("interface_grid_filters_save"))?>', 'TITLE': '<?=CUtil::JSEscape(GetMessage("interface_grid_filters_save_title"))?>', 'ONCLICK':'bxGrid_<?=$arParams["GRID_ID"]?>.AddFilterAs()', 'DISABLED':<?=($USER->IsAuthorized() && !empty($arResult["FILTER"])? 'false':'true')?>}
];

BX.ready(function(){bxGrid_<?=$arParams["GRID_ID"]?>.InitFilter()});
<?endif?>

phpVars.messLoading = '<?=GetMessageJS("interface_grid_loading")?>';
</script>